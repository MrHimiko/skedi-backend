<?php

namespace App\Plugins\Account\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Service\ResponseService;
use App\Plugins\Account\Service\UserAvailabilityService;
use App\Plugins\Account\Repository\UserRepository;
use App\Plugins\Account\Exception\AccountException;
use App\Plugins\Account\Entity\UserAvailabilityEntity;

#[Route('/api')]
class UserAvailabilityController extends AbstractController
{
    private ResponseService $responseService;
    private UserAvailabilityService $availabilityService;
    private UserRepository $userRepository;

    public function __construct(
        ResponseService $responseService,
        UserAvailabilityService $availabilityService,
        UserRepository $userRepository
    ) {
        $this->responseService = $responseService;
        $this->availabilityService = $availabilityService;
        $this->userRepository = $userRepository;
    }

    /**
     * Public endpoint to check if a user is available at a specific time
     * No authentication required
     */
    #[Route('/public/users/{user_id}/availability', name: 'public_user_availability', methods: ['GET'])]
    public function checkPublicAvailability(int $user_id, Request $request): JsonResponse
    {
        $startStr = $request->query->get('start_time');
        $endStr = $request->query->get('end_time');
        $timezone = $request->query->get('timezone', 'UTC');

        try {
            // Find the user
            $user = $this->userRepository->find($user_id);
            if (!$user) {
                return $this->responseService->json(false, 'not-found', null, 404);
            }

            if (!$startStr || !$endStr) {
                return $this->responseService->json(false, 'Start time and end time are required', null, 400);
            }

            // Validate and convert timezone
            try {
                $tz = new \DateTimeZone($timezone);
            } catch (\Exception $e) {
                $timezone = 'UTC';
                $tz = new \DateTimeZone($timezone);
            }

            // Parse times in the specified timezone
            $startTime = new \DateTime($startStr, $tz);
            $endTime = new \DateTime($endStr, $tz);

            if ($startTime >= $endTime) {
                return $this->responseService->json(false, 'End time must be after start time', null, 400);
            }

            // Convert to UTC for database operations
            $startTime->setTimezone(new \DateTimeZone('UTC'));
            $endTime->setTimezone(new \DateTimeZone('UTC'));

            $isAvailable = $this->availabilityService->isUserAvailable($user, $startTime, $endTime);

            return $this->responseService->json(true, 'retrieve', [
                'user_id' => $user_id,
                'available' => $isAvailable,
                'start_time' => $startStr,
                'end_time' => $endStr,
                'timezone' => $timezone
            ]);
        } catch (AccountException $e) {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        } catch (\Exception $e) {
            return $this->responseService->json(false, $e, null, 500);
        }
    }
    
    /**
     * Get authenticated user's availability within a time range
     * Requires authentication
     */
    #[Route('/user/availability', name: 'user_availability_get#', methods: ['GET'])]
    public function getUserAvailability(Request $request): JsonResponse
    {
        $user = $request->attributes->get('user');
        $startStr = $request->query->get('start_time');
        $endStr = $request->query->get('end_time');

        try {
            if (!$startStr || !$endStr) {
                return $this->responseService->json(false, 'Start time and end time are required', null, 400);
            }

            $startTime = new \DateTime($startStr);
            $endTime = new \DateTime($endStr);

            if ($startTime >= $endTime) {
                return $this->responseService->json(false, 'End time must be after start time', null, 400);
            }

            $availability = $this->availabilityService->getAvailabilityByRange($user, $startTime, $endTime);
            
            // Convert to public format (without sensitive details)
            $result = array_map(function ($item) {
                return $item->toPublicArray();
            }, $availability);

            return $this->responseService->json(true, 'Availability retrieved successfully', $result);
        } catch (AccountException $e) {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        } catch (\Exception $e) {
            return $this->responseService->json(false, $e, null, 500);
        }
    }
    
    /**
     * Create a new availability block for authenticated user
     * Requires authentication
     */
    #[Route('/user/availability', name: 'user_availability_create#', methods: ['POST'])]
    public function createAvailability(Request $request): JsonResponse
    {
        $user = $request->attributes->get('user');
        $data = $request->attributes->get('data');

        try {
            if (empty($data['title']) || empty($data['start_time']) || empty($data['end_time'])) {
                return $this->responseService->json(false, 'Title, start time, and end time are required', null, 400);
            }

            $startTime = $data['start_time'] instanceof \DateTimeInterface 
                ? $data['start_time'] 
                : new \DateTime($data['start_time']);
            
            $endTime = $data['end_time'] instanceof \DateTimeInterface 
                ? $data['end_time'] 
                : new \DateTime($data['end_time']);

            $availability = $this->availabilityService->createInternalAvailability(
                $user,
                $data['title'],
                $startTime,
                $endTime,
                null, // No event
                null, // No booking
                $data['description'] ?? null
            );

            return $this->responseService->json(true, 'Availability created successfully', $availability->toArray());
        } catch (AccountException $e) {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        } catch (\Exception $e) {
            return $this->responseService->json(false, $e, null, 500);
        }
    }
    
    /**
     * Update an existing availability block
     * Requires authentication and ownership
     */
    #[Route('/user/availability/{id}', name: 'user_availability_update#', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function updateAvailability(int $id, Request $request): JsonResponse
    {
        $user = $request->attributes->get('user');
        $data = $request->attributes->get('data');

        try {
            $repo = $this->getDoctrine()->getRepository(UserAvailabilityEntity::class);
            $availability = $repo->find($id);
            
            if (!$availability) {
                return $this->responseService->json(false, 'Availability not found', null, 404);
            }
            
            // Security check - only allow users to update their own availability
            if ($availability->getUser()->getId() !== $user->getId()) {
                return $this->responseService->json(false, 'Access denied', null, 403);
            }
            
            // Only allow updating internal availability records
            if ($availability->getSource() !== 'internal') {
                return $this->responseService->json(false, 'Cannot update external availability', null, 400);
            }

            // Convert date strings to DateTime objects
            if (!empty($data['start_time']) && is_string($data['start_time'])) {
                $data['start_time'] = new \DateTime($data['start_time']);
            }
            
            if (!empty($data['end_time']) && is_string($data['end_time'])) {
                $data['end_time'] = new \DateTime($data['end_time']);
            }

            $this->availabilityService->updateAvailability($availability, $data);

            return $this->responseService->json(true, 'Availability updated successfully', $availability->toArray());
        } catch (AccountException $e) {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        } catch (\Exception $e) {
            return $this->responseService->json(false, $e, null, 500);
        }
    }
    
    /**
     * Delete an availability block
     * Requires authentication and ownership
     */
    #[Route('/user/availability/{id}', name: 'user_availability_delete#', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function deleteAvailability(int $id, Request $request): JsonResponse
    {
        $user = $request->attributes->get('user');

        try {
            $repo = $this->getDoctrine()->getRepository(UserAvailabilityEntity::class);
            $availability = $repo->find($id);
            
            if (!$availability) {
                return $this->responseService->json(false, 'Availability not found', null, 404);
            }
            
            // Security check - only allow users to delete their own availability
            if ($availability->getUser()->getId() !== $user->getId()) {
                return $this->responseService->json(false, 'Access denied', null, 403);
            }
            
            // Only allow deleting internal availability records
            if ($availability->getSource() !== 'internal') {
                return $this->responseService->json(false, 'Cannot delete external availability', null, 400);
            }

            $this->availabilityService->deleteAvailability($availability);

            return $this->responseService->json(true, 'Availability deleted successfully');
        } catch (AccountException $e) {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        } catch (\Exception $e) {
            return $this->responseService->json(false, $e, null, 500);
        }
    }
}