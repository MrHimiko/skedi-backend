<?php

namespace App\Plugins\Events\Service;

use App\Service\CrudManager;
use App\Exception\CrudException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraints as Assert;

use App\Plugins\Events\Entity\EventEntity;
use App\Plugins\Events\Entity\EventBookingEntity;
use App\Plugins\Events\Entity\EventGuestEntity;
use App\Plugins\Events\Entity\ContactEntity;
use App\Plugins\Events\Exception\EventsException;
use DateTime;

class EventBookingService
{
    private CrudManager $crudManager;
    private EntityManagerInterface $entityManager;
    private ContactService $contactService;
    private EventScheduleService $scheduleService;

    public function __construct(
        CrudManager $crudManager,
        EntityManagerInterface $entityManager,
        ContactService $contactService,
        EventScheduleService $scheduleService
    ) {
        $this->crudManager = $crudManager;
        $this->entityManager = $entityManager;
        $this->contactService = $contactService;
        $this->scheduleService = $scheduleService;
    }

    public function getMany(array $filters, int $page, int $limit, array $criteria = []): array
    {
        try {
            return $this->crudManager->findMany(
                EventBookingEntity::class,
                $filters,
                $page,
                $limit,
                $criteria
            );
        } catch (CrudException $e) {
            throw new EventsException($e->getMessage());
        }
    }

    public function getOne(int $id, array $criteria = []): ?EventBookingEntity
    {
        return $this->crudManager->findOne(EventBookingEntity::class, $id, $criteria);
    }

    public function create(array $data): EventBookingEntity
    {
        try {
            if (empty($data['event_id'])) {
                throw new EventsException('Event ID is required');
            }
            
            $event = $this->entityManager->getRepository(EventEntity::class)->find($data['event_id']);
            if (!$event) {
                throw new EventsException('Event not found');
            }
            
            // Validate time slot
            if (empty($data['start_time']) || empty($data['end_time'])) {
                throw new EventsException('Start and end time are required');
            }
            
            // Handle timezone if provided
            $timezone = $data['timezone'] ?? 'UTC';
            try {
                new \DateTimeZone($timezone);
            } catch (\Exception $e) {
                $timezone = 'UTC';
            }
            
            // Convert to UTC timestamps for storage
            $startTime = $data['start_time'] instanceof \DateTimeInterface 
                ? clone $data['start_time'] 
                : new \DateTime($data['start_time'], new \DateTimeZone($timezone));
            
            $endTime = $data['end_time'] instanceof \DateTimeInterface 
                ? clone $data['end_time'] 
                : new \DateTime($data['end_time'], new \DateTimeZone($timezone));
            
            // Ensure timestamps are in UTC
            if ($startTime->getTimezone()->getName() !== 'UTC') {
                $startTime->setTimezone(new \DateTimeZone('UTC'));
            }
            
            if ($endTime->getTimezone()->getName() !== 'UTC') {
                $endTime->setTimezone(new \DateTimeZone('UTC'));
            }
            
            if ($startTime >= $endTime) {
                throw new EventsException('End time must be after start time');
            }
            
            // Check if the slot is available based on schedule, existing bookings, and host availability
            if (!$this->scheduleService->isTimeSlotAvailableForAll($event, $startTime, $endTime)) {
                throw new EventsException('The selected time slot is not available for the event or its hosts');
            }
            
            // Create the booking
            $booking = new EventBookingEntity();
            $booking->setEvent($event);
            $booking->setStartTime($startTime);
            $booking->setEndTime($endTime);
            $booking->setStatus('confirmed'); // Default status
            
            // Store original timezone in form data for reference
            if (empty($data['form_data']) || !is_array($data['form_data'])) {
                $data['form_data'] = [];
            }
            $data['form_data']['booking_timezone'] = $timezone;
            
            // Handle duration option selection
            if (isset($data['duration_index']) && is_numeric($data['duration_index'])) {
                $durations = $event->getDuration();
                $durationIndex = (int)$data['duration_index'];
                
                if (isset($durations[$durationIndex])) {
                    $data['form_data']['selected_duration'] = $durations[$durationIndex];
                }
            }
            
            // Save form data
            $booking->setFormDataFromArray($data['form_data']);
            
            $this->entityManager->persist($booking);
            $this->entityManager->flush();
            
            // Process guests if provided
            if (!empty($data['guests']) && is_array($data['guests'])) {
                foreach ($data['guests'] as $guestData) {
                    $this->addGuest($booking, $guestData);
                }
            }
            
            // Create availability records for event hosts
            $this->scheduleService->handleBookingCreated($booking);
            
            return $booking;
        } catch (\Exception $e) {
            throw new EventsException('Failed to create booking: ' . $e->getMessage());
        }
    }

    public function update(EventBookingEntity $booking, array $data): void
    {
        try {
            // Check if times are being changed
            $timesChanged = false;
            
            if (!empty($data['start_time']) && !empty($data['end_time'])) {
                $startTime = $data['start_time'] instanceof \DateTimeInterface 
                    ? $data['start_time'] 
                    : new \DateTime($data['start_time']);
                    
                $endTime = $data['end_time'] instanceof \DateTimeInterface 
                    ? $data['end_time'] 
                    : new \DateTime($data['end_time']);
                
                if ($startTime >= $endTime) {
                    throw new EventsException('End time must be after start time');
                }
                
                // Only check availability if times are changing
                if ($startTime != $booking->getStartTime() || $endTime != $booking->getEndTime()) {
                    $timesChanged = true;
                    
                    // Check if the new slot is available, excluding this booking
                    if (!$this->scheduleService->isTimeSlotAvailableForAll(
                        $booking->getEvent(), 
                        $startTime, 
                        $endTime, 
                        null,
                        $booking->getId()
                    )) {
                        throw new EventsException('The selected time slot is not available');
                    }
                    
                    $booking->setStartTime($startTime);
                    $booking->setEndTime($endTime);
                }
            }
            
            // Update status if provided
            if (!empty($data['status'])) {
                $booking->setStatus($data['status']);
            }
            
            // Update form data if provided
            if (!empty($data['form_data']) && is_array($data['form_data'])) {
                $booking->setFormDataFromArray($data['form_data']);
            }
            
            // Update cancellation status if provided
            if (isset($data['cancelled'])) {
                $booking->setCancelled((bool)$data['cancelled']);
            }
            
            $this->entityManager->persist($booking);
            $this->entityManager->flush();
            
            // Update guests if provided
            if (!empty($data['guests']) && is_array($data['guests'])) {
                // Remove existing guests
                $existingGuests = $this->crudManager->findMany(
                    EventGuestEntity::class,
                    [],
                    1,
                    1000,
                    ['booking' => $booking]
                );
                    
                foreach ($existingGuests as $existingGuest) {
                    $this->entityManager->remove($existingGuest);
                }
                $this->entityManager->flush();
                
                // Add new guests
                foreach ($data['guests'] as $guestData) {
                    $this->addGuest($booking, $guestData);
                }
            }
            
            // Update availability records if times changed or status changed
            if ($timesChanged || isset($data['cancelled'])) {
                if ($booking->isCancelled()) {
                    $this->scheduleService->handleBookingCancelled($booking);
                } else {
                    $this->scheduleService->handleBookingUpdated($booking);
                }
            }
        } catch (\Exception $e) {
            throw new EventsException('Failed to update booking: ' . $e->getMessage());
        }
    }

    public function cancel(EventBookingEntity $booking): void
    {
        try {
            $booking->setCancelled(true);
            $booking->setStatus('cancelled');
            
            $this->entityManager->persist($booking);
            $this->entityManager->flush();
            
            // Update availability records
            $this->scheduleService->handleBookingCancelled($booking);
        } catch (\Exception $e) {
            throw new EventsException('Failed to cancel booking: ' . $e->getMessage());
        }
    }
    
    public function delete(EventBookingEntity $booking): void
    {
        try {
            // First mark the booking as cancelled to update availability records
            if (!$booking->isCancelled()) {
                $booking->setCancelled(true);
                $this->scheduleService->handleBookingCancelled($booking);
            }
            
            // Remove related guests first
            $guests = $this->crudManager->findMany(
                EventGuestEntity::class,
                [],
                1,
                1000,
                ['booking' => $booking]
            );
                
            foreach ($guests as $guest) {
                $this->entityManager->remove($guest);
            }
            
            // Remove the booking
            $this->entityManager->remove($booking);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            throw new EventsException('Failed to delete booking: ' . $e->getMessage());
        }
    }
    
    /**
     * Add a guest to a booking
     */
    private function addGuest(EventBookingEntity $booking, array $guestData): EventGuestEntity
    {
        try {
            if (empty($guestData['name']) || empty($guestData['email'])) {
                throw new EventsException('Guest must have a name and email');
            }
            
            $guest = new EventGuestEntity();
            $guest->setBooking($booking);
            $guest->setName($guestData['name']);
            $guest->setEmail($guestData['email']);
            
            if (!empty($guestData['phone'])) {
                $guest->setPhone($guestData['phone']);
            }
            
            $this->entityManager->persist($guest);
            $this->entityManager->flush();
            
            // Create a new contact record
            $contact = new ContactEntity();
            $contact->setName($guestData['name']);
            $contact->setEmail($guestData['email']);
            
            if (!empty($guestData['phone'])) {
                $contact->setPhone($guestData['phone']);
            }
            
            // Set direct entity references instead of IDs
            $contact->setEvent($booking->getEvent());
            $contact->setBooking($booking);
            
            $this->entityManager->persist($contact);
            $this->entityManager->flush();
            
            return $guest;
        } catch (\Exception $e) {
            throw new EventsException('Failed to add guest: ' . $e->getMessage());
        }
    }
    
    /**
     * Get bookings for an event within a date range
     */
    public function getBookingsByEvent(EventEntity $event, array $filters = [], bool $includeCancelled = false): array
    {
        $criteria = ['event' => $event];
        
        if (!$includeCancelled) {
            $criteria['cancelled'] = false;
        }
        
        return $this->getMany($filters, 1, 1000, $criteria);
    }
    
    /**
     * Get bookings for an event within a date range
     */
    public function getBookingsByDateRange(EventEntity $event, \DateTimeInterface $startDate, \DateTimeInterface $endDate, bool $includeCancelled = false): array
    {
        try {
            $criteria = [
                'event' => $event
            ];
            
            if (!$includeCancelled) {
                $criteria['cancelled'] = false;
            }
            
            return $this->crudManager->findMany(
                EventBookingEntity::class,
                [
                    [
                        'field' => 'startTime',
                        'operator' => 'greater_than_or_equal',
                        'value' => $startDate
                    ],
                    [
                        'field' => 'startTime',
                        'operator' => 'less_than_or_equal',
                        'value' => $endDate
                    ]
                ],
                1,
                1000,
                $criteria
            );
        } catch (CrudException $e) {
            throw new EventsException($e->getMessage());
        }
    }
    
    /**
     * Get guests for a booking
     */
    public function getGuests(EventBookingEntity $booking): array
    {
        try {
            return $this->crudManager->findMany(
                EventGuestEntity::class,
                [],
                1,
                1000,
                ['booking' => $booking]
            );
        } catch (CrudException $e) {
            throw new EventsException($e->getMessage());
        }
    }
}