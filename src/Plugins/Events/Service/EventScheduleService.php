<?php

namespace App\Plugins\Events\Service;

use Doctrine\ORM\EntityManagerInterface;
use App\Plugins\Events\Entity\EventEntity;
use App\Plugins\Events\Exception\EventsException;
use DateTimeInterface;
use DateTime;

class EventScheduleService
{
    private EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
    }

    /**
     * Get schedules for an event
     */
    public function getScheduleForEvent(EventEntity $event): array
    {
        $schedule = $event->getSchedule();
        
        // Return default schedule if none exists
        if (empty($schedule)) {
            return [
                'monday' => ['enabled' => true, 'start_time' => '09:00:00', 'end_time' => '17:00:00', 'breaks' => []],
                'tuesday' => ['enabled' => true, 'start_time' => '09:00:00', 'end_time' => '17:00:00', 'breaks' => []],
                'wednesday' => ['enabled' => true, 'start_time' => '09:00:00', 'end_time' => '17:00:00', 'breaks' => []],
                'thursday' => ['enabled' => true, 'start_time' => '09:00:00', 'end_time' => '17:00:00', 'breaks' => []],
                'friday' => ['enabled' => true, 'start_time' => '09:00:00', 'end_time' => '17:00:00', 'breaks' => []],
                'saturday' => ['enabled' => false, 'start_time' => '09:00:00', 'end_time' => '17:00:00', 'breaks' => []],
                'sunday' => ['enabled' => false, 'start_time' => '09:00:00', 'end_time' => '17:00:00', 'breaks' => []]
            ];
        }
        
        return $schedule;
    }

    /**
     * Update schedule for an event
     * 
     * @param EventEntity $event
     * @param array $scheduleData Format: [
     *   'monday' => ['enabled' => true, 'start_time' => '09:00:00', 'end_time' => '17:00:00', 
     *               'breaks' => [['start_time' => '12:00:00', 'end_time' => '13:00:00']]],
     *   'tuesday' => ...
     * ]
     */
    public function updateEventSchedule(EventEntity $event, array $scheduleData): array
    {
        try {
            // Validate schedule data
            $validatedSchedule = $this->validateAndSanitizeSchedule($scheduleData);
            
            // Update the event with new schedule
            $event->setSchedule($validatedSchedule);
            
            $this->entityManager->persist($event);
            $this->entityManager->flush();
            
            return $validatedSchedule;
        } catch (\Exception $e) {
            throw new EventsException('Failed to update event schedule: ' . $e->getMessage());
        }
    }
    
    /**
     * Validate and sanitize schedule data
     */
    private function validateAndSanitizeSchedule(array $scheduleData): array
    {
        $validDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $validatedSchedule = [];
        
        // Create default schedule structure for all days first
        foreach ($validDays as $day) {
            $validatedSchedule[$day] = [
                'enabled' => $day !== 'saturday' && $day !== 'sunday', // Default: Mon-Fri enabled
                'start_time' => '09:00:00',
                'end_time' => '17:00:00',
                'breaks' => []
            ];
        }
        
        // Override with provided data
        foreach ($scheduleData as $day => $dayData) {
            $day = strtolower($day);
            
            // Skip invalid days
            if (!in_array($day, $validDays)) {
                continue;
            }
            
            // Validate required fields in day data
            if (!is_array($dayData)) {
                continue;
            }
            
            // Set enabled status
            if (isset($dayData['enabled'])) {
                $validatedSchedule[$day]['enabled'] = (bool)$dayData['enabled'];
            }
            
            // Validate and set start_time
            if (!empty($dayData['start_time'])) {
                try {
                    // Validate time format
                    $startTime = new DateTime($dayData['start_time']);
                    $validatedSchedule[$day]['start_time'] = $startTime->format('H:i:s');
                } catch (\Exception $e) {
                    // Use default if invalid
                }
            }
            
            // Validate and set end_time
            if (!empty($dayData['end_time'])) {
                try {
                    // Validate time format
                    $endTime = new DateTime($dayData['end_time']);
                    $validatedSchedule[$day]['end_time'] = $endTime->format('H:i:s');
                } catch (\Exception $e) {
                    // Use default if invalid
                }
            }
            
            // Make sure end time is after start time
            $startTime = new DateTime($validatedSchedule[$day]['start_time']);
            $endTime = new DateTime($validatedSchedule[$day]['end_time']);
            
            if ($startTime >= $endTime) {
                // If times are invalid, reset to default
                $validatedSchedule[$day]['start_time'] = '09:00:00';
                $validatedSchedule[$day]['end_time'] = '17:00:00';
            }
            
            // Process breaks
            if (!empty($dayData['breaks']) && is_array($dayData['breaks'])) {
                $validatedBreaks = [];
                
                foreach ($dayData['breaks'] as $breakData) {
                    if (!is_array($breakData)) {
                        continue;
                    }
                    
                    $validBreak = [];
                    
                    // Validate break start time
                    if (!empty($breakData['start_time'])) {
                        try {
                            $breakStartTime = new DateTime($breakData['start_time']);
                            $validBreak['start_time'] = $breakStartTime->format('H:i:s');
                        } catch (\Exception $e) {
                            continue; // Skip invalid break
                        }
                    } else {
                        continue; // Skip if no start time
                    }
                    
                    // Validate break end time
                    if (!empty($breakData['end_time'])) {
                        try {
                            $breakEndTime = new DateTime($breakData['end_time']);
                            $validBreak['end_time'] = $breakEndTime->format('H:i:s');
                        } catch (\Exception $e) {
                            continue; // Skip invalid break
                        }
                    } else {
                        continue; // Skip if no end time
                    }
                    
                    // Make sure break end time is after break start time
                    $breakStartTime = new DateTime($validBreak['start_time']);
                    $breakEndTime = new DateTime($validBreak['end_time']);
                    
                    if ($breakStartTime >= $breakEndTime) {
                        continue; // Skip invalid break
                    }
                    
                    // Make sure break is within the day's time range
                    $dayStartTime = new DateTime($validatedSchedule[$day]['start_time']);
                    $dayEndTime = new DateTime($validatedSchedule[$day]['end_time']);
                    
                    if ($breakStartTime < $dayStartTime || $breakEndTime > $dayEndTime) {
                        continue; // Skip break outside day's range
                    }
                    
                    $validatedBreaks[] = $validBreak;
                }
                
                $validatedSchedule[$day]['breaks'] = $validatedBreaks;
            }
        }
        
        return $validatedSchedule;
    }
/**
     * Check if a specific time slot is available based on the schedule
     */
    public function isTimeSlotAvailable(EventEntity $event, DateTimeInterface $startDateTime, DateTimeInterface $endDateTime): bool
    {
        // Get the day of the week
        $dayOfWeek = strtolower($startDateTime->format('l'));
        
        // Get the event schedule
        $schedule = $this->getScheduleForEvent($event);
        
        // Check if this day is enabled
        if (!isset($schedule[$dayOfWeek]) || !$schedule[$dayOfWeek]['enabled']) {
            return false; // Day not available
        }
        
        // Extract just the time component for comparison
        $startTime = $startDateTime->format('H:i:s');
        $endTime = $endDateTime->format('H:i:s');
        
        // Check if within working hours
        $scheduleStartTime = $schedule[$dayOfWeek]['start_time'];
        $scheduleEndTime = $schedule[$dayOfWeek]['end_time'];
        
        if ($startTime < $scheduleStartTime || $endTime > $scheduleEndTime) {
            return false; // Not within working hours
        }
        
        // Check for conflicts with breaks
        foreach ($schedule[$dayOfWeek]['breaks'] as $break) {
            $breakStartTime = $break['start_time'];
            $breakEndTime = $break['end_time'];
            
            // Check for overlap
            if (
                ($startTime < $breakEndTime && $endTime > $breakStartTime) ||
                ($startTime <= $breakStartTime && $endTime >= $breakEndTime)
            ) {
                return false; // Overlaps with a break
            }
        }
        
        return true; // Available
    }
    
    
    /**
     * Get available time slots for a day based on the schedule
     */
    public function getAvailableTimeSlots(EventEntity $event, DateTimeInterface $date, int $durationMinutes = 30): array
    {
        // Get the day of the week
        $dayOfWeek = strtolower($date->format('l'));
        
        // Get the event schedule
        $schedule = $this->getScheduleForEvent($event);
        
        // Check if this day is enabled
        if (!isset($schedule[$dayOfWeek]) || !$schedule[$dayOfWeek]['enabled']) {
            return []; // No schedule for this day
        }
        
        // Extract schedule times
        $scheduleStartTime = clone $date;
        list($hours, $minutes, $seconds) = explode(':', $schedule[$dayOfWeek]['start_time']);
        $scheduleStartTime->setTime((int)$hours, (int)$minutes, (int)$seconds);
        
        $scheduleEndTime = clone $date;
        list($hours, $minutes, $seconds) = explode(':', $schedule[$dayOfWeek]['end_time']);
        $scheduleEndTime->setTime((int)$hours, (int)$minutes, (int)$seconds);
        
        // Convert breaks to DateTime objects
        $breaks = [];
        foreach ($schedule[$dayOfWeek]['breaks'] as $break) {
            $breakStart = clone $date;
            list($hours, $minutes, $seconds) = explode(':', $break['start_time']);
            $breakStart->setTime((int)$hours, (int)$minutes, (int)$seconds);
            
            $breakEnd = clone $date;
            list($hours, $minutes, $seconds) = explode(':', $break['end_time']);
            $breakEnd->setTime((int)$hours, (int)$minutes, (int)$seconds);
            
            $breaks[] = [
                'start' => $breakStart,
                'end' => $breakEnd
            ];
        }
        
        // Generate time slots
        $timeSlots = [];
        $slotStart = clone $scheduleStartTime;
        $slotDuration = new \DateInterval('PT' . $durationMinutes . 'M');
        
        while ($slotStart < $scheduleEndTime) {
            $slotEnd = clone $slotStart;
            $slotEnd->add($slotDuration);
            
            // If slot end is after schedule end, break the loop
            if ($slotEnd > $scheduleEndTime) {
                break;
            }
            
            // Check for conflicts with breaks
            $hasConflict = false;
            foreach ($breaks as $break) {
                if (
                    ($slotStart < $break['end'] && $slotEnd > $break['start']) ||
                    ($slotStart <= $break['start'] && $slotEnd >= $break['end'])
                ) {
                    $hasConflict = true;
                    break;
                }
            }
            
            if (!$hasConflict) {
                $timeSlots[] = [
                    'start' => $slotStart->format('Y-m-d H:i:s'),
                    'end' => $slotEnd->format('Y-m-d H:i:s')
                ];
            }
            
            // Move to next slot
            $slotStart->add($slotDuration);
        }
        
        return $timeSlots;
    }
    
    /**
     * Get available dates within a range based on the event schedule
     */
    public function getAvailableDates(EventEntity $event, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        // Get the event schedule
        $schedule = $this->getScheduleForEvent($event);
        
        // Create a map of enabled days
        $enabledDays = [];
        foreach ($schedule as $day => $dayData) {
            if ($dayData['enabled']) {
                $enabledDays[$day] = true;
            }
        }
        
        // Generate list of available dates
        $availableDates = [];
        $currentDate = clone $startDate;
        
        while ($currentDate <= $endDate) {
            $dayOfWeek = strtolower($currentDate->format('l'));
            
            if (isset($enabledDays[$dayOfWeek])) {
                $availableDates[] = $currentDate->format('Y-m-d');
            }
            
            $currentDate->modify('+1 day');
        }
        
        return $availableDates;
    }
}