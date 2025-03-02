<?php

namespace App\Plugins\Storage\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Plugins\Storage\Repository\FolderRepository;
use App\Plugins\Account\Entity\OrganizationEntity;
use App\Plugins\Storage\Entity\FileEntity;

use DateTimeInterface;
use DateTime;

#[ORM\Entity(repositoryClass: FolderRepository::class)]
#[ORM\Table(name: "storage_folders")]
class FolderEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "folder_id", type: "integer")]
    private int $id;

    #[ORM\Column(name: "folder_name", type: "string", length: 255)]
    private string $name;

    #[ORM\ManyToOne(targetEntity: OrganizationEntity::class)]
    #[ORM\JoinColumn(name: "folder_organization_id", referencedColumnName: "organization_id", nullable: false)]
    private OrganizationEntity $organization;

    #[ORM\Column(name: "folder_size", type: "integer", options: ["default" => 0])]
    private int $size = 0;

    #[ORM\Column(name: "folder_files", type: "integer", options: ["default" => 0])]
    private int $files = 0;

    #[ORM\Column(name: "folder_created", type: "datetime", nullable: false, options: ["default" => "CURRENT_TIMESTAMP"])]
    private DateTimeInterface $created;

    #[ORM\Column(name: "folder_updated", type: "datetime", nullable: false, options: ["default" => "CURRENT_TIMESTAMP"])]
    private DateTimeInterface $updated;

    #[ORM\Column(name: "folder_deleted", type: "boolean", options: ["default" => false])]
    private bool $deleted = false;

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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
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

    public function getSize(): int
    {
        return $this->size;
    }

    public function setSize(int $size): self
    {
        $this->size = $size;
        return $this;
    }

    public function getFiles(): int
    {
        return $this->files;
    }

    public function setFiles(int $files): self
    {
        $this->files = $files;
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

    public function toArray(): array
    {
        return [
            'id'           => $this->getId(),
            'name'         => $this->getName(),
            'organization' => $this->getOrganization()->getId(),
            'size'         => $this->getSize(),
            'files'        => $this->getFiles(),
            'created'      => $this->getCreated()->format('Y-m-d H:i:s'),
            'updated'      => $this->getUpdated()->format('Y-m-d H:i:s'),
            'deleted'      => $this->isDeleted()
        ];
    }
}