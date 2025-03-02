<?php

namespace App\Plugins\Account\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Plugins\Billing\Entity\PlanEntity;

use App\Plugins\Account\Repository\OrganizationRepository;

use DateTime;
use DateTimeInterface;

#[ORM\Entity(repositoryClass: OrganizationRepository::class)]
#[ORM\Table(name: "account_organizations")]
class OrganizationEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "organization_id", type: "integer")]
    private int $id;

    #[ORM\Column(name: "partition", type: "integer", options: ["default" => 1])]
    private int $partition = 1;

    #[ORM\Column(name: "organization_name", type: "string", length: 255)]
    private string $name;

    #[ORM\Column(name: "organization_domain", type: "string", length: 255, unique: true)]
    private string $domain;

    #[ORM\Column(name: "organization_description", type: "string", length: 1000, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: "organization_website", type: "string", length: 255, nullable: true)]
    private ?string $website = null;

    #[ORM\Column(name: "organization_color", type: "string", length: 255, nullable: true)]
    private ?string $color = null;

    #[ORM\Column(name: "organization_updated", type: "datetime", nullable: false, options: ["default" => "CURRENT_TIMESTAMP"])]
    private DateTimeInterface $updated;

    #[ORM\Column(name: "organization_created", type: "datetime", nullable: false, options: ["default" => "CURRENT_TIMESTAMP"])]
    private DateTimeInterface $created;

    #[ORM\Column(name: "organization_deleted", type: "boolean", options: ["default" => false])]
    private bool $deleted = false;

    #[ORM\Column(name: "organization_extensions", type: "integer_array", nullable: true, options: ["default" => "[]"])]
    private ?array $extensions = [];

    #[ORM\ManyToOne(targetEntity: PlanEntity::class)]
    #[ORM\JoinColumn(name: "organization_plan_id", referencedColumnName: "plan_id", nullable: false, options: ["default" => 1])]
    private PlanEntity $plan;

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

    public function getPartition(): int
    {
        return $this->partition;
    }

    public function setPartition(int $partition): self
    {
        $this->partition = $partition;
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

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function setDomain(string $domain): self
    {
        $this->domain = $domain;
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

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): self
    {
        $this->website = $website;
        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): self
    {
        $this->color = $color;
        return $this;
    }

    public function getPlan(): PlanEntity
    {
        return $this->plan;
    }

    public function setPlan(PlanEntity $plan): self
    {
        $this->plan = $plan;
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

    public function getExtensions(): array
    {
        return $this->extensions;
    }

    public function setExtensions(?array $extensions): self
    {
        $this->extensions = $extensions;
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

    public function toArray(bool $minimal = true): array
    {
        if($minimal)
        {
            return [
                'id'          => $this->getId(),
                'name'        => $this->getName(),
                'domain'      => $this->getDomain(),
                'description' => $this->getDescription(),
                'website'     => $this->getWebsite(),
                'color'       => $this->getColor(),
                'extensions'  => $this->getExtensions(),
                'updated'     => $this->getUpdated()->format('Y-m-d H:i:s'),
                'created'     => $this->getCreated()->format('Y-m-d H:i:s'),
            ];
        }

        return [
            'id'          => $this->getId(),
            'name'        => $this->getName(),
            'domain'      => $this->getDomain(),
            'description' => $this->getDescription(),
            'website'     => $this->getWebsite(),
            'color'       => $this->getColor(),
            'plan'        => $this->getPlan()->toArray('id', 'name'),
            'extensions'  => $this->getExtensions(),
            'updated'     => $this->getUpdated()->format('Y-m-d H:i:s'),
            'created'     => $this->getCreated()->format('Y-m-d H:i:s'),
        ];
    }
}
