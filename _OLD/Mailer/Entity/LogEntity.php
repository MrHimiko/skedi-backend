<?php

namespace App\Plugins\Mailer\Entity;

use App\Plugins\Mailer\Repository\LogRepository;
use App\Plugins\Account\Entity\OrganizationEntity;
use App\Plugins\Account\Entity\UserEntity;

use Doctrine\ORM\Mapping as ORM;

use DateTime;
use DateTimeInterface;

#[ORM\Entity(repositoryClass: LogRepository::class)]
#[ORM\Table(name: "mailer_logs")]
class LogEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "log_id", type: "integer")]
    private int $id;

    #[ORM\ManyToOne(targetEntity: OrganizationEntity::class)]
    #[ORM\JoinColumn(name: "log_organization_id", referencedColumnName: "organization_id", nullable: false)]
    private OrganizationEntity $organization;

    #[ORM\ManyToOne(targetEntity: UserEntity::class)]
    #[ORM\JoinColumn(name: "log_user_id", referencedColumnName: "user_id", nullable: true)]
    private ?UserEntity $user = null;

    #[ORM\Column(name: "log_email", type: "string", length: 255)]
    private string $email;

    #[ORM\Column(name: "log_template", type: "string", length: 255)]
    private string $template;

    #[ORM\Column(name: "log_data", type: "json", options: ["default" => "{}"])]
    private object $data;

    #[ORM\Column(name: "log_created", type: "datetime", nullable: false, options: ["default" => "CURRENT_TIMESTAMP"])]
    private DateTimeInterface $created;

    #[ORM\Column(name: "log_updated", type: "datetime", nullable: false, options: ["default" => "CURRENT_TIMESTAMP"])]
    private DateTimeInterface $updated;

    public function __construct()
    {
        $this->created = new DateTime();
        $this->updated = new DateTime();

        $this->data = new \stdClass();
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

    public function getUser(): ?UserEntity
    {
        return $this->user;
    }

    public function setUser(?UserEntity $user): self
    {
        $this->user = $user;
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

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function setTemplate(string $template): self
    {
        $this->template = $template;
        return $this;
    }

    public function getData(): object
    {
        return $this->data;
    }

    public function setData(object $data): self
    {
        $this->data = $data;
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

    public function toArray(UserEntity $user): array
    {
        return [
            'id' => $user->getId(),
            'organization' => $user->getOrganization()->getId(),
            'user' => $user->getUser()?->getId(),
            'email' => $user->getEmail(),
            'template' => $user->getTemplate(),
            'data' => $user->getData(),
            'created' => $user->getCreated()->format('Y-m-d H:i:s'),
            'updated' => $user->getUpdated()->format('Y-m-d H:i:s'),
        ];
    }
}