<?php

namespace App\Plugins\Events\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTime;
use DateTimeInterface;

#[ORM\Entity]
#[ORM\Table(name: "event_schedules")]
#[ORM\HasLifecycleCallbacks]
class EventScheduleEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "id", type: "bigint")]
    private int $id;

    #[ORM\ManyToOne(targetEntity: EventEntity::class)]
    #[ORM\JoinColumn(name: "event_id", referencedColumnName: "id", nullable: false)]
    private EventEntity $event;
    
    #[ORM\Column(name: "schedule", type: "json", nullable: false)]
    private array $schedule = [];

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
    
    public function getSchedule(): array
    {
        return $this->schedule;
    }
    
    public function setSchedule(array $schedule): self
    {
        $this->schedule = $schedule;
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
            'schedule' => $this->getSchedule(),
            'updated' => $this->getUpdated()->format('Y-m-d H:i:s'),
            'created' => $this->getCreated()->format('Y-m-d H:i:s'),
        ];
    }
}