<?php

namespace App\Plugins\Events\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTime;
use DateTimeInterface;

#[ORM\Entity]
#[ORM\Table(name: "contacts")]
#[ORM\HasLifecycleCallbacks]
class ContactEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "id", type: "bigint")]
    private int $id;
    
    #[ORM\Column(name: "name", type: "string", length: 255, nullable: false)]
    private string $name;
    
    #[ORM\Column(name: "email", type: "string", length: 255, nullable: false)]
    private string $email;
    
    #[ORM\Column(name: "phone", type: "string", length: 50, nullable: true)]
    private ?string $phone = null;
    
    #[ORM\ManyToOne(targetEntity: EventEntity::class)]
    #[ORM\JoinColumn(name: "event_id", referencedColumnName: "id", nullable: true)]
    private ?EventEntity $event = null;
    
    #[ORM\ManyToOne(targetEntity: EventBookingEntity::class)]
    #[ORM\JoinColumn(name: "booking_id", referencedColumnName: "id", nullable: true)]
    private ?EventBookingEntity $booking = null;

    #[ORM\Column(name: "updated", type: "datetime", nullable: false, options: ["default" => "CURRENT_TIMESTAMP"])]
    private DateTimeInterface $updated;

    #[ORM\Column(name: "created", type: "datetime", nullable: false, options: ["default" => "CURRENT_TIMESTAMP"])]
    private DateTimeInterface $created;

    public function __construct()
    {
        $this->created = new DateTime();
        $this->updated = new DateTime();
    }

    public function getId(): int
    {
        return $this->id;
    }
    
    public function getName(): string
    {
        return $this->name;
    }
    
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }
    
    public function getEmail(): string
    {
        return $this->email;
    }
    
    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }
    
    public function getPhone(): ?string
    {
        return $this->phone;
    }
    
    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }
    
    public function getEvent(): ?EventEntity
    {
        return $this->event;
    }
    
    public function setEvent(?EventEntity $event): self
    {
        $this->event = $event;
        return $this;
    }
    
    public function getBooking(): ?EventBookingEntity
    {
        return $this->booking;
    }
    
    public function setBooking(?EventBookingEntity $booking): self
    {
        $this->booking = $booking;
        return $this;
    }

    public function getUpdated(): DateTimeInterface
    {
        return $this->updated;
    }
    
    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updated = new DateTime();
    }

    public function getCreated(): DateTimeInterface
    {
        return $this->created;
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'email' => $this->getEmail(),
            'phone' => $this->getPhone(),
            'updated' => $this->getUpdated()->format('Y-m-d H:i:s'),
            'created' => $this->getCreated()->format('Y-m-d H:i:s'),
        ];
        
        if ($this->getEvent()) {
            $data['event_id'] = $this->getEvent()->getId();
        }
        
        if ($this->getBooking()) {
            $data['booking_id'] = $this->getBooking()->getId();
        }
        
        return $data;
    }
}