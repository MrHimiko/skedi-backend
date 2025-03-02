<?php

namespace App\Plugins\Users\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Plugins\Account\Entity\UserEntity;
use App\Plugins\Users\Repository\VendorRepository;
use DateTimeInterface;

#[ORM\Entity(repositoryClass: VendorRepository::class)]
#[ORM\Table(name: "users_vendors")]
class VendorEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "vendor_id", type: "integer")]
    private int $id;

    #[ORM\ManyToOne(targetEntity: UserEntity::class)]
    #[ORM\JoinColumn(name: "vendor_user_id", referencedColumnName: "id", nullable: false)]
    private UserEntity $user;

    #[ORM\Column(type: 'datetime', nullable: false, options: ['default' => 'CURRENT_TIMESTAMP'], name: 'token_updated')]
    private DateTimeInterface $updated; 

    #[ORM\Column(type: 'datetime', nullable: false, options: ['default' => 'CURRENT_TIMESTAMP'], name: 'token_created')]
    private DateTimeInterface $created;

    public function getId(): int
    {
        return $this->id;
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
            'user' => $this->getUser()->toArray(),
            'updated' => $this->getUpdated()->format('Y-m-d H:i:s'),
            'created' => $this->getCreated()->format('Y-m-d H:i:s')
        ];
    }
}
