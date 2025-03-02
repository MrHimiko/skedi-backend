<?php

namespace App\Plugins\Activity\Entity;

use App\Plugins\Activity\Repository\LogRepository;
use App\Plugins\Account\Entity\OrganizationEntity;
use App\Plugins\Account\Entity\UserEntity;

use App\Plugins\People\Entity\TenantEntity;
use App\Plugins\Notes\Entity\NoteEntity;

use Doctrine\ORM\Mapping as ORM;

use DateTime;
use DateTimeInterface;

#[ORM\Entity(repositoryClass: LogRepository::class)]
#[ORM\Table(name: "activity_logs")]
class LogEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "log_id", type: "integer")]
    private int $id;

    #[ORM\Column(name: "partition", type: "integer", options: ["default" => 1])]
    private int $partition = 1;

    #[ORM\ManyToOne(targetEntity: OrganizationEntity::class)]
    #[ORM\JoinColumn(name: "log_organization_id", referencedColumnName: "organization_id", nullable: false)]
    private OrganizationEntity $organization;

    #[ORM\ManyToOne(targetEntity: UserEntity::class)]
    #[ORM\JoinColumn(name: "log_user_id", referencedColumnName: "user_id", nullable: true)]
    private ?UserEntity $user = null;

    #[ORM\ManyToOne(targetEntity: TenantEntity::class)]
    #[ORM\JoinColumn(name: "log_tenant_id", referencedColumnName: "tenant_id", nullable: true)]
    private ?TenantEntity $tenant = null;

    #[ORM\ManyToOne(targetEntity: TenantEntity::class)]
    #[ORM\JoinColumn(name: "log_prospect_id", referencedColumnName: "tenant_id", nullable: true)]
    private ?TenantEntity $prospect = null;

    #[ORM\ManyToOne(targetEntity: NoteEntity::class)]
    #[ORM\JoinColumn(name: "log_note_id", referencedColumnName: "note_id", nullable: true)]
    private ?NoteEntity $note = null;

    #[ORM\Column(name: "log_identifier", type: "bigint", nullable: false)]
    private int $identifier;

    #[ORM\Column(name: "log_entity", type: "string", length: 255, nullable: false)]
    private string $entity;

    #[ORM\Column(name: "log_data", type: "json", nullable: true, options: ["default" => "{}"])]
    private ?array $data = null;

    #[ORM\Column(name: "log_created", type: "datetime", nullable: false, options: ["default" => "CURRENT_TIMESTAMP"])]
    private DateTimeInterface $created;

    #[ORM\Column(name: "log_updated", type: "datetime", nullable: false, options: ["default" => "CURRENT_TIMESTAMP"])]
    private DateTimeInterface $updated;

    public function __construct()
    {
        $this->created = new DateTime();
        $this->updated = new DateTime();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPartition(): int
    {
        return $this->partition;
    }

    public function setPartition(int $partition): self
    {
        $this->partition = $partition;
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

    public function getUser(): ?UserEntity
    {
        return $this->user;
    }

    public function setUser(?UserEntity $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getTenant(): ?TenantEntity
    {
        return $this->tenant;
    }

    public function setTenant(?TenantEntity $tenant): self
    {
        $this->tenant = $tenant;
        return $this;
    }

    public function getProspect(): ?TenantEntity
    {
        return $this->prospect;
    }

    public function setProspect(?TenantEntity $prospect): self
    {
        $this->prospect = $prospect;
        return $this;
    }

    public function getNote(): ?NoteEntity
    {
        return $this->note;
    }

    public function setNote(?NoteEntity $note): self
    {
        $this->note = $note;
        return $this;
    }

    public function getIdentifier(): int
    {
        return $this->identifier;
    }

    public function setIdentifier(int $identifier): self
    {
        $this->identifier = $identifier;
        return $this;
    }

    public function getEntity(): string
    {
        return $this->entity;
    }

    public function setEntity(string $entity): self
    {
        $this->entity = $entity;
        return $this;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function setData(?array $data): self
    {
        $this->data = $data;
        return $this;
    }

    public function getCreated(): DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(DateTimeInterface $created): self
    {
        $this->created = $created;
        return $this;
    }

    public function getUpdated(): DateTimeInterface
    {
        return $this->updated;
    }

    public function setUpdated(DateTimeInterface $updated): self
    {
        $this->updated = $updated;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'id'           => $this->getId(),
            'organization' => $this->getOrganization()->getId(),
            'user'         => $this->getUser()?->toArray(),
            'identifier'   => $this->getIdentifier(),
            'entity'       => $this->getEntity(),
            'data'         => $this->getData(),
            'created'      => $this->getCreated()->format('Y-m-d H:i:s'),
            'updated'      => $this->getUpdated()->format('Y-m-d H:i:s'),
        ];
    }
}