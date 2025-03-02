<?php

namespace App\Plugins\Users\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

use App\Plugins\Users\Entity\OwnerEntity;

class OwnerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OwnerEntity::class);
    }
}
