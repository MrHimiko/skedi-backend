<?php

namespace App\Plugins\Events\Service;

use App\Service\CrudManager;
use App\Exception\CrudException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraints as Assert;

use App\Plugins\Events\Entity\EventEntity;
use App\Plugins\Events\Entity\EventAssigneeEntity;
use App\Plugins\Events\Repository\EventAssigneeRepository;
use App\Plugins\Events\Exception\EventsException;
use App\Plugins\Account\Entity\UserEntity;

class EventAssigneeService
{
    private CrudManager $crudManager;
    private EntityManagerInterface $entityManager;
    private EventAssigneeRepository $assigneeRepository;

    public function __construct(
        CrudManager $crudManager,
        EntityManagerInterface $entityManager,
        EventAssigneeRepository $assigneeRepository
    ) {
        $this->crudManager = $crudManager;
        $this->entityManager = $entityManager;
        $this->assigneeRepository = $assigneeRepository;
    }

    public function getMany(array $filters, int $page, int $limit, array $criteria = []): array
    {
        try {
            return $this->crudManager->findMany(
                EventAssigneeEntity::class,
                $filters,
                $page,
                $limit,
                $criteria
            );
        } catch (CrudException $e) {
            throw new EventsException($e->getMessage());
        }
    }

    public function getOne(int $id, array $criteria = []): ?EventAssigneeEntity
    {
        return $this->crudManager->findOne(EventAssigneeEntity::class, $id, $criteria);
    }

    public function create(array $data): EventAssigneeEntity
    {
        try {
            if (empty($data['event_id'])) {
                throw new EventsException('Event ID is required');
            }
            
            $event = $this->entityManager->getRepository(EventEntity::class)->find($data['event_id']);
            if (!$event) {
                throw new EventsException('Event not found');
            }
            
            if (empty($data['user_id'])) {
                throw new EventsException('User ID is required');
            }
            
            $user = $this->entityManager->getRepository(UserEntity::class)->find($data['user_id']);
            if (!$user) {
                throw new EventsException('User not found');
            }
            
            // Check if user is already an assignee for this event
            $existingAssignee = $this->assigneeRepository->findOneBy([
                'event' => $event,
                'user' => $user
            ]);
            
            if ($existingAssignee) {
                throw new EventsException('User is already assigned to this event');
            }
            
            $assignee = new EventAssigneeEntity();
            $assignee->setEvent($event);
            $assignee->setUser($user);
            
            $this->entityManager->persist($assignee);
            $this->entityManager->flush();
            
            return $assignee;
        } catch (\Exception $e) {
            throw new EventsException($e->getMessage());
        }
    }

    public function delete(EventAssigneeEntity $assignee): void
    {
        try {
            $this->entityManager->remove($assignee);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            throw new EventsException($e->getMessage());
        }
    }
    
    public function getAssigneesByEvent(EventEntity $event): array
    {
        return $this->assigneeRepository->findBy(['event' => $event]);
    }
    
    public function getEventsByAssignee(UserEntity $user): array
    {
        $assignees = $this->assigneeRepository->findEventsByAssignee($user->getId());
        
        // Extract events from assignees
        $events = [];
        foreach ($assignees as $assignee) {
            $events[] = $assignee->getEvent();
        }
        
        return $events;
    }
    
    public function isUserAssignedToEvent(UserEntity $user, EventEntity $event): bool
    {
        $assignee = $this->assigneeRepository->findOneBy([
            'event' => $event,
            'user' => $user
        ]);
        
        return $assignee !== null;
    }
    
    public function addMultipleAssignees(EventEntity $event, array $userIds): void
    {
        try {
            foreach ($userIds as $userId) {
                $user = $this->entityManager->getRepository(UserEntity::class)->find($userId);
                if (!$user) {
                    continue; // Skip invalid users
                }
                
                // Check if user is already an assignee
                $existingAssignee = $this->assigneeRepository->findOneBy([
                    'event' => $event,
                    'user' => $user
                ]);
                
                if (!$existingAssignee) {
                    $assignee = new EventAssigneeEntity();
                    $assignee->setEvent($event);
                    $assignee->setUser($user);
                    
                    $this->entityManager->persist($assignee);
                }
            }
            
            $this->entityManager->flush();
        } catch (\Exception $e) {
            throw new EventsException($e->getMessage());
        }
    }
    
    public function removeMultipleAssignees(EventEntity $event, array $userIds): void
    {
        try {
            foreach ($userIds as $userId) {
                $assignee = $this->assigneeRepository->findOneBy([
                    'event' => $event,
                    'user' => $userId
                ]);
                
                if ($assignee) {
                    $this->entityManager->remove($assignee);
                }
            }
            
            $this->entityManager->flush();
        } catch (\Exception $e) {
            throw new EventsException($e->getMessage());
        }
    }
}