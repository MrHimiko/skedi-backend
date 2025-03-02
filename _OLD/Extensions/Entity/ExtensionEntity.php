<?php

namespace App\Plugins\Extensions\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTimeInterface;
use DateTime;

#[ORM\Entity]
#[ORM\Table(name: "extensions")]
class ExtensionEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "extension_id", type: "bigint")]
    private int $id;

    #[ORM\Column(name: "extension_name", type: "string", length: 255, nullable: false)]
    private string $name;

    #[ORM\Column(name: "extension_description", type: "string", length: 255, nullable: false)]
    private string $description;

    #[ORM\Column(name: "extension_icon", type: "string", length: 255, nullable: false)]
    private string $icon;

    #[ORM\Column(name: "extension_price", type: "float", nullable: false, options: ["default" => 0])]
    private float $price = 0.0;

    #[ORM\Column(name: "extension_deleted", type: "boolean", options: ["default" => false])]
    private bool $deleted = false;

    #[ORM\Column(name: "extension_panel", type: "string", length: 255, nullable: false)]
    private string $panel;

    #[ORM\Column(name: "extension_updated", type: "datetime", nullable: false, options: ["default" => "CURRENT_TIMESTAMP"])]
    private DateTimeInterface $updated;

    #[ORM\Column(name: "extension_created", type: "datetime", nullable: false, options: ["default" => "CURRENT_TIMESTAMP"])]
    private DateTimeInterface $created;

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

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function setIcon(string $icon): self
    {
        $this->icon = $icon;
        return $this;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;
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

    public function getPanel(): string
    {
        return $this->panel;
    }

    public function setPanel(string $panel): self
    {
        $this->panel = $panel;
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
            'name'        => $this->getName(),
            'description' => $this->getDescription(),
            'icon'        => $this->getIcon(),
            'price'       => $this->getPrice(),
            'panel'       => $this->getPanel(),
            'updated'     => $this->getUpdated()->format('Y-m-d H:i:s'),
            'created'     => $this->getCreated()->format('Y-m-d H:i:s'),
        ];
    }
}