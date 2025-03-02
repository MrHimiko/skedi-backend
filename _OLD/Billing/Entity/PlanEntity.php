<?php

namespace App\Plugins\Billing\Entity;

use App\Plugins\Billing\Repository\PlanRepository;
use Doctrine\ORM\Mapping as ORM;

use DateTime;
use DateTimeInterface;

#[ORM\Entity(repositoryClass: PlanRepository::class)]
#[ORM\Table(name: "billing_plans")]
class PlanEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "plan_id", type: "integer")]
    private int $id;

    #[ORM\Column(name: "plan_name", type: "string", length: 255)]
    private string $name;

    #[ORM\Column(name: "plan_description", type: "string", length: 2000)]
    private string $description;

    #[ORM\Column(name: "plan_price", type: "float")]
    private float $price;

    #[ORM\Column(name: "plan_features", type: "json")]
    private array $features = [];

    #[ORM\Column(name: "plan_limits", type: "json")]
    private array $limits = [];

    #[ORM\Column(name: "plan_created", type: "datetime", nullable: false, options: ["default" => "CURRENT_TIMESTAMP"])]
    private DateTimeInterface $created;

    #[ORM\Column(name: "plan_updated", type: "datetime", nullable: false, options: ["default" => "CURRENT_TIMESTAMP"])]
    private DateTimeInterface $updated;

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

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
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

    public function getFeatures(): array
    {
        return $this->features;
    }

    public function setFeatures(array $features): self
    {
        $this->features = $features;
        return $this;
    }

    public function getLimits(): array
    {
        return $this->limits;
    }

    public function setLimits(array $limits): self
    {
        $this->limits = $limits;
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

    public function toArray(...$keys): array
    {
        return array_filter([
            'id' => $this->getId(),
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'price' => $this->getPrice(),
            'features' => $this->getFeatures(),
            'limits' => $this->getLimits(),
            'created' => $this->getCreated()->format('Y-m-d H:i:s'),
            'updated' => $this->getUpdated()->format('Y-m-d H:i:s'),
        ], fn($key) => empty($keys) || in_array($key, $keys), ARRAY_FILTER_USE_KEY);
    }
}
