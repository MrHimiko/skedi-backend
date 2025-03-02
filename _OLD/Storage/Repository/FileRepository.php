<?php

    namespace App\Plugins\Storage\Repository;

    use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
    use Doctrine\Persistence\ManagerRegistry;

    use App\Plugins\Storage\Entity\FileEntity;
    use App\Plugins\Account\Entity\OrganizationEntity;

    class FileRepository extends ServiceEntityRepository
    {
        public function __construct(ManagerRegistry $registry)
        {
            parent::__construct($registry, FileEntity::class);
        }

        public function findOneByHash(OrganizationEntity $organization, string $hash): ?FileEntity
        {
            return $this->findOneBy([
                // 'partition' => $organization->getPartition(),
                'organization' => $organization, 
                'hash' => $hash
            ]);
        }

        public function findByIds(OrganizationEntity $organization, array $ids): array
        {
            $ids = array_map('intval', array_filter($ids, 'is_numeric'));

            if(empty($ids)) 
            {
                return [];
            }

            $results = $this->findBy([
                'organization' => $organization,
                'id' => $ids
            ]);

            $files = [];
            $indexedResults = [];

            foreach ($results as $item) 
            {
                $indexedResults[$item->getId()] = $item;
            }

            foreach($ids as $id)
            {
                if(array_key_exists($id, $indexedResults))
                {
                    $files[] = $indexedResults[$id];
                }
            }

            return $files;
        }
    }
