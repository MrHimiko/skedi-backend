<?php

namespace App\Plugins\Events\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTime;
use DateTimeInterface;

#[ORM\Entity]
#[ORM\Table(name: "event_booking_options")]
#[ORM\HasLifecycleCallbacks]
class EventBookingOptionEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "id", type: "bigint")]
    private int $id;

    #[ORM\ManyToOne(targetEntity: EventEntity::class)]
    #[ORM\JoinColumn(name: "event_id", referencedColumnName: "id", nullable: false)]
    private EventEntity $event;
    
    #[ORM\Column(name: "duration_minutes", type: "integer", nullable: false)]
    private int $durationMinutes;
    
    #[ORM\Column(name: "name", type: "string", length: 255, nullable: false)]
    private string $name;
    
    #[ORM\Column(name: "description", type: "text", nullable: true)]
    private ?string $description = null;
    
    #[ORM\Column(name: "active", type: "boolean", options: ["default" => true])]
    private bool $active = true;

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
    
    public function getEvent(): EventEntity
    {
        return $this->event;
    }
    
    public function setEvent(EventEntity $event): self
    {
        $this->event = $event;
        return $this;
    }
    
    public function getDurationMinutes(): int
    {
        return $this->durationMinutes;
    }
    
    public function setDurationMinutes(int $durationMinutes): self
    {
        $this->durationMinutes = $durationMinutes;
        return $this;
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
    
    public function getDescription(): ?string
    {
        return $this->description;
    }
    
    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }
    
    public function isActive(): bool
    {
        return $this->active;
    }
    
    public function setActive(bool $active): self
    {
        $this->active = $active;
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
        return [
            'id' => $this->getId(),
            'event_id' => $this->getEvent()->getId(),
            'duration_minutes' => $this->getDurationMinutes(),
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'active' => $this->isActive(),
            'updated' => $this->getUpdated()->format('Y-m-d H:i:s'),
            'created' => $this->getCreated()->format('Y-m-d H:i:s'),
        ];
    }
}