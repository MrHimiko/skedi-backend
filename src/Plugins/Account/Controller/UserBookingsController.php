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
            
            if (!$startTime || !$endTime) {
                return $this->responseService->json(false, 'Start time and end time are required', null, 400);
            }
            
            // Parse dates
            $startDate = new \DateTime($startTime);
            $endDate = new \DateTime($endTime);
            
            if ($startDate >= $endDate) {
                return $this->responseService->json(false, 'End time must be after start time', null, 400);
            }
            
            // Setup filters for date range - always use the provided start/end time
            $filters = [
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
            ];
            
            // Basic criteria
            $criteria = [
                'user' => $user,
                'deleted' => false
            ];
            
            // Handle status filtering - only filter by status if specified
            if ($status !== 'all' && $status !== 'past' && $status !== 'upcoming') {
                $criteria['status'] = $status;
            } else if ($status === 'past') {
                // Past bookings - add as a filter rather than criteria
                $now = new \DateTime();
                $filters[] = [
                    'field' => 'endTime',
                    'operator' => 'less_than',
                    'value' => $now
                ];
            } else if ($status === 'upcoming') {
                // Upcoming bookings - add as a filter rather than criteria
                $now = new \DateTime();
                $filters[] = [
                    'field' => 'startTime',
                    'operator' => 'greater_than',
                    'value' => $now
                ];
            }
            
            // Add sorting
            $callback = function($queryBuilder) {
                $queryBuilder->orderBy('t1.startTime', 'ASC');
            };
            
            // Use UserAvailabilityEntity
            $bookings = $this->crudManager->findMany(
                'App\Plugins\Account\Entity\UserAvailabilityEntity',
                $filters,
                $page,
                $limit,
                $criteria,
                $callback
            );
            
            // Get total count for pagination
            $totalCount = $this->crudManager->findMany(
                'App\Plugins\Account\Entity\UserAvailabilityEntity',
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
                $formattedBookings[] = $booking->toArray();
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