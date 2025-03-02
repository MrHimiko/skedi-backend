<?php

namespace App\Plugins\Account\Repository;

use App\Plugins\Account\Entity\OrganizationEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OrganizationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrganizationEntity::class);
    }

    public function findOneByDomain(string $domain): ?OrganizationEntity
    {
        return $this->findOneBy(['domain' => $domain]);
    }
}
