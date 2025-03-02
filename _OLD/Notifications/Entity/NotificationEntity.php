<?php

namespace App\Plugins\Notifications\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTime;
use DateTimeInterface;

#[ORM\Entity]
#[ORM\Table(name: "notifications")]
class NotificationEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "notification_id", type: "integer")]
    private int $id;

    #[ORM\Column(name: "notification_organizations", type: "json", nullable: true)]
    private ?array $organizations = null;

    #[ORM\Column(name: "notification_users", type: "json", nullable: true)]
    private ?array $users = null;

    #[ORM\Column(name: "notification_user_roles", type: "json", nullable: true)]
    private ?array $userRoles = null;

    #[ORM\Column(name: "notification_user_types", type: "json", nullable: true)]
    private ?array $userTypes = null;

    #[ORM\Column(name: "notification_billing_plans", type: "json", nullable: true)]
    private ?array $billingPlans = null;

    #[ORM\Column(name: "notification_group", type: "string", length: 255, nullable: true)]
    private ?string $group = null;

    #[ORM\Column(name: "notification_title", type: "string", length: 255, nullable: false)]
    private string $title;

    #[ORM\Column(name: "notification_description", type: "string", length: 1000, nullable: false)]
    private string $description;

    #[ORM\Column(name: "notification_link", type: "string", length: 255, nullable: true)]
    private ?string $link = null;

    #[ORM\Column(name: "notification_entity", type: "string", length: 255, nullable: true)]
    private ?string $entity = null;

    #[ORM\Column(name: "notification_identifier", type: "integer", nullable: true)]
    private ?int $identifier = null;

    #[ORM\Column(name: "notification_content", type: "json", nullable: true)]
    private ?array $content = null;

    #[ORM\Column(name: "notification_email", type: "boolean", nullable: true, options: ['default' => false])]
    private bool $email = false;

    #[ORM\Column(name: "notification_sms", type: "boolean", nullable: true, options: ['default' => false])]
    private bool $sms = false;

    #[ORM\Column(name: "notification_push", type: "boolean", nullable: true, options: ['default' => false])]
    private bool $push = false;

    #[ORM\Column(name: "notification_updated", type: "datetime", nullable: false, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private DateTimeInterface $updated;

    #[ORM\Column(name: "notification_created", type: "datetime", nullable: false, options: ['default' => 'CURRENT_TIMESTAMP'])]
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

    public function getOrganizations(): ?array
    {
        return $this->organizations;
    }

    public function setOrganizations(?array $organizations): self
    {
        $this->organizations = $organizations;
        return $this;
    }

    public function getUsers(): ?array
    {
        return $this->users;
    }

    public function setUsers(?array $users): self
    {
        $this->users = $users;
        return $this;
    }

    public function getUserRoles(): ?array
    {
        return $this->userRoles;
    }

    public function setUserRoles(?array $userRoles): self
    {
        $this->userRoles = $userRoles;
        return $this;
    }

    public function getUserTypes(): ?array
    {
        return $this->userTypes;
    }

    public function setUserTypes(?array $userTypes): self
    {
        $this->userTypes = $userTypes;
        return $this;
    }

    public function getBillingPlans(): ?array
    {
        return $this->billingPlans;
    }

    public function setBillingPlans(?array $billingPlans): self
    {
        $this->billingPlans = $billingPlans;
        return $this;
    }

    public function getGroup(): ?string
    {
        return $this->group;
    }

    public function setGroup(?string $group): self
    {
        $this->group = $group;
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

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function setLink(?string $link): self
    {
        $this->link = $link;
        return $this;
    }

    public function getEntity(): ?string
    {
        return $this->entity;
    }

    public function setEntity(?string $entity): self
    {
        $this->entity = $entity;
        return $this;
    }

    public function getIdentifier(): ?int
    {
        return $this->identifier;
    }

    public function setIdentifier(?int $identifier): self
    {
        $this->identifier = $identifier;
        return $this;
    }

    public function getContent(): ?array
    {
        return $this->content;
    }

    public function setContent(?array $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getEmail(): bool
    {
        return $this->email;
    }

    public function setEmail(bool $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getSms(): bool
    {
        return $this->sms;
    }

    public function setSms(bool $sms): self
    {
        $this->sms = $sms;
        return $this;
    }

    public function getPush(): bool
    {
        return $this->push;
    }

    public function setPush(bool $push): self
    {
        $this->push = $push;
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
            'group' => $this->getGroup(),
            'title' => $this->getTitle(),
            'description' => $this->getDescription(),
            'link' => $this->getLink(),
            'content' => $this->getContent(),
            'updated' => $this->getUpdated()->format('Y-m-d H:i:s'),
            'created' => $this->getCreated()->format('Y-m-d H:i:s'),
        ];
    }
}