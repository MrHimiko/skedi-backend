<?php

namespace App\Plugins\Account\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Service\ResponseService;
use App\Plugins\Account\Repository\UserRepository;
use App\Plugins\Account\Entity\UserAvailabilityEntity;
use App\Plugins\Account\Exception\AccountException;
use App\Service\CrudManager;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/api')]
class UserBookingsController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(
        ResponseService $responseService,
        UserRepository $userRepository,
        CrudManager $crudManager,
        EntityManagerInterface $entityManager
    ) {
        $this->responseService = $responseService;
        $this->userRepository = $userRepository;
        $this->crudManager = $crudManager;
        $this->entityManager = $entityManager;
    }

    /**
     * Get a user's bookings with filtering options
     * Requires authentication and authorization
     */
    #[Route('/user/{id}/bookings', name: 'user_bookings_get#', methods: ['GET'])]
    public function getUserBookings(int $id, Request $request): JsonResponse
    {
        $authenticatedUser = $request->attributes->get('user');
        
        // Security check - only allow access to own data
        if ($authenticatedUser->getId() !== $id) {
            return $this->responseService->json(false, 'deny', null, 403);
        }
        
        try {
            $user = $this->userRepository->find($id);
            if (!$user) {
                return $this->responseService->json(false, 'not-found', null, 404);
            }
            
            // Get query parameters
            $startTime = $request->query->get('start_time');
            $endTime = $request->query->get('end_time');
            $status = $request->query->get('status', 'all');
            $page = max(1, (int)$request->query->get('page', 1));
            $limit = min(100, max(10, (int)$request->query->get('page_size', 20)));
            
            if (!$startTime || !$endTime) {
                return $this->responseService->json(false, 'Start time and end time are required', null, 400);
            }
            
            // Parse dates
            $startDate = new \DateTime($startTime);
            $endDate = new \DateTime($endTime);
            
            if ($startDate >= $endDate) {
                return $this->responseService->json(false, 'End time must be after start time', null, 400);
            }
            
            // Get availability records for the user
            $availabilityRecords = $this->crudManager->findMany(
                'App\Plugins\Account\Entity\UserAvailabilityEntity',
                [
                    [
                        'field' => 'startTime',
                        'operator' => 'greater_than_or_equal',
                        'value' => $startDate
                    ],
                    [
                        'field' => 'endTime',
                        'operator' => 'less_than_or_equal',
                        'value' => $endDate
                    ]
                ],
                1,
                1000,
                [
                    'user' => $user,
                    'deleted' => false
                ],
                function($queryBuilder) {
                    $queryBuilder->orderBy('t1.startTime', 'ASC');
                }
            );
            
            // Extract booking IDs from availability records
            $bookingIds = [];
            foreach ($availabilityRecords as $record) {
                if ($record->getBooking() !== null) {
                    $bookingIds[] = $record->getBooking()->getId();
                }
            }
            
            // If no bookings found, return empty array
            if (empty($bookingIds)) {
                return $this->responseService->json(true, 'retrieve', [
                    'bookings' => [],
                    'pagination' => [
                        'current_page' => $page,
                        'total_pages' => 0,
                        'total_items' => 0,
                        'page_size' => $limit
                    ]
                ]);
            }
            
            // Fetch the actual booking data from event_bookings table
            $bookings = $this->entityManager->getRepository('App\Plugins\Events\Entity\EventBookingEntity')
                ->findBy(['id' => $bookingIds]);
            
            // Format results - combine data from both entities
            $formattedBookings = [];
            foreach ($bookings as $booking) {
                // Find the corresponding availability record
                $availabilityRecord = null;
                foreach ($availabilityRecords as $record) {
                    if ($record->getBooking() && $record->getBooking()->getId() === $booking->getId()) {
                        $availabilityRecord = $record;
                        break;
                    }
                }
                
                // Skip if we can't find the matching record (shouldn't happen)
                if (!$availabilityRecord) continue;
                
                // Apply status filter if needed
                if ($status !== 'all') {
                    if ($status === 'past') {
                        $now = new \DateTime();
                        if ($booking->getEndTime() >= $now || $booking->getStatus() === 'removed') continue;
                    } elseif ($status === 'upcoming') {
                        $now = new \DateTime();
                        if ($booking->getStartTime() <= $now || $booking->getStatus() === 'removed') continue;
                    } elseif ($booking->getStatus() !== $status || $booking->getStatus() === 'removed') {
                        continue;
                    }
                } else {
                    if ($booking->getStatus() === 'removed') continue;
                }
                
                // Create a combined record with data from both entities
                $formattedBookings[] = [
                    'id' => $availabilityRecord->getId(),
                    'user_id' => $user->getId(),
                    'title' => $availabilityRecord->getTitle(),
                    'description' => $availabilityRecord->getDescription(),
                    'start_time' => $booking->getStartTime()->format('Y-m-d H:i:s'),
                    'end_time' => $booking->getEndTime()->format('Y-m-d H:i:s'),
                    'source' => $availabilityRecord->getSource(),
                    'source_id' => $availabilityRecord->getSourceId(),
                    'status' => $booking->getStatus(), // Get status from booking
                    'booking_id' => $booking->getId(),
                    'event_id' => $booking->getEvent()->getId(),
                    'cancelled' => $booking->isCancelled(),
                    'created' => $availabilityRecord->getCreated()->format('Y-m-d H:i:s'),
                    'updated' => $availabilityRecord->getUpdated()->format('Y-m-d H:i:s')
                ];
            }
            
            // Apply pagination to formatted results
            $total = count($formattedBookings);
            $offset = ($page - 1) * $limit;
            $pagedBookings = array_slice($formattedBookings, $offset, $limit);
            
            return $this->responseService->json(true, 'retrieve', [
                'bookings' => $pagedBookings,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => ceil($total / $limit),
                    'total_items' => $total,
                    'page_size' => $limit
                ]
            ]);
        } catch (AccountException $e) {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        } catch (\Exception $e) {
            return $this->responseService->json(false, $e, null, 500);
        }
    }
}