<?php

namespace App\Plugins\Extensions\Service;

use App\Service\CrudManager;
use App\Exception\CrudException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraints as Assert;

use App\Plugins\Extensions\Entity\ExtensionEntity;
use App\Plugins\Extensions\Exception\ExtensionsException;

class ExtensionService
{
    private CrudManager $crudManager;
    private EntityManagerInterface $entityManager;

    public function __construct(CrudManager $crudManager, EntityManagerInterface $entityManager)
    {
        $this->crudManager = $crudManager;
        $this->entityManager = $entityManager;
    }

    public function getMany(array $filters, int $page, int $limit, array $criteria = []): array
    {
        try 
        {
            return $this->crudManager->findMany(ExtensionEntity::class, $filters, $page, $limit, $criteria + [
                'deleted' => false
            ]);
        }
        catch (CrudException $e)
        {
            throw new ExtensionsException($e->getMessage());
        }
    }

    public function getOne(int $id, array $criteria = []): ?ExtensionEntity
    {
        return $this->crudManager->findOne(ExtensionEntity::class, $id, $criteria + [
            'deleted' => false
        ]);
    }
}
