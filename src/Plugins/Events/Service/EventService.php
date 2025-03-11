<?php

namespace App\Plugins\Events\Service;

use App\Service\CrudManager;
use App\Exception\CrudException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraints as Assert;

use App\Plugins\Events\Entity\EventEntity;
use App\Plugins\Events\Entity\EventScheduleEntity;
use App\Plugins\Events\Entity\EventFormFieldEntity;
use App\Plugins\Events\Entity\EventBookingOptionEntity;
use App\Plugins\Events\Entity\EventAssigneeEntity;
use App\Plugins\Events\Exception\EventsException;

use App\Plugins\Organizations\Entity\OrganizationEntity;
use App\Plugins\Teams\Entity\TeamEntity;
use App\Plugins\Account\Entity\UserEntity;
use App\Service\SlugService;

class EventService
{
    private CrudManager $crudManager;
    private EntityManagerInterface $entityManager;
    private SlugService $slugService;

    public function __construct(
        CrudManager $crudManager,
        EntityManagerInterface $entityManager,
        SlugService $slugService
    ) {
        $this->crudManager = $crudManager;
        $this->entityManager = $entityManager;
        $this->slugService = $slugService;
    }

    public function getMany(array $filters, int $page, int $limit, array $criteria = []): array
    {
        try {
            return $this->crudManager->findMany(
                EventEntity::class,
                $filters,
                $page,
                $limit,
                $criteria + [
                    'deleted' => false
                ]
            );
        } catch (CrudException $e) {
            throw new EventsException($e->getMessage());
        }
    }

    public function getOne(int $id, array $criteria = []): ?EventEntity
    {
        return $this->crudManager->findOne(EventEntity::class, $id, $criteria + ['deleted' => false]);
    }

    public function create(array $data, ?callable $callback = null): EventEntity
    {
        try {
            $event = new EventEntity();
            
            if ($callback) {
                $callback($event);
            }
            
            // Generate slug if not provided
            if(!array_key_exists('slug', $data)) {
                $data['slug'] = $data['name'] ?? null;
            }
            
            if ($data['slug']) {
                $data['slug'] = $this->slugService->generateSlug($data['slug']);
            }
            
            // Extract the nested data before validation
            $assignees = $data['assignees'] ?? [];
            $timeSlots = $data['time_slots'] ?? [];
            $formFields = $data['form_fields'] ?? [];
            $bookingOptions = $data['booking_options'] ?? [];
            
            // Remove nested data from validation
            unset($data['assignees']);
            unset($data['time_slots']);
            unset($data['form_fields']);
            unset($data['booking_options']);
            
            $constraints = [
                'name' => [
                    new Assert\NotBlank(['message' => 'Event name is required.']),
                    new Assert\Type('string'),
                    new Assert\Length(['min' => 2, 'max' => 255]),
                ],
                'slug' => [
                    new Assert\NotBlank(['message' => 'Event slug is required.']),
                    new Assert\Type('string'),
                    new Assert\Length(['min' => 2, 'max' => 255]),
                    new Assert\Regex([
                        'pattern' => '/^[a-z0-9\-]+$/',
                        'message' => 'Slug can only contain lowercase letters, numbers, and hyphens.'
                    ]),
                ],
                'description' => new Assert\Optional([
                    new Assert\Type('string'),
                ]),
                'team_id' => new Assert\Optional([
                    new Assert\Type('numeric'),
                ]),
                'duration' => [
                    new Assert\Type('integer'),
                    new Assert\Range(['min' => 15, 'max' => 1440]) 
                ],
                'organization_id' => [
                    new Assert\Type('integer') 
                ],
            ];
            
            $transform = [
                'slug' => function(string $value) {
                    return $this->slugService->generateSlug($value);
                },
                'team_id' => function($value) {
                    if ($value) {
                        $team = $this->entityManager->getRepository(TeamEntity::class)->find($value);
                        if (!$team) {
                            throw new EventsException('Team not found.');
                        }
                        return $team;
                    }
                    return null;
                },
            ];
            
            $this->crudManager->create($event, $data, $constraints, $transform);
            
            // Process assignees if provided
            if (!empty($assignees) && is_array($assignees)) {
                foreach ($assignees as $assigneeId) {
                    $assigneeUser = $this->entityManager->getRepository(UserEntity::class)->find($assigneeId);
                    if ($assigneeUser) {
                        $assignee = new EventAssigneeEntity();
                        $assignee->setEvent($event);
                        $assignee->setUser($assigneeUser);
                        $assignee->setAssignedBy($event->getCreatedBy()); // Set the creator as the assignedBy
                        $this->entityManager->persist($assignee);
                    }
                }
                $this->entityManager->flush();
            }
            
            // Process time slots if provided
            if (!empty($timeSlots) && is_array($timeSlots)) {
                foreach ($timeSlots as $slotData) {
                    $this->addTimeSlot($event, $slotData);
                }
            }
            
            // Process form fields if provided
            if (!empty($formFields) && is_array($formFields)) {
                foreach ($formFields as $fieldData) {
                    $this->addFormField($event, $fieldData);
                }
            }
            
            // Process booking options if provided
            if (!empty($bookingOptions) && is_array($bookingOptions)) {
                foreach ($bookingOptions as $optionData) {
                    $this->addBookingOption($event, $optionData);
                }
            }
            
            return $event;
        } catch (CrudException $e) {
            throw new EventsException($e->getMessage());
        }
    }

    public function update(EventEntity $event, array $data): void
    {
        try {
            $assignees = $data['assignees'] ?? null;
            $timeSlots = $data['time_slots'] ?? null;
            $formFields = $data['form_fields'] ?? null;
            $bookingOptions = $data['booking_options'] ?? null;
            
            // Remove nested data from validation
            unset($data['assignees']);
            unset($data['time_slots']);
            unset($data['form_fields']);
            unset($data['booking_options']);
            unset($data['team']); // Remove 'team' if it exists
            
            // Handle slug updates
            if (!empty($data['slug']) || (!isset($data['slug']) && !empty($data['name']))) {
                if (empty($data['slug']) && !empty($data['name'])) {
                    $data['slug'] = $data['name'];
                }
                
                $data['slug'] = $this->slugService->generateSlug($data['slug']);
            }
            
            $constraints = [
                'name' => new Assert\Optional([
                    new Assert\NotBlank(['message' => 'Event name is required.']),
                    new Assert\Type('string'),
                    new Assert\Length(['min' => 2, 'max' => 255]),
                ]),
                'slug' => new Assert\Optional([
                    new Assert\NotBlank(['message' => 'Event slug is required.']),
                    new Assert\Type('string'),
                    new Assert\Length(['min' => 2, 'max' => 255]),
                    new Assert\Regex([
                        'pattern' => '/^[a-z0-9\-]+$/',
                        'message' => 'Slug can only contain lowercase letters, numbers, and hyphens.'
                    ]),
                ]),
                'description' => new Assert\Optional([
                    new Assert\Type('string'),
                ]),
                'team_id' => new Assert\Optional([
                    new Assert\Type('numeric'),
                ]),
                'duration' => new Assert\Optional([
                    new Assert\Type('integer'),
                    new Assert\Range(['min' => 15, 'max' => 1440]) 
                ]),
            ];
            
            $transform = [
                'slug' => function(string $value) {
                    return $this->slugService->generateSlug($value);
                },
                'team_id' => function($value) {
                    if ($value) {
                        $team = $this->entityManager->getRepository(TeamEntity::class)->find($value);
                        if (!$team) {
                            throw new EventsException('Team not found.');
                        }
                        return $team;
                    }
                    return null;
                },
            ];
            
            $this->crudManager->update($event, $data, $constraints, $transform);
            
            // Update assignees if provided
            if ($assignees !== null && is_array($assignees)) {
                // Get existing assignees
                $existingAssignees = $this->entityManager->getRepository(EventAssigneeEntity::class)
                    ->findBy(['event' => $event]);
                
                // Create a map of existing user IDs for quick lookup
                $existingUserIds = [];
                foreach ($existingAssignees as $existingAssignee) {
                    $existingUserIds[$existingAssignee->getUser()->getId()] = $existingAssignee;
                }
                
                // Determine which users to remove and which to add
                $userIdsToKeep = [];
                
                // Process new assignees
                foreach ($assignees as $assigneeId) {
                    $userIdsToKeep[] = $assigneeId;
                    
                    // Skip if already assigned
                    if (isset($existingUserIds[$assigneeId])) {
                        continue;
                    }
                    
                    // Add new assignee
                    $user = $this->entityManager->getRepository(UserEntity::class)->find($assigneeId);
                    if ($user) {
                        $assignee = new EventAssigneeEntity();
                        $assignee->setEvent($event);
                        $assignee->setUser($user);
                        $assignee->setAssignedBy($event->getCreatedBy());
                        $this->entityManager->persist($assignee);
                    }
                }
                
                // Remove assignees that aren't in the new list
                foreach ($existingAssignees as $existingAssignee) {
                    $userId = $existingAssignee->getUser()->getId();
                    if (!in_array($userId, $userIdsToKeep)) {
                        $this->entityManager->remove($existingAssignee);
                    }
                }
                
                $this->entityManager->flush();
            }
            
            // Update time slots if provided
            if ($timeSlots !== null && is_array($timeSlots)) {
                // Remove existing time slots
                $existingSlots = $this->entityManager->getRepository(EventScheduleEntity::class)
                    ->findBy(['event' => $event]);
                    
                foreach ($existingSlots as $existingSlot) {
                    $this->entityManager->remove($existingSlot);
                }
                $this->entityManager->flush();
                
                // Add new time slots
                foreach ($timeSlots as $slotData) {
                    $this->addTimeSlot($event, $slotData);
                }
            }
            
            // Update form fields if provided
            if ($formFields !== null && is_array($formFields)) {
                // Remove existing form fields
                $existingFields = $this->entityManager->getRepository(EventFormFieldEntity::class)
                    ->findBy(['event' => $event]);
                    
                foreach ($existingFields as $existingField) {
                    $this->entityManager->remove($existingField);
                }
                $this->entityManager->flush();
                
                // Add new form fields
                foreach ($formFields as $fieldData) {
                    $this->addFormField($event, $fieldData);
                }
            }
            
            // Update booking options if provided
            if ($bookingOptions !== null && is_array($bookingOptions)) {
                // Remove existing booking options
                $existingOptions = $this->entityManager->getRepository(EventBookingOptionEntity::class)
                    ->findBy(['event' => $event]);
                    
                foreach ($existingOptions as $existingOption) {
                    $this->entityManager->remove($existingOption);
                }
                $this->entityManager->flush();
                
                // Add new booking options
                foreach ($bookingOptions as $optionData) {
                    $this->addBookingOption($event, $optionData);
                }
            }
        } catch (CrudException $e) {
            throw new EventsException($e->getMessage());
        }
    }



    public function delete(EventEntity $event, bool $hard = false): void
    {
        try {
            $this->crudManager->delete($event, $hard);
        } catch (CrudException $e) {
            throw new EventsException($e->getMessage());
        }
    }
    
    public function getEventsByOrganization(OrganizationEntity $organization): array
    {
        try {
            return $this->getMany([], 1, 1000, ['organization' => $organization, 'deleted' => false]);
        } catch (CrudException $e) {
            throw new EventsException($e->getMessage());
        }
    }
    
    public function getEventsByTeam(TeamEntity $team): array
    {
        try {
            return $this->getMany([], 1, 1000, ['team' => $team, 'deleted' => false]);
        } catch (CrudException $e) {
            throw new EventsException($e->getMessage());
        }
    }
    
    public function getEventByIdAndOrganization(int $id, OrganizationEntity $organization): ?EventEntity
    {
        return $this->getOne($id, ['organization' => $organization]);
    }

    public function getEventByIdAndTeam(int $id, TeamEntity $team): ?EventEntity
    {
        return $this->getOne($id, ['team' => $team]);
    }
    
    public function getEventBySlug(string $slug, ?TeamEntity $team, OrganizationEntity $organization): ?EventEntity
    {
        $criteria = [
            'slug' => $slug,
            'deleted' => false
        ];
        
        if ($team) {
            $criteria['team'] = $team;
        } else {
            $criteria['organization'] = $organization;
            $criteria['team'] = null;
        }
        
        return $this->entityManager->getRepository(EventEntity::class)->findOneBy($criteria);
    }
    
    public function getAssignees(EventEntity $event): array
    {
        return $this->entityManager->getRepository(EventAssigneeEntity::class)
            ->findBy(['event' => $event]);
    }
    
    public function getTimeSlots(EventEntity $event): array
    {
        return $this->entityManager->getRepository(EventScheduleEntity::class)
            ->findBy(['event' => $event], ['startTime' => 'ASC']);
    }
    
    public function getFormFields(EventEntity $event): array
    {
        return $this->entityManager->getRepository(EventFormFieldEntity::class)
            ->findBy(['event' => $event], ['displayOrder' => 'ASC']);
    }
    
    public function getBookingOptions(EventEntity $event): array
    {
        return $this->entityManager->getRepository(EventBookingOptionEntity::class)
            ->findBy(['event' => $event, 'active' => true]);
    }
    
    private function addTimeSlot(EventEntity $event, array $data): EventScheduleEntity
    {
        try {
            if (empty($data['start_time']) || empty($data['end_time'])) {
                throw new EventsException('Time slot must have start and end times');
            }
            
            $startTime = $data['start_time'] instanceof \DateTimeInterface 
                ? $data['start_time'] 
                : new \DateTime($data['start_time']);
                
            $endTime = $data['end_time'] instanceof \DateTimeInterface 
                ? $data['end_time'] 
                : new \DateTime($data['end_time']);
            
            if ($startTime >= $endTime) {
                throw new EventsException('End time must be after start time');
            }
            
            $timeSlot = new EventScheduleEntity();
            $timeSlot->setEvent($event);
            $timeSlot->setStartTime($startTime);
            $timeSlot->setEndTime($endTime);
            $timeSlot->setIsBreak(!empty($data['is_break']) ? (bool)$data['is_break'] : false);
            
            $this->entityManager->persist($timeSlot);
            $this->entityManager->flush();
            
            return $timeSlot;
        } catch (\Exception $e) {
            throw new EventsException($e->getMessage());
        }
    }
    
    private function addFormField(EventEntity $event, array $data): EventFormFieldEntity
    {
        try {
            if (empty($data['field_name']) || empty($data['field_type'])) {
                throw new EventsException('Form field must have a name and type');
            }
            
            $formField = new EventFormFieldEntity();
            $formField->setEvent($event);
            $formField->setFieldName($data['field_name']);
            $formField->setFieldType($data['field_type']);
            $formField->setRequired(!empty($data['required']) ? (bool)$data['required'] : false);
            $formField->setDisplayOrder(!empty($data['display_order']) ? (int)$data['display_order'] : 0);
            
            if (!empty($data['options']) && is_array($data['options'])) {
                $formField->setOptionsFromArray($data['options']);
            } elseif (!empty($data['options']) && is_string($data['options'])) {
                $formField->setOptions($data['options']);
            }
            
            $this->entityManager->persist($formField);
            $this->entityManager->flush();
            
            return $formField;
        } catch (\Exception $e) {
            throw new EventsException($e->getMessage());
        }
    }
    
    private function addBookingOption(EventEntity $event, array $data): EventBookingOptionEntity
    {
        try {
            if (empty($data['name']) || empty($data['duration_minutes'])) {
                throw new EventsException('Booking option must have a name and duration');
            }
            
            $bookingOption = new EventBookingOptionEntity();
            $bookingOption->setEvent($event);
            $bookingOption->setName($data['name']);
            $bookingOption->setDurationMinutes((int)$data['duration_minutes']);
            
            if (isset($data['description'])) {
                $bookingOption->setDescription($data['description']);
            }
            
            if (isset($data['active'])) {
                $bookingOption->setActive((bool)$data['active']);
            }
            
            $this->entityManager->persist($bookingOption);
            $this->entityManager->flush();
            
            return $bookingOption;
        } catch (\Exception $e) {
            throw new EventsException($e->getMessage());
        }
    }
}