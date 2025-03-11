<?php
namespace App\Plugins\Events\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Service\ResponseService;
use App\Plugins\Events\Service\EventService;
use App\Plugins\Events\Exception\EventsException;
use App\Plugins\Organizations\Service\UserOrganizationService;
use App\Plugins\Teams\Service\TeamService;

#[Route('/api')]
class EventController extends AbstractController
{
    private ResponseService $responseService;
    private EventService $eventService;
    private UserOrganizationService $userOrganizationService;
    private TeamService $teamService;

    public function __construct(
        ResponseService $responseService,
        EventService $eventService,
        UserOrganizationService $userOrganizationService,
        TeamService $teamService
    ) {
        $this->responseService = $responseService;
        $this->eventService = $eventService;
        $this->userOrganizationService = $userOrganizationService;
        $this->teamService = $teamService;
    }

    #[Route('/events', name: 'events_get_many#', methods: ['GET'])]
    public function getEvents(Request $request): JsonResponse
    {
        $user = $request->attributes->get('user');
        $filters = $request->attributes->get('filters');
        $page = $request->attributes->get('page');
        $limit = $request->attributes->get('limit');
        $organization_id = $request->query->get('organization_id');

        try {
            // Check if organization_id is provided
            if (!$organization_id) {
                return $this->responseService->json(false, 'Organization ID is required.');
            }

            // Check if user has access to this organization
            if (!$organization = $this->userOrganizationService->getOrganizationByUser($organization_id, $user)) {
                return $this->responseService->json(false, 'Organization was not found.');
            }
            
            // Get events within this organization
            $events = $this->eventService->getMany($filters, $page, $limit, [
                'organization' => $organization->entity
            ]);
            
            $result = [];
            foreach ($events as $event) {
                $eventData = $event->toArray();
                
                // Add time slots
                $timeSlots = $this->eventService->getTimeSlots($event);
                $eventData['time_slots'] = array_map(function($slot) {
                    return $slot->toArray();
                }, $timeSlots);
                
                // Add booking options
                $bookingOptions = $this->eventService->getBookingOptions($event);
                $eventData['booking_options'] = array_map(function($option) {
                    return $option->toArray();
                }, $bookingOptions);
                
                // Add assignees
                $assignees = $this->eventService->getAssignees($event);
                $eventData['assignees'] = array_map(function($assignee) {
                    return $assignee->toArray();
                }, $assignees);
                
                $result[] = $eventData;
            }
            
            return $this->responseService->json(true, 'Events retrieved successfully.', $result);
        } catch (EventsException $e) {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        } catch (\Exception $e) {
            return $this->responseService->json(false, $e, null, 500);
        }
    }

    #[Route('/events/{id}', name: 'events_get_one#', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getEventById(int $id, Request $request): JsonResponse
    {
        $user = $request->attributes->get('user');
        $organization_id = $request->query->get('organization_id');
        
        try {
            // Check if organization_id is provided
            if (!$organization_id) {
                return $this->responseService->json(false, 'Organization ID is required.');
            }
            
            // Check if user has access to this organization
            if (!$organization = $this->userOrganizationService->getOrganizationByUser($organization_id, $user)) {
                return $this->responseService->json(false, 'Organization was not found.');
            }
            
            // Get event by ID ensuring it belongs to the organization
            if (!$event = $this->eventService->getEventByIdAndOrganization($id, $organization->entity)) {
                return $this->responseService->json(false, 'Event was not found.');
            }
            
            $eventData = $event->toArray();
            
            // Add time slots
            $timeSlots = $this->eventService->getTimeSlots($event);
            $eventData['time_slots'] = array_map(function($slot) {
                return $slot->toArray();
            }, $timeSlots);
            
            // Add form fields
            $formFields = $this->eventService->getFormFields($event);
            $eventData['form_fields'] = array_map(function($field) {
                return $field->toArray();
            }, $formFields);
            
            // Add booking options
            $bookingOptions = $this->eventService->getBookingOptions($event);
            $eventData['booking_options'] = array_map(function($option) {
                return $option->toArray();
            }, $bookingOptions);
            
            // Add assignees
            $assignees = $this->eventService->getAssignees($event);
            $eventData['assignees'] = array_map(function($assignee) {
                return $assignee->toArray();
            }, $assignees);
            
            return $this->responseService->json(true, 'Event retrieved successfully.', $eventData);
        } catch (EventsException $e) {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        } catch (\Exception $e) {
            return $this->responseService->json(false, $e, null, 500);
        }
    }

    #[Route('/events', name: 'events_create#', methods: ['POST'])]
    public function createEvent(Request $request): JsonResponse
    {
        $user = $request->attributes->get('user');
        $data = $request->attributes->get('data');
        $organization_id = $data['organization_id'] ?? $request->query->get('organization_id');
        
        try {
            if ($request->query->has('organization_id')) {
                $data['organization_id'] = (int)$request->query->get('organization_id');
            }
            // Check if organization_id is provided
            if (!$organization_id) {
                return $this->responseService->json(false, 'Organization ID is required.');
            }
            
            // Check if user has access to this organization
            if (!$organization = $this->userOrganizationService->getOrganizationByUser($organization_id, $user)) {
                return $this->responseService->json(false, 'Organization was not found.');
            }
      
            if (!isset($data['duration'])) {
                $data['duration'] = 30; 
            }
                
            // Rest of your existing code
            if (!empty($data['team_id'])) {
                $team = $this->teamService->getTeamByIdAndOrganization($data['team_id'], $organization->entity);
                if (!$team) {
                    return $this->responseService->json(false, 'Team was not found or does not belong to this organization.');
                }
            }
            
            // Create event with organization and creator set
            $event = $this->eventService->create($data, function($event) use ($organization, $user, $data) {
                $event->setOrganization($organization->entity);
                $event->setCreatedBy($user);
                
                // Set team if provided
                if (!empty($data['team_id'])) {
                    $team = $this->teamService->getTeamByIdAndOrganization($data['team_id'], $organization->entity);
                    if ($team) {
                        $event->setTeam($team);
                    }
                }
            });
            
            // Rest of the code remains the same
            $eventData = $event->toArray();
            
            // Add time slots, form fields, booking options, and assignees to response
            $timeSlots = $this->eventService->getTimeSlots($event);
            $eventData['time_slots'] = array_map(function($slot) {
                return $slot->toArray();
            }, $timeSlots);
            
            $formFields = $this->eventService->getFormFields($event);
            $eventData['form_fields'] = array_map(function($field) {
                return $field->toArray();
            }, $formFields);
            
            $bookingOptions = $this->eventService->getBookingOptions($event);
            $eventData['booking_options'] = array_map(function($option) {
                return $option->toArray();
            }, $bookingOptions);
            
            $assignees = $this->eventService->getAssignees($event);
            $eventData['assignees'] = array_map(function($assignee) {
                return $assignee->toArray();
            }, $assignees);
            
            return $this->responseService->json(true, 'Event created successfully.', $eventData, 201);
        } catch (EventsException $e) {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        } catch (\Exception $e) {
            return $this->responseService->json(false, $e, null, 500);
        }
    }

    #[Route('/events/{id}', name: 'events_update#', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function updateEvent(int $id, Request $request): JsonResponse
    {
        $user = $request->attributes->get('user');
        $data = $request->attributes->get('data');
        $organization_id = $data['organization_id'] ?? $request->query->get('organization_id');
        
        try {
            // Check if organization_id is provided
           
            if ($request->query->has('organization_id')) {
                $data['organization_id'] = (int)$request->query->get('organization_id');
            }
            if (!$organization_id) {
                return $this->responseService->json(false, 'Organization ID is required.');
            }
            
            // Check if user has access to this organization
            if (!$organization = $this->userOrganizationService->getOrganizationByUser($organization_id, $user)) {
                return $this->responseService->json(false, 'Organization was not found.');
            }
            
            // Get event by ID ensuring it belongs to the organization
            if (!$event = $this->eventService->getEventByIdAndOrganization($id, $organization->entity)) {
                return $this->responseService->json(false, 'Event was not found.');
            }
            
            // Rest of your existing code
            if (!empty($data['team_id'])) {
                $team = $this->teamService->getTeamByIdAndOrganization($data['team_id'], $organization->entity);
                if (!$team) {
                    return $this->responseService->json(false, 'Team was not found or does not belong to this organization.');
                }
                $data['team'] = $team; // Set the actual team object
            }
            
            $this->eventService->update($event, $data);
            
            $eventData = $event->toArray();
            
            // Add time slots, form fields, booking options, and assignees to response
            $timeSlots = $this->eventService->getTimeSlots($event);
            $eventData['time_slots'] = array_map(function($slot) {
                return $slot->toArray();
            }, $timeSlots);
            
            $formFields = $this->eventService->getFormFields($event);
            $eventData['form_fields'] = array_map(function($field) {
                return $field->toArray();
            }, $formFields);
            
            $bookingOptions = $this->eventService->getBookingOptions($event);
            $eventData['booking_options'] = array_map(function($option) {
                return $option->toArray();
            }, $bookingOptions);
            
            $assignees = $this->eventService->getAssignees($event);
            $eventData['assignees'] = array_map(function($assignee) {
                return $assignee->toArray();
            }, $assignees);
            
            return $this->responseService->json(true, 'Event updated successfully.', $eventData);
        } catch (EventsException $e) {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        } catch (\Exception $e) {
            return $this->responseService->json(false, $e, null, 500);
        }
    }

    #[Route('/events/{id}', name: 'events_delete#', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function deleteEvent(int $id, Request $request): JsonResponse
    {
        $user = $request->attributes->get('user');
        $organization_id = $request->query->get('organization_id');
        
        try {
            // Check if organization_id is provided
            if (!$organization_id) {
                return $this->responseService->json(false, 'Organization ID is required.');
            }
            
            // Check if user has access to this organization
            if (!$organization = $this->userOrganizationService->getOrganizationByUser($organization_id, $user)) {
                return $this->responseService->json(false, 'Organization was not found.');
            }
            
            // Get event by ID ensuring it belongs to the organization
            if (!$event = $this->eventService->getEventByIdAndOrganization($id, $organization->entity)) {
                return $this->responseService->json(false, 'Event was not found.');
            }
            
            $this->eventService->delete($event);
            return $this->responseService->json(true, 'Event deleted successfully.');
        } catch (EventsException $e) {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        } catch (\Exception $e) {
            return $this->responseService->json(false, $e, null, 500);
        }
    }
}