<?php

namespace App\Plugins\Events\Service;

use Doctrine\ORM\EntityManagerInterface;
use App\Plugins\Events\Entity\EventEntity;
use App\Plugins\Events\Entity\EventBookingEntity;
use App\Plugins\Events\Exception\EventsException;
use App\Service\CrudManager;
use App\Exception\CrudException;
use DateTimeInterface;
use DateTime;

class EventScheduleService
{
    private EntityManagerInterface $entityManager;
    private CrudManager $crudManager;

    public function __construct(
        EntityManagerInterface $entityManager,
        CrudManager $crudManager
    ) {
        $this->entityManager = $entityManager;
        $this->crudManager = $crudManager;
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
     * Check if a specific time slot is available based on the schedule (timezone-aware)
     */
    public function isTimeSlotAvailable(EventEntity $event, DateTimeInterface $startDateTime, DateTimeInterface $endDateTime, ?string $clientTimezone = null): bool
    {
        // Ensure the dates are in UTC for internal processing
        $startUtc = clone $startDateTime;
        $endUtc = clone $endDateTime;
        
        if ($startDateTime->getTimezone()->getName() !== 'UTC') {
            $startUtc->setTimezone(new \DateTimeZone('UTC'));
        }
        
        if ($endDateTime->getTimezone()->getName() !== 'UTC') {
            $endUtc->setTimezone(new \DateTimeZone('UTC'));
        }
        
        // Get the day of the week
        $dayOfWeek = strtolower($startUtc->format('l'));
        
        // Get the event schedule
        $schedule = $this->getScheduleForEvent($event);
        
        // Check if this day is enabled
        if (!isset($schedule[$dayOfWeek]) || !$schedule[$dayOfWeek]['enabled']) {
            return false; // Day not available
        }
        
        // Extract just the time component for comparison
        $startTime = $startUtc->format('H:i:s');
        $endTime = $endUtc->format('H:i:s');
        
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
     * Get available time slots for a day based on the schedule and existing bookings with timezone support
     */
     public function getAvailableTimeSlots(EventEntity $event, DateTimeInterface $date, ?int $durationMinutes = null, ?string $clientTimezone = null): array
    {
        // Set default timezone if not provided
        if (!$clientTimezone) {
            $clientTimezone = 'UTC';
        }
        
        try {
            // Validate timezone
            new \DateTimeZone($clientTimezone);
        } catch (\Exception $e) {
            // Default to UTC if invalid timezone
            $clientTimezone = 'UTC';
        }
        
        // Default to first duration option if not specified
        if ($durationMinutes === null) {
            $durations = $event->getDuration();
            $durationMinutes = isset($durations[0]['duration']) ? (int)$durations[0]['duration'] : 30;
        }

        // Create client date objects for the requested date
        $clientDate = clone $date;
        if ($date->getTimezone()->getName() !== $clientTimezone) {
            $clientDate->setTimezone(new \DateTimeZone($clientTimezone));
        }
        
        // Reset to midnight in client timezone
        $clientStartOfDay = clone $clientDate;
        $clientStartOfDay->setTime(0, 0, 0);
        
        $clientEndOfDay = clone $clientDate;
        $clientEndOfDay->setTime(23, 59, 59);
        
        // Convert to UTC for processing - these define our search window
        $utcStartOfClientDay = clone $clientStartOfDay;
        $utcStartOfClientDay->setTimezone(new \DateTimeZone('UTC'));
        
        $utcEndOfClientDay = clone $clientEndOfDay;
        $utcEndOfClientDay->setTimezone(new \DateTimeZone('UTC'));
        
        // Get dates that need to be checked in UTC (could span up to 2 days)
        $datesToCheck = [];
        
        // Get the day of the week for the start date in UTC
        $currentDayCheck = clone $utcStartOfClientDay;
        $currentDayCheck->setTime(0, 0, 0); // Reset to start of the UTC day
        
        // Add the full UTC days that intersect with the client's requested day
        while ($currentDayCheck <= $utcEndOfClientDay) {
            $datesToCheck[] = clone $currentDayCheck;
            $currentDayCheck->modify('+1 day');
        }
        
        $allSlots = [];
        
        // Process each UTC day that overlaps with the requested client day
        foreach ($datesToCheck as $utcDayToCheck) {
            $dayOfWeek = strtolower($utcDayToCheck->format('l'));
            $schedule = $this->getScheduleForEvent($event);
            
            // Skip if day is not enabled in schedule
            if (!isset($schedule[$dayOfWeek]) || !$schedule[$dayOfWeek]['enabled']) {
                continue;
            }
            
            // Extract schedule times for this UTC day
            $scheduleStartTime = clone $utcDayToCheck;
            list($hours, $minutes, $seconds) = explode(':', $schedule[$dayOfWeek]['start_time']);
            $scheduleStartTime->setTime((int)$hours, (int)$minutes, (int)$seconds);
            
            $scheduleEndTime = clone $utcDayToCheck;
            list($hours, $minutes, $seconds) = explode(':', $schedule[$dayOfWeek]['end_time']);
            $scheduleEndTime->setTime((int)$hours, (int)$minutes, (int)$seconds);
            
            // Convert breaks to DateTime objects
            $breaks = [];
            foreach ($schedule[$dayOfWeek]['breaks'] as $break) {
                $breakStart = clone $utcDayToCheck;
                list($hours, $minutes, $seconds) = explode(':', $break['start_time']);
                $breakStart->setTime((int)$hours, (int)$minutes, (int)$seconds);
                
                $breakEnd = clone $utcDayToCheck;
                list($hours, $minutes, $seconds) = explode(':', $break['end_time']);
                $breakEnd->setTime((int)$hours, (int)$minutes, (int)$seconds);
                
                $breaks[] = [
                    'start' => $breakStart,
                    'end' => $breakEnd
                ];
            }
            
            // Get existing bookings for this UTC day
            $existingBookings = $this->getEventBookingsForDate($event, $utcDayToCheck);
            
            // Generate time slots in 15-minute increments
            $slotStart = clone $scheduleStartTime;
            $slotIncrement = new \DateInterval('PT15M'); // 15-min increments
            $eventDuration = new \DateInterval('PT' . $durationMinutes . 'M');
            
            while ($slotStart < $scheduleEndTime) {
                $slotEnd = clone $slotStart;
                $slotEnd->add($eventDuration);
                
                // If slot end is after schedule end, break
                if ($slotEnd > $scheduleEndTime) {
                    break;
                }
                
                // Check for conflicts
                $hasConflict = false;
                
                // Check breaks
                foreach ($breaks as $break) {
                    if ($slotStart < $break['end'] && $slotEnd > $break['start']) {
                        $hasConflict = true;
                        break;
                    }
                }
                
                // Check bookings
                if (!$hasConflict) {
                    foreach ($existingBookings as $booking) {
                        if ($slotStart < $booking['end'] && $slotEnd > $booking['start']) {
                            $hasConflict = true;
                            break;
                        }
                    }
                }
                
                if (!$hasConflict) {
                    // Create client-timezone versions of slot times
                    $clientSlotStart = clone $slotStart;
                    $clientSlotStart->setTimezone(new \DateTimeZone($clientTimezone));
                    
                    $clientSlotEnd = clone $slotEnd;
                    $clientSlotEnd->setTimezone(new \DateTimeZone($clientTimezone));
                    
                    // Check if this slot's START time falls within the requested client day
                    // This is the key change - we ONLY care about the start time now
                    if ($clientSlotStart->format('Y-m-d') === $clientDate->format('Y-m-d')) {
                        $allSlots[] = [
                            'start' => $slotStart->format('Y-m-d H:i:s'),
                            'end' => $slotEnd->format('Y-m-d H:i:s'),
                            'start_client' => $clientSlotStart->format('Y-m-d H:i:s'),
                            'end_client' => $clientSlotEnd->format('Y-m-d H:i:s'),
                            'timezone' => $clientTimezone
                        ];
                    }
                }
                
                // Move to next slot
                $slotStart->add($slotIncrement);
            }
        }
        
        return $allSlots;
    }

    /**
     * Get available dates within a range based on the event schedule with timezone support
     */
    public function getAvailableDates(EventEntity $event, \DateTimeInterface $startDate, \DateTimeInterface $endDate, ?string $clientTimezone = null): array
    {
        // Set default timezone if not provided
        if (!$clientTimezone) {
            $clientTimezone = 'UTC';
        }
        
        try {
            // Validate timezone
            new \DateTimeZone($clientTimezone);
        } catch (\Exception $e) {
            // Default to UTC if invalid timezone
            $clientTimezone = 'UTC';
        }
        
        // Get the event schedule
        $schedule = $this->getScheduleForEvent($event);
        
        // Extend range by one day in each direction to account for timezone differences
        $expandedStartDate = clone $startDate;
        $expandedStartDate->modify('-1 day');
        
        $expandedEndDate = clone $endDate;
        $expandedEndDate->modify('+1 day');
        
        // Generate list of dates to check in UTC
        $datesToCheck = [];
        $currentDate = clone $expandedStartDate;
        
        while ($currentDate <= $expandedEndDate) {
            $datesToCheck[] = clone $currentDate;
            $currentDate->modify('+1 day');
        }
        
        // Map to hold available dates in client timezone
        $availableDatesMap = [];
        
        // Check each date
        foreach ($datesToCheck as $utcDate) {
            $dayOfWeek = strtolower($utcDate->format('l'));
            
            // Skip if day is not enabled in schedule
            if (!isset($schedule[$dayOfWeek]) || !$schedule[$dayOfWeek]['enabled']) {
                continue;
            }
            
            // Create client timezone version of this date
            $clientDate = clone $utcDate;
            $clientDate->setTimezone(new \DateTimeZone($clientTimezone));
            $clientDateStr = $clientDate->format('Y-m-d');
            
            // Only include if it falls within the original requested range in client timezone
            $clientStartStr = $startDate->setTimezone(new \DateTimeZone($clientTimezone))->format('Y-m-d');
            $clientEndStr = $endDate->setTimezone(new \DateTimeZone($clientTimezone))->format('Y-m-d');
            
            if ($clientDateStr >= $clientStartStr && $clientDateStr <= $clientEndStr) {
                // Extract schedule times
                $scheduleStartTime = clone $utcDate;
                list($hours, $minutes, $seconds) = explode(':', $schedule[$dayOfWeek]['start_time']);
                $scheduleStartTime->setTime((int)$hours, (int)$minutes, (int)$seconds);
                
                $scheduleEndTime = clone $utcDate;
                list($hours, $minutes, $seconds) = explode(':', $schedule[$dayOfWeek]['end_time']);
                $scheduleEndTime->setTime((int)$hours, (int)$minutes, (int)$seconds);
                
                // Convert to client timezone for display
                $clientStartTime = clone $scheduleStartTime;
                $clientStartTime->setTimezone(new \DateTimeZone($clientTimezone));
                
                $clientEndTime = clone $scheduleEndTime;
                $clientEndTime->setTimezone(new \DateTimeZone($clientTimezone));
                
                // Store date with schedule information
                if (!isset($availableDatesMap[$clientDateStr])) {
                    $availableDatesMap[$clientDateStr] = [
                        'date' => $clientDateStr,
                        'day_of_week' => $clientDate->format('l'),
                        'available_blocks' => []
                    ];
                }
                
                // Add this availability block
                $availableDatesMap[$clientDateStr]['available_blocks'][] = [
                    'start_time' => $clientStartTime->format('H:i:s'),
                    'end_time' => $clientEndTime->format('H:i:s'),
                    'start_utc' => $scheduleStartTime->format('Y-m-d H:i:s'),
                    'end_utc' => $scheduleEndTime->format('Y-m-d H:i:s')
                ];
            }
        }
        
        // Convert map to array for return
        return array_values($availableDatesMap);
    }

    /**
     * Get all bookings for a specific event and date
     */
    private function getEventBookingsForDate(EventEntity $event, DateTimeInterface $date): array
    {
        $startOfDay = clone $date;
        $startOfDay->setTime(0, 0, 0);
        
        $endOfDay = clone $date;
        $endOfDay->setTime(23, 59, 59);
        
        try {
            // Define filters for the date range
            $filters = [
                [
                    'field' => 'startTime',
                    'operator' => 'greater_than_or_equal',
                    'value' => $startOfDay
                ],
                [
                    'field' => 'startTime',
                    'operator' => 'less_than_or_equal',
                    'value' => $endOfDay
                ]
            ];
            
            // Use CrudManager to fetch bookings
            $bookings = $this->crudManager->findMany(
                EventBookingEntity::class,
                $filters,
                1, 
                60, 
                [
                    'event' => $event,
                    'cancelled' => false
                ]
            );
            
            $formattedBookings = [];
            foreach ($bookings as $booking) {
                $formattedBookings[] = [
                    'start' => $booking->getStartTime(),
                    'end' => $booking->getEndTime()
                ];
            }
            
            return $formattedBookings;
        } catch (CrudException $e) {
            error_log('Error fetching bookings: ' . $e->getMessage());
            return [];
        }
    }


}