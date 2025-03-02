<?php

namespace App\Plugins\Account\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Plugins\Account\Repository\RoleRepository;

use DateTime;
use DateTimeInterface;

#[ORM\Entity(repositoryClass: RoleRepository::class)]
#[ORM\Table(name: "account_roles")]
class RoleEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "role_id", type: "integer")]
    private int $id;

    #[ORM\Column(name: "role_name", type: "string", length: 255)]
    private string $name;

    #[ORM\Column(name: "role_updated", type: "datetime", nullable: false, options: ["default" => "CURRENT_TIMESTAMP"])]
    private DateTimeInterface $updated;

    #[ORM\Column(name: "role_created", type: "datetime", nullable: false, options: ["default" => "CURRENT_TIMESTAMP"])]
    private DateTimeInterface $created;

    #[ORM\Column(name: "role_permissions", type: 'varchar_array')]
    private array $permissions = [];

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

    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function setPermissions(array $permissions): self
    {
        $this->permissions = $permissions;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'permissions' => $this->getPermissions(),
            'updated' => $this->getUpdated()->format('Y-m-d H:i:s'),
            'created' => $this->getCreated()->format('Y-m-d H:i:s'),
        ];
    }
}