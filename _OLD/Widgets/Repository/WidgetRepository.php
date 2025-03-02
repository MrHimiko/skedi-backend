<?php

namespace App\Plugins\Widgets\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Plugins\Widgets\Entity\WidgetEntity;

class WidgetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WidgetEntity::class);
    }
}
