<?php

namespace App\Plugins\Activity\Entity;

use App\Plugins\Activity\Repository\CommentRepository;
use App\Plugins\Account\Entity\OrganizationEntity;
use App\Plugins\Account\Entity\UserEntity;

use App\Plugins\People\Entity\TenantEntity;
use App\Plugins\Notes\Entity\NoteEntity;

use Doctrine\ORM\Mapping as ORM;

use DateTime;
use DateTimeInterface;

#[ORM\Entity(repositoryClass: CommentRepository::class)]
#[ORM\Table(name: "activity_comments")]
class CommentEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "comment_id", type: "integer")]
    private int $id;

    #[ORM\Column(name: "partition", type: "integer", options: ["default" => 1])]
    private int $partition = 1;

    #[ORM\ManyToOne(targetEntity: OrganizationEntity::class)]
    #[ORM\JoinColumn(name: "comment_organization_id", referencedColumnName: "organization_id", nullable: false)]
    private OrganizationEntity $organization;

    #[ORM\ManyToOne(targetEntity: UserEntity::class)]
    #[ORM\JoinColumn(name: "comment_user_id", referencedColumnName: "user_id", nullable: true)]
    private ?UserEntity $user = null;

    #[ORM\Column(name: "comment_message", type: "text", nullable: false)]
    private string $message;

    #[ORM\Column(name: "comment_files", type: "integer_array", nullable: true, options: ["default" => "[]"])]
    private ?array $files = [];

    #[ORM\ManyToOne(targetEntity: TenantEntity::class)]
    #[ORM\JoinColumn(name: "comment_tenant_id", referencedColumnName: "tenant_id", nullable: true)]
    private ?TenantEntity $tenant = null;

    #[ORM\ManyToOne(targetEntity: TenantEntity::class)]
    #[ORM\JoinColumn(name: "comment_prospect_id", referencedColumnName: "tenant_id", nullable: true)]
    private ?TenantEntity $prospect = null;

    #[ORM\ManyToOne(targetEntity: NoteEntity::class)]
    #[ORM\JoinColumn(name: "comment_note_id", referencedColumnName: "note_id", nullable: true)]
    private ?NoteEntity $note = null;

    #[ORM\Column(name: "comment_created", type: "datetime", nullable: false, options: ["default" => "CURRENT_TIMESTAMP"])]
    private DateTimeInterface $created;

    #[ORM\Column(name: "comment_updated", type: "datetime", nullable: false, options: ["default" => "CURRENT_TIMESTAMP"])]
    private DateTimeInterface $updated;

    public bool $log = true;

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

    public function getName()
    {
        return substr($this->message, 0, 500);
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;
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
            'message'      => $this->getMessage(),
            'user'         => $this->getUser()?->toArray(true),
            'files'        => $this->getFiles(),
            'created'      => $this->getCreated()->format('Y-m-d H:i:s'),
            'updated'      => $this->getUpdated()->format('Y-m-d H:i:s'),
        ];
    }
}
