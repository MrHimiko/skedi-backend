<?php

namespace App\Plugins\Storage\Service;

use Symfony\Component\Validator\Constraints as Assert;

use App\Plugins\Account\Entity\OrganizationEntity;
use App\Plugins\Storage\Entity\FolderEntity;

use App\Service\CrudManager;

class FolderService
{
    private CrudManager $crudManager;

    public function __construct(
        CrudManager $crudManager, 
    )
    {
        $this->crudManager = $crudManager;
    }

    public function getMany(OrganizationEntity $organization, array $filters, int $page, int $limit): array
    {
        return $this->crudManager->findMany(FolderEntity::class, $filters, $page, $limit, ['organization' => $organization, 'deleted' => false]);
    }

    public function getOne(OrganizationEntity $organization, int $id): ?FolderEntity
    {
        return $this->crudManager->findOne(FolderEntity::class, $id, [
            'deleted' => false,
            'organization' => $organization
        ]);
    }

    public function delete(FolderEntity $folder): void
    {
        $this->crudManager->delete($folder);
    }

    public function update(FolderEntity $folder, array $data = []): void
    {
        $this->crudManager->update($folder, $data, [
            'name' => new Assert\Optional([
                new Assert\Type('string'),
                new Assert\Length(['min' => 2, 'max' => 255]),
            ]),
        ]);
    }

    public function create(OrganizationEntity $organization, array $data = []): FolderEntity
    {
        $folder = new FolderEntity();
        $folder->setOrganization($organization);

        $this->crudManager->create($folder, $data, [
            'name' => [
                new Assert\Type('string'),
                new Assert\Length(['min' => 2, 'max' => 255])
            ]
        ]);

        return $folder;
    }
}