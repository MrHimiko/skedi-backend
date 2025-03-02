<?php

namespace App\Plugins\Users\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

use App\Plugins\Users\Entity\ProspectEntity;

class ProspectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProspectEntity::class);
    }
}
