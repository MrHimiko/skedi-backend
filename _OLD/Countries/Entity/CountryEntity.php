<?php

namespace App\Plugins\Countries\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTime;
use DateTimeInterface;

#[ORM\Entity]
#[ORM\Table(name: "countries")]
class CountryEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "country_id", type: "bigint")]
    private int $id;

    #[ORM\Column(name: "country_icon", type: "bigint", nullable: true)]
    private ?int $icon;

    #[ORM\Column(name: "country_name", type: "string", length: 255, nullable: false)]
    private string $name;

    #[ORM\Column(name: "country_code", type: "string", length: 2, nullable: false)]
    private string $code;

    #[ORM\Column(name: "country_phone", type: "string", length: 5, nullable: true)]
    private ?string $phone;

    #[ORM\Column(name: "country_updated", type: "datetime", nullable: false, options: ["default" => "CURRENT_TIMESTAMP"])]
    private DateTimeInterface $updated;

    #[ORM\Column(name: "country_created", type: "datetime", nullable: false, options: ["default" => "CURRENT_TIMESTAMP"])]
    private DateTimeInterface $created;

    public function __construct()
    {
        $this->created = new DateTime();
        $this->updated = new DateTime();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getIcon(): ?int
    {
        return $this->icon;
    }

    public function setIcon(?int $icon): self
    {
        $this->icon = $icon;
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

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;
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

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'icon' => $this->getIcon(),
            'name' => $this->getName(),
            'code' => $this->getCode(),
            'phone' => $this->getPhone(),
            'created' => $this->getCreated()->format('Y-m-d H:i:s'),
            'updated' => $this->getUpdated()->format('Y-m-d H:i:s'),
        ];
    }
}
