<?php

namespace App\Plugins\Events\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTime;
use DateTimeInterface;
use App\Plugins\Account\Entity\UserEntity;

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
    
    #[ORM\ManyToOne(targetEntity: UserEntity::class)]
    #[ORM\JoinColumn(name: "last_assignee_id", referencedColumnName: "id", nullable: true)]
    private ?UserEntity $lastAssignee = null;
    
    #[ORM\ManyToOne(targetEntity: EventEntity::class)]
    #[ORM\JoinColumn(name: "last_event_id", referencedColumnName: "id", nullable: true)]
    private ?EventEntity $lastEvent = null;
    
    #[ORM\Column(name: "last_interaction", type: "datetime", nullable: true)]
    private ?DateTimeInterface $lastInteraction = null;
    
    #[ORM\Column(name: "notes", type: "text", nullable: true)]
    private ?string $notes = null;

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
    
    public function getLastAssignee(): ?UserEntity
    {
        return $this->lastAssignee;
    }
    
    public function setLastAssignee(?UserEntity $lastAssignee): self
    {
        $this->lastAssignee = $lastAssignee;
        return $this;
    }
    
    public function getLastEvent(): ?EventEntity
    {
        return $this->lastEvent;
    }
    
    public function setLastEvent(?EventEntity $lastEvent): self
    {
        $this->lastEvent = $lastEvent;
        return $this;
    }
    
    public function getLastInteraction(): ?DateTimeInterface
    {
        return $this->lastInteraction;
    }
    
    public function setLastInteraction(?DateTimeInterface $lastInteraction): self
    {
        $this->lastInteraction = $lastInteraction;
        return $this;
    }
    
    public function getNotes(): ?string
    {
        return $this->notes;
    }
    
    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
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
            'notes' => $this->getNotes(),
            'updated' => $this->getUpdated()->format('Y-m-d H:i:s'),
            'created' => $this->getCreated()->format('Y-m-d H:i:s'),
        ];
        
        if ($this->getLastAssignee()) {
            $data['last_assignee'] = $this->getLastAssignee()->toArray();
        }
        
        if ($this->getLastEvent()) {
            $data['last_event_id'] = $this->getLastEvent()->getId();
        }
        
        if ($this->getLastInteraction()) {
            $data['last_interaction'] = $this->getLastInteraction()->format('Y-m-d H:i:s');
        }
        
        return $data;
    }
}