<?php

namespace App\Plugins\Events\Service;

use App\Service\CrudManager;
use App\Exception\CrudException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraints as Assert;

use App\Plugins\Events\Entity\EventEntity;
use App\Plugins\Events\Entity\EventTimeSlotEntity;
use App\Plugins\Events\Entity\EventFormFieldEntity;
use App\Plugins\Events\Entity\EventBookingOptionEntity;
use App\Plugins\Events\Entity\EventAssigneeEntity;
use App\Plugins\Events\Exception\EventsException;

use App\Plugins\Organizations\Entity\OrganizationEntity;
use App\Plugins\Teams\Entity\TeamEntity;
use App\Plugins\Account\Entity\UserEntity;

class EventService
{
    private CrudManager $crudManager;
    private EntityManagerInterface $entityManager;

    public function __construct(
        CrudManager $crudManager,
        EntityManagerInterface $entityManager
    ) {
        $this->crudManager = $crudManager;
        $this->entityManager = $entityManager;
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
            
            $constraints = [
                'name' => [
                    new Assert\NotBlank(['message' => 'Event name is required.']),
                    new Assert\Type('string'),
                    new Assert\Length(['min' => 2, 'max' => 255]),
                ],
                'description' => new Assert\Optional([
                    new Assert\Type('string'),
                ]),
                'team_id' => new Assert\Optional([
                    new Assert\Type('numeric'),
                ]),
            ];
            
            $this->crudManager->create($event, $data, $constraints);
            
            // Process assignees if provided
            if (!empty($data['assignees']) && is_array($data['assignees'])) {
                foreach ($data['assignees'] as $assigneeId) {
                    $user = $this->entityManager->getRepository(UserEntity::class)->find($assigneeId);
                    if ($user) {
                        $assignee = new EventAssigneeEntity();
                        $assignee->setEvent($event);
                        $assignee->setUser($user);
                        $this->entityManager->persist($assignee);
                    }
                }
                $this->entityManager->flush();
            }
            
            // Process time slots if provided
            if (!empty($data['time_slots']) && is_array($data['time_slots'])) {
                foreach ($data['time_slots'] as $slotData) {
                    $this->addTimeSlot($event, $slotData);
                }
            }
            
            // Process form fields if provided
            if (!empty($data['form_fields']) && is_array($data['form_fields'])) {
                foreach ($data['form_fields'] as $fieldData) {
                    $this->addFormField($event, $fieldData);
                }
            }
            
            // Process booking options if provided
            if (!empty($data['booking_options']) && is_array($data['booking_options'])) {
                foreach ($data['booking_options'] as $optionData) {
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
            $constraints = [
                'name' => new Assert\Optional([
                    new Assert\NotBlank(['message' => 'Event name is required.']),
                    new Assert\Type('string'),
                    new Assert\Length(['min' => 2, 'max' => 255]),
                ]),
                'description' => new Assert\Optional([
                    new Assert\Type('string'),
                ]),
                'team_id' => new Assert\Optional([
                    new Assert\Type('numeric'),
                ]),
            ];
            
            $transform = [
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
            if (isset($data['assignees']) && is_array($data['assignees'])) {
                // Remove existing assignees
                $existingAssignees = $this->entityManager->getRepository(EventAssigneeEntity::class)
                    ->findBy(['event' => $event]);
                    
                foreach ($existingAssignees as $existingAssignee) {
                    $this->entityManager->remove($existingAssignee);
                }
                
                // Add new assignees
                foreach ($data['assignees'] as $assigneeId) {
                    $user = $this->entityManager->getRepository(UserEntity::class)->find($assigneeId);
                    if ($user) {
                        $assignee = new EventAssigneeEntity();
                        $assignee->setEvent($event);
                        $assignee->setUser($user);
                        $this->entityManager->persist($assignee);
                    }
                }
                $this->entityManager->flush();
            }
            
            // Update time slots if provided
            if (isset($data['time_slots']) && is_array($data['time_slots'])) {
                // Remove existing time slots
                $existingSlots = $this->entityManager->getRepository(EventTimeSlotEntity::class)
                    ->findBy(['event' => $event]);
                    
                foreach ($existingSlots as $existingSlot) {
                    $this->entityManager->remove($existingSlot);
                }
                $this->entityManager->flush();
                
                // Add new time slots
                foreach ($data['time_slots'] as $slotData) {
                    $this->addTimeSlot($event, $slotData);
                }
            }
            
            // Update form fields if provided
            if (isset($data['form_fields']) && is_array($data['form_fields'])) {
                // Remove existing form fields
                $existingFields = $this->entityManager->getRepository(EventFormFieldEntity::class)
                    ->findBy(['event' => $event]);
                    
                foreach ($existingFields as $existingField) {
                    $this->entityManager->remove($existingField);
                }
                $this->entityManager->flush();
                
                // Add new form fields
                foreach ($data['form_fields'] as $fieldData) {
                    $this->addFormField($event, $fieldData);
                }
            }
            
            // Update booking options if provided
            if (isset($data['booking_options']) && is_array($data['booking_options'])) {
                // Remove existing booking options
                $existingOptions = $this->entityManager->getRepository(EventBookingOptionEntity::class)
                    ->findBy(['event' => $event]);
                    
                foreach ($existingOptions as $existingOption) {
                    $this->entityManager->remove($existingOption);
                }
                $this->entityManager->flush();
                
                // Add new booking options
                foreach ($data['booking_options'] as $optionData) {
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
    
    public function getAssignees(EventEntity $event): array
    {
        return $this->entityManager->getRepository(EventAssigneeEntity::class)
            ->findBy(['event' => $event]);
    }
    
    public function getTimeSlots(EventEntity $event): array
    {
        return $this->entityManager->getRepository(EventTimeSlotEntity::class)
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
    
    private function addTimeSlot(EventEntity $event, array $data): EventTimeSlotEntity
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
            
            $timeSlot = new EventTimeSlotEntity();
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