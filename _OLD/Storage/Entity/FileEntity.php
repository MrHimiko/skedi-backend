<?php

namespace App\Plugins\Storage\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Plugins\Storage\Repository\FileRepository;
use App\Plugins\Storage\Entity\FolderEntity;
use App\Plugins\Account\Entity\OrganizationEntity;
use App\Plugins\People\Entity\TenantEntity;
use App\Plugins\Notes\Entity\NoteEntity;

use DateTimeInterface;
use DateTime;

#[ORM\Entity(repositoryClass: FileRepository::class)]
#[ORM\Table(name: "storage_files")]
class FileEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "file_id", type: "integer")]
    private int $id;

    #[ORM\Column(name: "file_name", type: "string", length: 255)]
    private string $name;

    #[ORM\Column(name: "file_hash", type: "string", length: 255)]
    private string $hash;

    #[ORM\Column(name: "file_size", type: "integer")]
    private int $size;

    #[ORM\Column(name: "file_type", type: "string", length: 50)]
    private string $type;

    #[ORM\Column(name: "file_extension", type: "string", length: 10)]
    private string $extension;

    #[ORM\ManyToOne(targetEntity: FolderEntity::class, inversedBy: "files")]
    #[ORM\JoinColumn(name: "file_folder_id", referencedColumnName: "folder_id", nullable: true)]
    private ?FolderEntity $folder = null;

    #[ORM\ManyToOne(targetEntity: OrganizationEntity::class)]
    #[ORM\JoinColumn(name: "file_organization_id", referencedColumnName: "organization_id", nullable: false)]
    private OrganizationEntity $organization;

    #[ORM\ManyToOne(targetEntity: TenantEntity::class)]
    #[ORM\JoinColumn(name: "file_tenant_id", referencedColumnName: "tenant_id", nullable: true)]
    private ?TenantEntity $tenant = null;

    #[ORM\ManyToOne(targetEntity: TenantEntity::class)]
    #[ORM\JoinColumn(name: "file_prospect_id", referencedColumnName: "tenant_id", nullable: true)]
    private ?TenantEntity $prospect = null;

    #[ORM\ManyToOne(targetEntity: NoteEntity::class)]
    #[ORM\JoinColumn(name: "file_note_id", referencedColumnName: "note_id", nullable: true)]
    private ?NoteEntity $note = null;

    #[ORM\Column(name: "file_updated", type: "datetime", nullable: false, options: ["default" => "CURRENT_TIMESTAMP"])]
    private DateTimeInterface $updated;

    #[ORM\Column(name: "file_created", type: "datetime", nullable: false, options: ["default" => "CURRENT_TIMESTAMP"])]
    private DateTimeInterface $created;

    #[ORM\Column(name: "file_deleted", type: "boolean", options: ["default" => false])]
    private bool $deleted = false;

    public bool $log = true;

    public function __construct()
    {
        $this->updated = new DateTime();
        $this->created = new DateTime();
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

    public function getHash(): string
    {
        return $this->hash;
    }

    public function setHash(string $hash): self
    {
        $this->hash = $hash;
        return $this;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function setSize(int $size): self
    {
        $this->size = $size;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getExtension(): string
    {
        return $this->extension;
    }

    public function setExtension(string $extension): self
    {
        $this->extension = $extension;
        return $this;
    }

    public function getFolder(): ?Folder
    {
        return $this->folder;
    }

    public function setFolder(?Folder $folder): self
    {
        $this->folder = $folder;
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

    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    public function setDeleted(bool $deleted): self
    {
        $this->deleted = $deleted;
        return $this;
    }

    public function getLink(): string
    {
        return 'https://storage.rentsera.com/' . $this->hash . '.' . $this->extension;
    }

    public function toArray(bool $minimal = false): array
    {
        if($minimal)
        {
            return [
                'id' => $this->getId(),
                'name' => $this->getName(),
                'hash' => $this->getHash(),
                'size' => $this->getSize(),
                'type' => $this->getType(),
                'extension' => $this->getExtension(),
                'folder' => $this->getFolder()?->toArray(true),
                'link' => $this->getLink(),
            ];
        }
        
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'hash' => $this->getHash(),
            'size' => $this->getSize(),
            'type' => $this->getType(),
            'extension' => $this->getExtension(),
            'folder' => $this->getFolder()?->toArray(),
            'organization' => $this->getOrganization()->toArray(),
            'link' => $this->getLink(),
            'created' => $this->getCreated()->format('Y-m-d H:i:s'),
            'updated' => $this->getUpdated()->format('Y-m-d H:i:s'),
            'deleted' => $this->isDeleted()
        ];
    }
}