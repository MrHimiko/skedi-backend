<?php

namespace App\Plugins\Widgets\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Plugins\Account\Entity\OrganizationEntity;
use App\Plugins\Account\Entity\UserEntity;

use DateTimeInterface;
use DateTime;

#[ORM\Entity]
#[ORM\Table(name: "widgets_tabs")]
class TabEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "tab_id", type: "integer")]
    private int $id;

    #[ORM\ManyToOne(targetEntity: OrganizationEntity::class)]
    #[ORM\JoinColumn(name: "tab_organization_id", referencedColumnName: "organization_id", nullable: false)]
    private OrganizationEntity $organization;

    #[ORM\ManyToOne(targetEntity: UserEntity::class)]
    #[ORM\JoinColumn(name: "tab_user_id", referencedColumnName: "user_id", nullable: false)]
    private UserEntity $user;

    #[ORM\Column(name: "tab_name", type: "string", length: 255, nullable: false)]
    private string $name;

    #[ORM\Column(name: "tab_order", type: "integer", nullable: false)]
    private int $order;

    #[ORM\Column(type: 'datetime', nullable: false, options: ['default' => 'CURRENT_TIMESTAMP'], name: 'tab_updated')]
    private DateTimeInterface $updated;

    #[ORM\Column(type: 'datetime', nullable: false, options: ['default' => 'CURRENT_TIMESTAMP'], name: 'tab_created')]
    private DateTimeInterface $created;

    public bool $log = true;

    public function __construct()
    {
        $this->updated = new DateTime();
        $this->created = new DateTime();
        $this->order = 1;
    }

    public function getId(): int
    {
        return $this->id;
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

    public function getUser(): UserEntity
    {
        return $this->user;
    }

    public function setUser(UserEntity $user): self
    {
        $this->user = $user;
        return $this;
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

    public function getOrder(): int
    {
        return $this->order;
    }

    public function setOrder(int $order): self
    {
        $this->order = $order;
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
            'id' => $this->getId(),
            'name' => $this->getName(),
            'order' => $this->getOrder(),
            'updated' => $this->getUpdated()->format('Y-m-d H:i:s'),
            'created' => $this->getCreated()->format('Y-m-d H:i:s')
        ];
    }
}
