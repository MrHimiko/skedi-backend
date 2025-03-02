<?php

namespace App\Plugins\Widgets\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Plugins\Account\Entity\OrganizationEntity;
use App\Plugins\Account\Entity\UserEntity;
use App\Plugins\Widgets\Entity\TabEntity;

use DateTimeInterface;
use DateTime;

#[ORM\Entity]
#[ORM\Table(name: "widgets_items")]
class ItemEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "item_id", type: "bigint")]
    private int $id;

    #[ORM\ManyToOne(targetEntity: OrganizationEntity::class)]
    #[ORM\JoinColumn(name: "item_organization_id", referencedColumnName: "organization_id", nullable: false)]
    private OrganizationEntity $organization;

    #[ORM\ManyToOne(targetEntity: UserEntity::class)]
    #[ORM\JoinColumn(name: "item_user_id", referencedColumnName: "user_id", nullable: false)]
    private UserEntity $user;

    #[ORM\ManyToOne(targetEntity: TabEntity::class)]
    #[ORM\JoinColumn(name: "item_tab_id", referencedColumnName: "tab_id", nullable: true)]
    private ?TabEntity $tab = null;

    #[ORM\Column(name: "item_name", type: "string", length: 255, nullable: false)]
    private string $name;

    #[ORM\Column(name: "item_title", type: "string", length: 255, nullable: false)]
    private string $title;

    #[ORM\Column(name: "item_description", type: "string", length: 1000, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: "item_order", type: "integer", nullable: false, options: ["default" => 1])]
    private int $order = 1;

    #[ORM\Column(name: "item_width", type: "integer", nullable: false, options: ["default" => 4])]
    private int $width = 4;

    #[ORM\Column(type: "datetime", nullable: false, options: ["default" => "CURRENT_TIMESTAMP"], name: "widget_updated")]
    private DateTimeInterface $updated;

    #[ORM\Column(type: "datetime", nullable: false, options: ["default" => "CURRENT_TIMESTAMP"], name: "widget_created")]
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

    public function getTab(): ?TabEntity
    {
        return $this->tab;
    }

    public function setTab(?TabEntity $tab): self
    {
        $this->tab = $tab;
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

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
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

    public function getOrder(): int
    {
        return $this->order;
    }

    public function setOrder(int $order): self
    {
        $this->order = $order;
        return $this;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function setWidth(int $width): self
    {
        $this->width = $width;
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
            'tab' => $this->getTab()?->toArray(),
            'name' => $this->getName(),
            'title' => $this->getTitle(),
            'description' => $this->getDescription(),
            'order' => $this->getOrder(),
            'width' => $this->getWidth(),
            'updated' => $this->getUpdated()->format('Y-m-d H:i:s'),
            'created' => $this->getCreated()->format('Y-m-d H:i:s')
        ];
    }
}
