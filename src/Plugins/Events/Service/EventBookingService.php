<?php

namespace App\Plugins\Events\Service;

use App\Service\CrudManager;
use App\Exception\CrudException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraints as Assert;

use App\Plugins\Events\Entity\EventEntity;
use App\Plugins\Events\Entity\EventBookingEntity;
use App\Plugins\Events\Entity\EventBookingOptionEntity;
use App\Plugins\Events\Entity\EventGuestEntity;
use App\Plugins\Events\Entity\EventTimeSlotEntity;
use App\Plugins\Events\Entity\ContactEntity;
use App\Plugins\Events\Exception\EventsException;
use DateTime;

class EventBookingService
{
    private CrudManager $crudManager;
    private EntityManagerInterface $entityManager;
    private ContactService $contactService;

    public function __construct(
        CrudManager $crudManager,
        EntityManagerInterface $entityManager,
        ContactService $contactService
    ) {
        $this->crudManager = $crudManager;
        $this->entityManager = $entityManager;
        $this->contactService = $contactService;
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
            
            $startTime = $data['start_time'] instanceof \DateTimeInterface 
                ? $data['start_time'] 
                : new \DateTime($data['start_time']);
                
            $endTime = $data['end_time'] instanceof \DateTimeInterface 
                ? $data['end_time'] 
                : new \DateTime($data['end_time']);
            
            if ($startTime >= $endTime) {
                throw new EventsException('End time must be after start time');
            }
            
            // Check if the slot is available (not in a break and not overlapping with existing bookings)
            $this->validateTimeSlotAvailability($event, $startTime, $endTime);
            
            // Create the booking
            $booking = new EventBookingEntity();
            $booking->setEvent($event);
            $booking->setStartTime($startTime);
            $booking->setEndTime($endTime);
            $booking->setStatus('confirmed'); // Default status
            
            // Set booking option if provided
            if (!empty($data['booking_option_id'])) {
                $bookingOption = $this->entityManager->getRepository(EventBookingOptionEntity::class)
                    ->find($data['booking_option_id']);
                    
                if ($bookingOption && $bookingOption->getEvent()->getId() === $event->getId()) {
                    $booking->setBookingOption($bookingOption);
                }
            }
            
            // Save form data if provided
            if (!empty($data['form_data']) && is_array($data['form_data'])) {
                $booking->setFormDataFromArray($data['form_data']);
            }
            
            $this->entityManager->persist($booking);
            $this->entityManager->flush();
            
            // Process guests if provided
            if (!empty($data['guests']) && is_array($data['guests'])) {
                foreach ($data['guests'] as $guestData) {
                    $this->addGuest($booking, $guestData);
                }
            }
            
            return $booking;
        } catch (\Exception $e) {
            throw new EventsException($e->getMessage());
        }
    }

    public function update(EventBookingEntity $booking, array $data): void
    {
        try {
            // Update booking status if provided
            if (!empty($data['status'])) {
                $booking->setStatus($data['status']);
            }
            
            // Update booking times if provided
            $timeUpdated = false;
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
                
                // Check if the new slot is available
                if ($startTime != $booking->getStartTime() || $endTime != $booking->getEndTime()) {
                    $this->validateTimeSlotAvailability($booking->getEvent(), $startTime, $endTime, $booking->getId());
                    $booking->setStartTime($startTime);
                    $booking->setEndTime($endTime);
                    $timeUpdated = true;
                }
            }
            
            // Update booking option if provided
            if (!empty($data['booking_option_id'])) {
                $bookingOption = $this->entityManager->getRepository(EventBookingOptionEntity::class)
                    ->find($data['booking_option_id']);
                    
                if ($bookingOption && $bookingOption->getEvent()->getId() === $booking->getEvent()->getId()) {
                    $booking->setBookingOption($bookingOption);
                }
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
                $existingGuests = $this->entityManager->getRepository(EventGuestEntity::class)
                    ->findBy(['booking' => $booking]);
                    
                foreach ($existingGuests as $existingGuest) {
                    $this->entityManager->remove($existingGuest);
                }
                $this->entityManager->flush();
                
                // Add new guests
                foreach ($data['guests'] as $guestData) {
                    $this->addGuest($booking, $guestData);
                }
            }
        } catch (\Exception $e) {
            throw new EventsException($e->getMessage());
        }
    }

    public function cancel(EventBookingEntity $booking): void
    {
        try {
            $booking->setCancelled(true);
            $booking->setStatus('cancelled');
            
            $this->entityManager->persist($booking);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            throw new EventsException($e->getMessage());
        }
    }
    
    public function delete(EventBookingEntity $booking): void
    {
        try {
            // Remove related guests first
            $guests = $this->entityManager->getRepository(EventGuestEntity::class)
                ->findBy(['booking' => $booking]);
                
            foreach ($guests as $guest) {
                $this->entityManager->remove($guest);
            }
            
            // Remove the booking
            $this->entityManager->remove($booking);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            throw new EventsException($e->getMessage());
        }
    }
    
    public function getBookingsByEvent(EventEntity $event, array $filters = [], bool $includeCancelled = false): array
    {
        $criteria = ['event' => $event];
        
        if (!$includeCancelled) {
            $criteria['cancelled'] = false;
        }
        
        return $this->getMany($filters, 1, 1000, $criteria);
    }
    
    public function getBookingsByDateRange(EventEntity $event, \DateTimeInterface $startDate, \DateTimeInterface $endDate, bool $includeCancelled = false): array
    {
        try {
            $qb = $this->entityManager->createQueryBuilder();
            $qb->select('b')
               ->from(EventBookingEntity::class, 'b')
               ->where('b.event = :event')
               ->andWhere('b.startTime >= :startDate')
               ->andWhere('b.startTime <= :endDate')
               ->setParameter('event', $event)
               ->setParameter('startDate', $startDate)
               ->setParameter('endDate', $endDate);
               
            if (!$includeCancelled) {
                $qb->andWhere('b.cancelled = :cancelled')
                   ->setParameter('cancelled', false);
            }
            
            return $qb->getQuery()->getResult();
        } catch (\Exception $e) {
            throw new EventsException($e->getMessage());
        }
    }
    
    public function getGuests(EventBookingEntity $booking): array
    {
        return $this->entityManager->getRepository(EventGuestEntity::class)
            ->findBy(['booking' => $booking]);
    }
    
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
            
            // Update or create contact record
            $this->contactService->updateOrCreate([
                'name' => $guestData['name'],
                'email' => $guestData['email'],
                'phone' => $guestData['phone'] ?? null,
                'last_event_id' => $booking->getEvent()->getId(),
                'last_interaction' => new DateTime(),
            ]);
            
            return $guest;
        } catch (\Exception $e) {
            throw new EventsException($e->getMessage());
        }
    }
    
    private function validateTimeSlotAvailability(EventEntity $event, \DateTimeInterface $startTime, \DateTimeInterface $endTime, ?int $excludeBookingId = null): void
    {
        // 1. Check if the time is during an available slot (not in a break)
        $isWithinAvailableSlot = false;
        $timeSlots = $this->entityManager->getRepository(EventTimeSlotEntity::class)
            ->findBy(['event' => $event], ['startTime' => 'ASC']);
            
        if (empty($timeSlots)) {
            throw new EventsException('No time slots defined for this event');
        }
        
        foreach ($timeSlots as $slot) {
            // Skip break slots
            if ($slot->isBreak()) {
                // Check if requested time overlaps with a break
                if (
                    ($startTime >= $slot->getStartTime() && $startTime < $slot->getEndTime()) ||
                    ($endTime > $slot->getStartTime() && $endTime <= $slot->getEndTime()) ||
                    ($startTime <= $slot->getStartTime() && $endTime >= $slot->getEndTime())
                ) {
                    throw new EventsException('The selected time overlaps with a break period');
                }
                continue;
            }
            
            // Check if time is within a non-break slot
            if ($startTime >= $slot->getStartTime() && $endTime <= $slot->getEndTime()) {
                $isWithinAvailableSlot = true;
                break;
            }
        }
        
        if (!$isWithinAvailableSlot) {
            throw new EventsException('The selected time is not within any available time slot');
        }
        
        // 2. Check for overlapping bookings
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('b')
           ->from(EventBookingEntity::class, 'b')
           ->where('b.event = :event')
           ->andWhere('b.cancelled = :cancelled')
           ->andWhere(
               $qb->expr()->orX(
                   // New booking starts during an existing booking
                   $qb->expr()->andX(
                       $qb->expr()->gte('b.startTime', ':start'),
                       $qb->expr()->lt('b.startTime', ':end')
                   ),
                   // New booking ends during an existing booking
                   $qb->expr()->andX(
                       $qb->expr()->gt('b.endTime', ':start'),
                       $qb->expr()->lte('b.endTime', ':end')
                   ),
                   // New booking completely contains an existing booking
                   $qb->expr()->andX(
                       $qb->expr()->lte('b.startTime', ':start'),
                       $qb->expr()->gte('b.endTime', ':end')
                   )
               )
           )
           ->setParameter('event', $event)
           ->setParameter('cancelled', false)
           ->setParameter('start', $startTime)
           ->setParameter('end', $endTime);
        
        // Exclude the current booking if we're updating
        if ($excludeBookingId) {
            $qb->andWhere('b.id != :excludeId')
               ->setParameter('excludeId', $excludeBookingId);
        }
        
        $overlappingBookings = $qb->getQuery()->getResult();
        
        if (!empty($overlappingBookings)) {
            throw new EventsException('The selected time slot overlaps with an existing booking');
        }
    }
}