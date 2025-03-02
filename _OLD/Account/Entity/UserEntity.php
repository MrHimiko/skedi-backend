<?php

namespace App\Plugins\Account\Entity;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Doctrine\ORM\Mapping as ORM;

use DateTimeInterface;
use DateTime;

#[ORM\Entity(repositoryClass: "App\Plugins\Account\Repository\UserRepository")]
#[ORM\Table(name: "account_users")]
class UserEntity implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "user_id", type: "integer")]
    private int $id;

    #[ORM\Column(name: "partition", type: "integer", options: ["default" => 1])]
    private int $partition = 1;

    #[ORM\Column(name: "user_name", type: "string", length: 255)]
    private string $name;

    #[ORM\Column(name: "user_email", type: "string", length: 255, unique: true)]
    private string $email;

    #[ORM\Column(name: "user_password", type: "string", length: 255, nullable: true)]
    private ?string $password;

    #[ORM\Column(name: "user_updated", type: "datetime", nullable: true)]
    private ?DateTimeInterface $updated = null;

    #[ORM\Column(name: "user_created", type: "datetime", nullable: false, options: ["default" => "CURRENT_TIMESTAMP"])]
    private DateTimeInterface $created;

    #[ORM\ManyToOne(targetEntity: RoleEntity::class)]
    #[ORM\JoinColumn(name: "user_role_id", referencedColumnName: "role_id", nullable: false, options: ["default" => 1])]
    private RoleEntity $role;

    #[ORM\Column(name: "user_permissions", type: 'varchar_array')]
    private array $permissions = [];

    #[ORM\ManyToOne(targetEntity: OrganizationEntity::class, inversedBy: "users")]
    #[ORM\JoinColumn(name: "user_organization_id", referencedColumnName: "organization_id", nullable: false)]
    private OrganizationEntity $organization;

    #[ORM\Column(name: "user_extensions", type: "integer_array", nullable: true, options: ["default" => "[]"])]
    private ?array $extensions = [];

    #[ORM\Column(name: "user_deleted", type: "boolean", options: ["default" => false])]
    private bool $deleted = false;

    #[ORM\Column(name: "user_type", type: "string", length: 255, options: ["default" => "staff"])]
    private string $type = 'staff';

    #[ORM\Column(name: "user_recovery", type: "string", length: 255, nullable: true)]
    private ?string $recovery = null;

    public function __construct()
    {
        $this->updated = new DateTime(); 
        $this->created = new DateTime(); 
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

    public function getInitials(): string
    {
        $name = explode(' ' , $this->name);

        if(count($name) >= 2)
        {
            return $name[0][0] . '' . $name[1][0];
        }

        return $this->name[0];
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

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getUpdated(): ?DateTimeInterface
    {
        return $this->updated;
    }

    public function setUpdated(?DateTimeInterface $updated): self
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

    public function getRole(): RoleEntity
    {
        return $this->role;
    }

    public function setRole(RoleEntity $role): self
    {
        $this->role = $role;
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

    public function getOrganization(): OrganizationEntity
    {
        return $this->organization;
    }

    public function setOrganization(OrganizationEntity $organization): self
    {
        $this->organization = $organization;
        return $this;
    }

    public function getExtensions(): array
    {
        return $this->extensions;
    }

    public function setExtensions(?array $extensions): self
    {
        $this->extensions = $extensions;
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

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    /* Auth */

    public function getUserIdentifier(): string
    {
        return $this->email ?? 'guest';
    }

    public function getRoles(): array
    {
        return [$this->role->getName()];
    }

    public function eraseCredentials(): void
    {
        // Clear sensitive data if stored temporarily
    }

    public function getRecovery(): ?string
    {
        return $this->recovery;
    }

    public function setRecovery(?string $recovery): self
    {
        $this->recovery = $recovery;
        return $this;
    }

    public function toArray(bool $minimal = true): array
    {
        if($minimal)
        {
            return [
                'id'           => $this->getId(),
                'name'         => $this->getName(),
                'initials'     => $this->getInitials(),
                'email'        => $this->getEmail(),
                'type'         => $this->getType(),
                'organization' => $this->getOrganization()->toArray(),
                'extensions'   => $this->getExtensions()
            ];
        }

        return [
            'id'           => $this->getId(),
            'name'         => $this->getName(),
            'initials'     => $this->getInitials(),
            'email'        => $this->getEmail(),
            'role'         => $this->getRole()->toArray(),
            'permissions'  => $this->getPermissions(),
            'organization' => $this->getOrganization()->toArray(),
            'type'         => $this->getType(),
            'extensions'   => $this->getExtensions(),
            'updated'      => $this->getUpdated()?->format('Y-m-d H:i:s'),
            'created'      => $this->getCreated()->format('Y-m-d H:i:s'),
        ];
    }
}
