<?php

namespace App\Plugins\Notes\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Plugins\Account\Entity\OrganizationEntity;
use App\Plugins\Account\Entity\UserEntity;
use App\Plugins\People\Entity\TenantEntity;
use App\Plugins\Activity\Entity\LogEntity;

use DateTimeInterface;
use DateTime;

#[ORM\Entity]
#[ORM\Table(name: "notes")]
class NoteEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "note_id", type: "bigint")]
    private int $id;

    #[ORM\Column(name: "partition", type: "integer", options: ["default" => 1])]
    private int $partition = 1;

    #[ORM\ManyToOne(targetEntity: OrganizationEntity::class)]
    #[ORM\JoinColumn(name: "note_organization_id", referencedColumnName: "organization_id", nullable: false)]
    private OrganizationEntity $organization;

    #[ORM\ManyToOne(targetEntity: UserEntity::class)]
    #[ORM\JoinColumn(name: "note_created_user_id", referencedColumnName: "user_id", nullable: false)]
    private UserEntity $createdUser;

    #[ORM\ManyToOne(targetEntity: UserEntity::class)]
    #[ORM\JoinColumn(name: "note_updated_user_id", referencedColumnName: "user_id", nullable: true)]
    private ?UserEntity $updatedUser = null;

    #[ORM\ManyToOne(targetEntity: TenantEntity::class)]
    #[ORM\JoinColumn(name: "note_tenant_id", referencedColumnName: "tenant_id", nullable: true)]
    private ?TenantEntity $tenant = null;

    #[ORM\ManyToOne(targetEntity: TenantEntity::class)]
    #[ORM\JoinColumn(name: "note_prospect_id", referencedColumnName: "tenant_id", nullable: true)]
    private ?TenantEntity $prospect = null;

    #[ORM\Column(name: "note_title", type: "string", length: 255, nullable: false)]
    private string $title;

    #[ORM\Column(name: "note_content", type: "text", nullable: true)]
    private ?string $content = null;

    #[ORM\Column(name: "note_tags", type: "integer_array", nullable: true, options: ["default" => "[]"])]
    private ?array $tags = [];

    #[ORM\Column(name: "note_files", type: "integer_array", nullable: true, options: ["default" => "[]"])]
    private ?array $files = [];

    #[ORM\Column(name: "note_deleted", type: "boolean", options: ["default" => false])]
    private bool $deleted = false;

    #[ORM\Column(name: "note_updated", type: "datetime", nullable: false, options: ["default" => "CURRENT_TIMESTAMP"])]
    private DateTimeInterface $updated;

    #[ORM\Column(name: "note_created", type: "datetime", nullable: false, options: ["default" => "CURRENT_TIMESTAMP"])]
    private DateTimeInterface $created;

    public function __construct()
    {
        $this->updated = new DateTime();
        $this->created = new DateTime();
    }

    public function onLog(LogEntity $log, &$data)
    {
        $log->setNote($this);
    }

    public function getLogName()
    {
        return $this->getTitle();
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

    public function getCreatedUser(): UserEntity
    {
        return $this->createdUser;
    }

    public function setCreatedUser(UserEntity $createdUser): self
    {
        $this->createdUser = $createdUser;
        return $this;
    }

    public function getUpdatedUser(): ?UserEntity
    {
        return $this->updatedUser;
    }

    public function setUpdatedUser(?UserEntity $updatedUser): self
    {
        $this->updatedUser = $updatedUser;
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

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getName(): string
    {
        return $this->title;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getTags(): ?array
    {
        return $this->tags;
    }

    public function setTags(?array $tags): self
    {
        $this->tags = $tags;
        return $this;
    }

    public function getFiles(): array
    {
        return $this->files;
    }

    public function setFiles(?array $files): self
    {
        $this->files = $files;
        return $this;
    }

    public function getDeleted(): bool
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

    public function setUpdated(DateTimeInterface $updated): self
    {
        $this->updated = $updated;
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

    public function toArray(): array
    {
        return [
            'id'          => $this->getId(),
            'createdUser' => $this->getCreatedUser()->toArray(),
            'updatedUser' => $this->getUpdatedUser()?->toArray(),
            'title'       => $this->getTitle(),
            'content'     => $this->getContent(),
            'tags'        => $this->getTags(),
            'files'       => $this->getFiles(),
            'updated'     => $this->getUpdated()->format('Y-m-d H:i:s'),
            'created'     => $this->getCreated()->format('Y-m-d H:i:s'),
        ];
    }
}
