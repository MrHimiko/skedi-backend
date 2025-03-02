<?php

    namespace App\Plugins\Storage\Repository;

    use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
    use Doctrine\Persistence\ManagerRegistry;

    use App\Plugins\Storage\Entity\FolderEntity;

    class FolderRepository extends ServiceEntityRepository
    {
        public function __construct(ManagerRegistry $registry)
        {
            parent::__construct($registry, FolderEntity::class);
        }
    }
