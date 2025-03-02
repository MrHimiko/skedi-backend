<?php

namespace App\Plugins\Events\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTime;
use DateTimeInterface;
use App\Plugins\Organizations\Entity\OrganizationEntity;
use App\Plugins\Teams\Entity\TeamEntity;
use App\Plugins\Account\Entity\UserEntity;

#[ORM\Entity]
#[ORM\Table(name: "events")]
#[ORM\HasLifecycleCallbacks]
class EventEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "id", type: "bigint")]
    private int $id;

    #[ORM\Column(name: "name", type: "string", length: 255, nullable: false)]
    private string $name;
    
    #[ORM\Column(name: "description", type: "text", nullable: true)]
    private ?string $description = null;
    
    #[ORM\ManyToOne(targetEntity: OrganizationEntity::class)]
    #[ORM\JoinColumn(name: "organization_id", referencedColumnName: "id", nullable: false)]
    private OrganizationEntity $organization;
    
    #[ORM\ManyToOne(targetEntity: TeamEntity::class)]
    #[ORM\JoinColumn(name: "team_id", referencedColumnName: "id", nullable: true)]
    private ?TeamEntity $team = null;
    
    #[ORM\ManyToOne(targetEntity: UserEntity::class)]
    #[ORM\JoinColumn(name: "created_by", referencedColumnName: "id", nullable: false)]
    private UserEntity $createdBy;

    #[ORM\Column(name: "deleted", type: "boolean", options: ["default" => false])]
    private bool $deleted = false;

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
    
    public function getDescription(): ?string
    {
        return $this->description;
    }
    
    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }
    
    public function getOrganization(): OrganizationEntity
    {
        return $this->organization;
    }
    
    public function setOrganization(OrganizationEntity $organization): self
    {
        $this->organization = $organization;
        return $this;
    }
    
    public function getTeam(): ?TeamEntity
    {
        return $this->team;
    }
    
    public function setTeam(?TeamEntity $team): self
    {
        $this->team = $team;
        return $this;
    }
    
    public function getCreatedBy(): UserEntity
    {
        return $this->createdBy;
    }
    
    public function setCreatedBy(UserEntity $createdBy): self
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    public function setDeleted(bool $deleted): self
    {
        $this->deleted = $deleted;
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
            'description' => $this->getDescription(),
            'organization_id' => $this->getOrganization()->getId(),
            'created_by' => $this->getCreatedBy()->getId(),
            'deleted' => $this->isDeleted(),
            'updated' => $this->getUpdated()->format('Y-m-d H:i:s'),
            'created' => $this->getCreated()->format('Y-m-d H:i:s'),
        ];
        
        if ($this->getTeam()) {
            $data['team_id'] = $this->getTeam()->getId();
        }
        
        return $data;
    }
}