<?php

namespace App\Plugins\Events\Service;

use App\Service\CrudManager;
use App\Exception\CrudException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraints as Assert;

use App\Plugins\Events\Entity\EventEntity;
use App\Plugins\Events\Entity\EventTimeSlotEntity;
use App\Plugins\Events\Repository\EventTimeSlotRepository;
use App\Plugins\Events\Exception\EventsException;

class EventTimeSlotService
{
    private CrudManager $crudManager;
    private EntityManagerInterface $entityManager;
    private EventTimeSlotRepository $timeSlotRepository;

    public function __construct(
        CrudManager $crudManager,
        EntityManagerInterface $entityManager,
        EventTimeSlotRepository $timeSlotRepository
    ) {
        $this->crudManager = $crudManager;
        $this->entityManager = $entityManager;
        $this->timeSlotRepository = $timeSlotRepository;
    }

    public function getMany(array $filters, int $page, int $limit, array $criteria = []): array
    {
        try {
            return $this->crudManager->findMany(
                EventTimeSlotEntity::class,
                $filters,
                $page,
                $limit,
                $criteria
            );
        } catch (CrudException $e) {
            throw new EventsException($e->getMessage());
        }
    }

    public function getOne(int $id, array $criteria = []): ?EventTimeSlotEntity
    {
        return $this->crudManager->findOne(EventTimeSlotEntity::class, $id, $criteria);
    }

    public function create(array $data): EventTimeSlotEntity
    {
        try {
            if (empty($data['event_id'])) {
                throw new EventsException('Event ID is required');
            }
            
            $event = $this->entityManager->getRepository(EventEntity::class)->find($data['event_id']);
            if (!$event) {
                throw new EventsException('Event not found');
            }
            
            if (empty($data['start_time']) || empty($data['end_time'])) {
                throw new EventsException('Start and end times are required');
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

    public function update(EventTimeSlotEntity $timeSlot, array $data): void
    {
        try {
            if (!empty($data['start_time'])) {
                $startTime = $data['start_time'] instanceof \DateTimeInterface 
                    ? $data['start_time'] 
                    : new \DateTime($data['start_time']);
                    
                $timeSlot->setStartTime($startTime);
            }
            
            if (!empty($data['end_time'])) {
                $endTime = $data['end_time'] instanceof \DateTimeInterface 
                    ? $data['end_time'] 
                    : new \DateTime($data['end_time']);
                    
                $timeSlot->setEndTime($endTime);
            }
            
            if ($timeSlot->getStartTime() >= $timeSlot->getEndTime()) {
                throw new EventsException('End time must be after start time');
            }
            
            if (isset($data['is_break'])) {
                $timeSlot->setIsBreak((bool)$data['is_break']);
            }
            
            $this->entityManager->persist($timeSlot);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            throw new EventsException($e->getMessage());
        }
    }

    public function delete(EventTimeSlotEntity $timeSlot): void
    {
        try {
            $this->entityManager->remove($timeSlot);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            throw new EventsException($e->getMessage());
        }
    }
    
    public function getTimeSlotsByEvent(EventEntity $event): array
    {
        return $this->timeSlotRepository->findBy(
            ['event' => $event],
            ['startTime' => 'ASC']
        );
    }
    
    public function getTimeSlotsByEventAndDateRange(EventEntity $event, \DateTime $startDate, \DateTime $endDate): array
    {
        return $this->timeSlotRepository->findByEventAndDateRange(
            $event->getId(),
            $startDate,
            $endDate
        );
    }
    
    public function getAvailableTimeSlots(EventEntity $event, \DateTime $startDate, \DateTime $endDate): array
    {
        // Get all time slots in the range
        $timeSlots = $this->getTimeSlotsByEventAndDateRange($event, $startDate, $endDate);
        
        // Filter out break slots
        return array_filter($timeSlots, function($timeSlot) {
            return !$timeSlot->isBreak();
        });
    }
}