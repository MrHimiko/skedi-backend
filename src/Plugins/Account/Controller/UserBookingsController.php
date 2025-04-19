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

#[Route('/api')]
class UserBookingsController extends AbstractController
{
    private ResponseService $responseService;
    private UserRepository $userRepository;
    private CrudManager $crudManager;

    public function __construct(
        ResponseService $responseService,
        UserRepository $userRepository,
        CrudManager $crudManager
    ) {
        $this->responseService = $responseService;
        $this->userRepository = $userRepository;
        $this->crudManager = $crudManager;
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
            $internalOnly = $request->query->get('internal') === 'true';
            
            if (!$startTime || !$endTime) {
                return $this->responseService->json(false, 'Start time and end time are required', null, 400);
            }
            
            // Parse dates
            $startDate = new \DateTime($startTime);
            $endDate = new \DateTime($endTime);
            
            if ($startDate >= $endDate) {
                return $this->responseService->json(false, 'End time must be after start time', null, 400);
            }
            
            // Print debug information
            error_log("Date Range: " . $startDate->format('Y-m-d H:i:s') . " to " . $endDate->format('Y-m-d H:i:s'));
            
            // Setup filters - simplified date range filter
            $filters = [];
            
            // Add status filter
            $now = new \DateTime();
            
            switch ($status) {
                case 'upcoming':
                    // For upcoming events, only filter by end time being in the future
                    $filters[] = [
                        'field' => 'endTime',
                        'operator' => 'greater_than',
                        'value' => $now
                    ];
                    // Status check moved to criteria below for better compatibility with CrudManager
                    break;
                
                case 'past':
                    $filters[] = [
                        'field' => 'endTime',
                        'operator' => 'less_than',
                        'value' => $now
                    ];
                    break;
                
                case 'cancelled':
                    // Status check moved to criteria below
                    break;
                
                case 'active':
                    // Status check moved to criteria below
                    break;
                
                // 'all' or any other value, no additional filter
            }
            
            // Setup criteria with status checks moved here from filters
            $criteria = [
                'user' => $user,
                'deleted' => false
            ];
            
            // Status-specific criteria
            if ($status === 'cancelled') {
                $criteria['status'] = 'cancelled';
            } else if ($status === 'active' || $status === 'upcoming') {
                $criteria['status'] = 'confirmed'; // Assuming 'confirmed' is your active status
            }
            
            // Filter by source if internal only requested
            if ($internalOnly) {
                $criteria['source'] = 'internal';
            }
            
            // Debug criteria
            error_log("Criteria: " . json_encode($criteria));
            
            // Manually handle date filtering with a callback for more control
            // Get bookings using CrudManager
            $bookings = $this->crudManager->findMany(
                UserAvailabilityEntity::class,
                $filters,
                $page,
                $limit,
                $criteria,
                function($queryBuilder) use ($startDate, $endDate) {
                    // This ensures we get bookings that overlap with our date range
                    $queryBuilder->andWhere('(t1.startTime < :endDate AND t1.endTime > :startDate)')
                        ->setParameter('startDate', $startDate)
                        ->setParameter('endDate', $endDate);
                    
                    // Add debug
                    error_log($queryBuilder->getQuery()->getSQL());
                }
            );
            
            // Get total count for pagination
            $totalCount = $this->crudManager->findMany(
                UserAvailabilityEntity::class,
                $filters,
                1,
                1,
                $criteria,
                null,
                true
            );
            
            // Format results
            $formattedBookings = [];
            foreach ($bookings as $booking) {
                $bookingData = $booking->toArray();
                $formattedBookings[] = $bookingData;
            }
            
            // Pagination info
            $totalPages = ceil($totalCount[0] / $limit);
            
            return $this->responseService->json(true, 'retrieve', [
                'bookings' => $formattedBookings,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_items' => $totalCount[0],
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