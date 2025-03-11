<?php

namespace App\Plugins\Events\Service;

use App\Service\CrudManager;
use Doctrine\ORM\EntityManagerInterface;
use App\Plugins\Events\Entity\EventEntity;
use App\Plugins\Events\Entity\EventScheduleEntity;
use App\Plugins\Events\Repository\EventScheduleRepository;
use App\Plugins\Events\Exception\EventsException;

class EventScheduleService
{
    private CrudManager $crudManager;
    private EntityManagerInterface $entityManager;
    private EventScheduleRepository $scheduleRepository;

    public function __construct(
        CrudManager $crudManager,
        EntityManagerInterface $entityManager,
        EventScheduleRepository $scheduleRepository
    ) {
        $this->crudManager = $crudManager;
        $this->entityManager = $entityManager;
        $this->scheduleRepository = $scheduleRepository;
    }

    public function getScheduleForEvent(EventEntity $event): ?EventScheduleEntity
    {
        return $this->scheduleRepository->findOneBy(['event' => $event]);
    }

    public function createOrUpdateSchedule(EventEntity $event, array $scheduleData): EventScheduleEntity
    {
        try {
            $existingSchedule = $this->getScheduleForEvent($event);
            
            if ($existingSchedule) {
                // Update existing schedule
                $existingSchedule->setSchedule($scheduleData);
                $this->entityManager->persist($existingSchedule);
                $this->entityManager->flush();
                
                return $existingSchedule;
            } else {
                // Create new schedule
                $schedule = new EventScheduleEntity();
                $schedule->setEvent($event);
                $schedule->setSchedule($scheduleData);
                
                $this->entityManager->persist($schedule);
                $this->entityManager->flush();
                
                return $schedule;
            }
        } catch (\Exception $e) {
            throw new EventsException('Failed to create or update schedule: ' . $e->getMessage());
        }
    }

    /**
     * Check if a specific time slot is available based on the schedule
     */
    public function isTimeSlotAvailable(EventEntity $event, \DateTimeInterface $startTime, \DateTimeInterface $endTime): bool
    {
        $schedule = $this->getScheduleForEvent($event);
        if (!$schedule) {
            return false; // No schedule means not available
        }
        
        $scheduleData = $schedule->getSchedule();
        $dayOfWeek = strtolower($startTime->format('l'));
        
        // Check if the day is enabled in the schedule
        if (!isset($scheduleData[$dayOfWeek]) || !$scheduleData[$dayOfWeek]['enabled']) {
            return false;
        }
        
        // Get the day's schedule
        $daySchedule = $scheduleData[$dayOfWeek];
        
        // Check if the time is within the day's working hours
        $workingStartTime = \DateTime::createFromFormat('H:i', $daySchedule['startTime']);
        $workingEndTime = \DateTime::createFromFormat('H:i', $daySchedule['endTime']);
        
        $requestStartTime = clone $startTime;
        $requestStartTime->setDate(1970, 1, 1);
        
        $requestEndTime = clone $endTime;
        $requestEndTime->setDate(1970, 1, 1);
        
        if ($requestStartTime < $workingStartTime || $requestEndTime > $workingEndTime) {
            return false;
        }
        
        // Check if the time overlaps with any pauses
        foreach ($daySchedule['pauses'] as $pause) {
            $pauseStartTime = \DateTime::createFromFormat('H:i', $pause['startTime']);
            $pauseEndTime = \DateTime::createFromFormat('H:i', $pause['endTime']);
            
            // Check for overlap
            if (
                ($requestStartTime >= $pauseStartTime && $requestStartTime < $pauseEndTime) ||
                ($requestEndTime > $pauseStartTime && $requestEndTime <= $pauseEndTime) ||
                ($requestStartTime <= $pauseStartTime && $requestEndTime >= $pauseEndTime)
            ) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Get available time slots for a day based on the schedule
     */
    public function getAvailableTimeSlots(EventEntity $event, \DateTimeInterface $date, int $durationMinutes = 30): array
    {
        $schedule = $this->getScheduleForEvent($event);
        if (!$schedule) {
            return [];
        }
        
        $scheduleData = $schedule->getSchedule();
        $dayOfWeek = strtolower($date->format('l'));
        
        // Check if the day is enabled in the schedule
        if (!isset($scheduleData[$dayOfWeek]) || !$scheduleData[$dayOfWeek]['enabled']) {
            return [];
        }
        
        // Get the day's schedule
        $daySchedule = $scheduleData[$dayOfWeek];
        
        $workingStartTime = \DateTime::createFromFormat('H:i', $daySchedule['startTime']);
        $workingEndTime = \DateTime::createFromFormat('H:i', $daySchedule['endTime']);
        
        if (!$workingStartTime || !$workingEndTime) {
            return [];
        }
        
        // Set the date part to the requested date
        $startDateTime = clone $date;
        $startDateTime->setTime(
            (int)$workingStartTime->format('H'),
            (int)$workingStartTime->format('i')
        );
        
        $endDateTime = clone $date;
        $endDateTime->setTime(
            (int)$workingEndTime->format('H'),
            (int)$workingEndTime->format('i')
        );
        
        // Generate time slots in intervals
        $timeSlots = [];
        $slotDuration = new \DateInterval('PT' . $durationMinutes . 'M');
        $currentSlot = clone $startDateTime;
        
        while ($currentSlot->add($slotDuration) <= $endDateTime) {
            $slotStart = clone $currentSlot;
            $slotStart->sub($slotDuration); // Go back to start of slot
            $slotEnd = clone $currentSlot;
            
            // Check if slot overlaps with any pauses
            $overlapsWithPause = false;
            foreach ($daySchedule['pauses'] as $pause) {
                $pauseStart = \DateTime::createFromFormat('H:i', $pause['startTime']);
                $pauseEnd = \DateTime::createFromFormat('H:i', $pause['endTime']);
                
                if (!$pauseStart || !$pauseEnd) {
                    continue;
                }
                
                // Set date part to match the requested date
                $pauseStartDateTime = clone $date;
                $pauseStartDateTime->setTime(
                    (int)$pauseStart->format('H'),
                    (int)$pauseStart->format('i')
                );
                
                $pauseEndDateTime = clone $date;
                $pauseEndDateTime->setTime(
                    (int)$pauseEnd->format('H'),
                    (int)$pauseEnd->format('i')
                );
                
                // Check for overlap
                if (
                    ($slotStart >= $pauseStartDateTime && $slotStart < $pauseEndDateTime) ||
                    ($slotEnd > $pauseStartDateTime && $slotEnd <= $pauseEndDateTime) ||
                    ($slotStart <= $pauseStartDateTime && $slotEnd >= $pauseEndDateTime)
                ) {
                    $overlapsWithPause = true;
                    break;
                }
            }
            
            if (!$overlapsWithPause) {
                $timeSlots[] = [
                    'start' => $slotStart->format('Y-m-d H:i:s'),
                    'end' => $slotEnd->format('Y-m-d H:i:s')
                ];
            }
        }
        
        return $timeSlots;
    }

    public function deleteSchedule(EventScheduleEntity $schedule): void
    {
        try {
            $this->entityManager->remove($schedule);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            throw new EventsException('Failed to delete schedule: ' . $e->getMessage());
        }
    }
}