<?php

namespace App\Plugins\Storage\Service;

use Symfony\Component\Validator\Constraints as Assert;

use App\Plugins\Account\Entity\OrganizationEntity;
use App\Plugins\Storage\Entity\FileEntity;
use App\Plugins\Storage\Entity\FolderEntity;
use App\Plugins\Storage\Repository\FileRepository;

use App\Service\CrudManager;

class FileService
{
    private CrudManager $crudManager;
    private FileRepository $fileRepository;

    public function __construct(
        CrudManager $crudManager, 
        FileRepository $fileRepository, 
    )
    {
        $this->crudManager = $crudManager;
        $this->fileRepository = $fileRepository;
    }

    public function getMany(OrganizationEntity $organization, array $filters, int $page, int $limit, array $criteria = [],): array
    {
        return $this->crudManager->findMany(FileEntity::class, $filters, $page, $limit, $criteria + [
            'deleted'      => false,
            'organization' => $organization, 
        ]);
    }

    public function getOne(OrganizationEntity $organization, int $id, array $criteria = []): ?FileEntity
    {
        return $this->crudManager->findOne(FileEntity::class, $id, $criteria + [
            'deleted'      => false,
            'organization' => $organization
        ]);
    }

    public function delete(FileEntity $file): void
    {
        $this->crudManager->delete($file);
    }

    public function update(FileEntity $file, array $data = [], ?FolderEntity $folder = null): void
    {
        $this->crudManager->update($file, $data, [
            'name' => new Assert\Optional([
                new Assert\Type('string'),
                new Assert\Length(['min' => 2, 'max' => 255]),
            ])
        ]);
    }

    public function create(OrganizationEntity $organization, array $data = [], ?FolderEntity $folder = null, ?callable $callback = null): FileEntity
    {
        $file = new FileEntity();

        $file->setOrganization($organization);
        $file->setFolder($folder);

        if($callback)
        {
            $callback($file);
        }

        $this->crudManager->create($file, $data, [
            'name' => [
                new Assert\Type('string'),
                new Assert\Length(['min' => 2, 'max' => 255]),
            ],
            'hash' => [
                new Assert\Type('string'),
                new Assert\Length(['min' => 64, 'max' => 64]), 
            ],
            'size' => [
                new Assert\Type('integer'),
                new Assert\Positive(),
                new Assert\Range(['max' => 10 * 1024 * 1024]),
            ],
            'type' =>[
                new Assert\Type('string'),
                new Assert\Length(['max' => 255]), 
            ],
            'extension' => [
                new Assert\Type('string'),
                new Assert\Length(['min' => 2, 'max' => 10]),
                new Assert\Regex('/^[a-zA-Z0-9]+$/'), 
            ],
        ]);

        return $file;
    }
}