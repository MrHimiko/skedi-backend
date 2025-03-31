<?php

namespace App\Plugins\Events\Service;

use App\Service\CrudManager;
use App\Exception\CrudException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraints as Assert;

use App\Plugins\Events\Entity\ContactEntity;
use App\Plugins\Events\Entity\EventEntity;
use App\Plugins\Events\Entity\EventBookingEntity;
use App\Plugins\Events\Exception\EventsException;

class ContactService
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
                ContactEntity::class,
                $filters,
                $page,
                $limit,
                $criteria
            );
        } catch (CrudException $e) {
            throw new EventsException($e->getMessage());
        }
    }

    public function getOne(int $id, array $criteria = []): ?ContactEntity
    {
        return $this->crudManager->findOne(ContactEntity::class, $id, $criteria);
    }
    
    public function findByEmail(string $email): ?ContactEntity
    {
        return $this->entityManager->getRepository(ContactEntity::class)
            ->findOneBy(['email' => $email]);
    }

    public function create(array $data): ContactEntity
    {
        try {
            $contact = new ContactEntity();
            
            $constraints = [
                'name' => [
                    new Assert\NotBlank(['message' => 'Contact name is required.']),
                    new Assert\Type('string'),
                    new Assert\Length(['min' => 2, 'max' => 255]),
                ],
                'email' => [
                    new Assert\NotBlank(['message' => 'Contact email is required.']),
                    new Assert\Email(['message' => 'Invalid email format']),
                ],
                'phone' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['max' => 50]),
                ]),
                'event_id' => new Assert\Optional([
                    new Assert\Type('numeric'),
                ]),
                'booking_id' => new Assert\Optional([
                    new Assert\Type('numeric'),
                ]),
            ];
            
            $transform = [
                'event_id' => function($value) {
                    if ($value) {
                        $event = $this->entityManager->getRepository(EventEntity::class)->find($value);
                        if (!$event) {
                            throw new EventsException('Event not found');
                        }
                        return $event;
                    }
                    return null;
                },
                'booking_id' => function($value) {
                    if ($value) {
                        $booking = $this->entityManager->getRepository(EventBookingEntity::class)->find($value);
                        if (!$booking) {
                            throw new EventsException('Booking not found');
                        }
                        return $booking;
                    }
                    return null;
                },
            ];
            
            $this->crudManager->create($contact, $data, $constraints, $transform);
            
            return $contact;
        } catch (CrudException $e) {
            throw new EventsException($e->getMessage());
        }
    }

    public function update(ContactEntity $contact, array $data): void
    {
        try {
            $constraints = [
                'name' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['min' => 2, 'max' => 255]),
                ]),
                'email' => new Assert\Optional([
                    new Assert\Email(['message' => 'Invalid email format']),
                ]),
                'phone' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['max' => 50]),
                ]),
                'event_id' => new Assert\Optional([
                    new Assert\Type('numeric'),
                ]),
                'booking_id' => new Assert\Optional([
                    new Assert\Type('numeric'),
                ]),
            ];
            
            $transform = [
                'event_id' => function($value) {
                    if ($value) {
                        $event = $this->entityManager->getRepository(EventEntity::class)->find($value);
                        if (!$event) {
                            throw new EventsException('Event not found');
                        }
                        return $event;
                    }
                    return null;
                },
                'booking_id' => function($value) {
                    if ($value) {
                        $booking = $this->entityManager->getRepository(EventBookingEntity::class)->find($value);
                        if (!$booking) {
                            throw new EventsException('Booking not found');
                        }
                        return $booking;
                    }
                    return null;
                },
            ];
            
            $this->crudManager->update($contact, $data, $constraints, $transform);
        } catch (CrudException $e) {
            throw new EventsException($e->getMessage());
        }
    }

    public function delete(ContactEntity $contact): void
    {
        try {
            $this->entityManager->remove($contact);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            throw new EventsException($e->getMessage());
        }
    }
    
    public function createContact(array $data): ContactEntity
    {
        if (empty($data['email'])) {
            throw new EventsException('Email is required');
        }
        
        // Create a new contact directly
        return $this->create($data);
    }
    
    public function getContactsByEvent(EventEntity $event): array
    {
        return $this->entityManager->getRepository(ContactEntity::class)
            ->findBy(['event' => $event]);
    }
    
    public function getContactsByBooking(EventBookingEntity $booking): array
    {
        return $this->entityManager->getRepository(ContactEntity::class)
            ->findBy(['booking' => $booking]);
    }
}