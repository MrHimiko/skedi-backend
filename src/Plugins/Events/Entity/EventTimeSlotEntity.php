<?php

namespace App\Plugins\Events\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTime;
use DateTimeInterface;

#[ORM\Entity]
#[ORM\Table(name: "event_time_slots")]
#[ORM\HasLifecycleCallbacks]
class EventTimeSlotEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "id", type: "bigint")]
    private int $id;

    #[ORM\ManyToOne(targetEntity: EventEntity::class)]
    #[ORM\JoinColumn(name: "event_id", referencedColumnName: "id", nullable: false)]
    private EventEntity $event;
    
    #[ORM\Column(name: "start_time", type: "datetime", nullable: false)]
    private DateTimeInterface $startTime;
    
    #[ORM\Column(name: "end_time", type: "datetime", nullable: false)]
    private DateTimeInterface $endTime;
    
    #[ORM\Column(name: "is_break", type: "boolean", options: ["default" => false])]
    private bool $isBreak = false;

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
    
    public function getStartTime(): DateTimeInterface
    {
        return $this->startTime;
    }
    
    public function setStartTime(DateTimeInterface $startTime): self
    {
        $this->startTime = $startTime;
        return $this;
    }
    
    public function getEndTime(): DateTimeInterface
    {
        return $this->endTime;
    }
    
    public function setEndTime(DateTimeInterface $endTime): self
    {
        $this->endTime = $endTime;
        return $this;
    }
    
    public function isBreak(): bool
    {
        return $this->isBreak;
    }
    
    public function setIsBreak(bool $isBreak): self
    {
        $this->isBreak = $isBreak;
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
            'start_time' => $this->getStartTime()->format('Y-m-d H:i:s'),
            'end_time' => $this->getEndTime()->format('Y-m-d H:i:s'),
            'is_break' => $this->isBreak(),
            'updated' => $this->getUpdated()->format('Y-m-d H:i:s'),
            'created' => $this->getCreated()->format('Y-m-d H:i:s'),
        ];
    }
}