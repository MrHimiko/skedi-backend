<?php

namespace App\Plugins\Events\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTime;
use DateTimeInterface;
use App\Plugins\Account\Entity\UserEntity;

#[ORM\Entity]
#[ORM\Table(name: "event_assignees")]
#[ORM\HasLifecycleCallbacks]
class EventAssigneeEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "id", type: "bigint")]
    private int $id;

    #[ORM\ManyToOne(targetEntity: EventEntity::class)]
    #[ORM\JoinColumn(name: "event_id", referencedColumnName: "id", nullable: false, onDelete: "CASCADE")]
    private EventEntity $event;
    
    #[ORM\ManyToOne(targetEntity: UserEntity::class)]
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "id", nullable: false)]
    private UserEntity $user;

    #[ORM\ManyToOne(targetEntity: UserEntity::class)]
    #[ORM\JoinColumn(name: "assigned_by", referencedColumnName: "id", nullable: false)]
    private UserEntity $assignedBy;

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
    
    public function getUser(): UserEntity
    {
        return $this->user;
    }
    
    public function setUser(UserEntity $user): self
    {
        $this->user = $user;
        return $this;
    }
    
    public function getAssignedBy(): UserEntity
    {
        return $this->assignedBy;
    }
    
    public function setAssignedBy(UserEntity $assignedBy): self
    {
        $this->assignedBy = $assignedBy;
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
            'user' => $this->getUser()->toArray(),
            'assigned_by' => $this->getAssignedBy()->toArray(),
            'updated' => $this->getUpdated()->format('Y-m-d H:i:s'),
            'created' => $this->getCreated()->format('Y-m-d H:i:s'),
        ];
    }
}