<?php

namespace App\Plugins\Notes\Service;

use App\Service\CrudManager;
use App\Exception\CrudException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraints as Assert;

use App\Plugins\Notes\Entity\NoteEntity;
use App\Plugins\Notes\Exception\NotesException;

use App\Plugins\Account\Entity\OrganizationEntity;
use App\Plugins\Account\Entity\UserEntity;

use App\Plugins\Storage\Repository\FileRepository;

class NoteService
{
    private CrudManager $crudManager;
    private EntityManagerInterface $entityManager;
    private FileRepository $fileRepository;

    public function __construct(
        CrudManager $crudManager,
        EntityManagerInterface $entityManager,
        FileRepository $fileRepository
    ) {
        $this->crudManager = $crudManager;
        $this->entityManager = $entityManager;
        $this->fileRepository = $fileRepository;
    }

    public function getMany(OrganizationEntity $organization, array $filters, int $page, int $limit, array $criteria = []): array
    {
        try 
        {
            return $this->crudManager->findMany(NoteEntity::class, $filters, $page, $limit, $criteria + [
                'deleted'      => false,
                'partition'    => $organization->getPartition(),
                'organization' => $organization
            ]);
        }
        catch (CrudException $e)
        {
            throw new NotesException($e->getMessage());
        }
    }

    public function getOne(OrganizationEntity $organization, int $id, array $criteria = []): ?NoteEntity
    {
        return $this->crudManager->findOne(NoteEntity::class, $id, $criteria + [
            'deleted'      => false,
            'partition'    => $organization->getPartition(),
            'organization' => $organization
        ]);
    }

    public function delete(NoteEntity $note, bool $hard = false): void
    {
        try 
        {
            $this->crudManager->delete($note, $hard);
        }
        catch (CrudException $e)
        {
            throw new NotesException($e->getMessage());
        }
    }

    public function create(UserEntity $user, array $data = [], ?callable $callback = null): NoteEntity
    {
        $organization = $user->getOrganization();
        
        try 
        {
            $note = new NoteEntity();

            $note->setPartition($organization->getPartition());
            $note->setOrganization($organization);
            $note->setCreatedUser($user);

            if($callback)
            {
                $callback($note);
            }

            $this->crudManager->create($note, $data, [
                'title' => [
                    new Assert\Type('string'),
                    new Assert\Length(['min' => 2, 'max' => 255]),
                ],
                'content' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['min' => 2, 'max' => 20000]),
                ]),
                'tags' => new Assert\Optional([
                    new Assert\Type('array'),
                ]),
                'files' => new Assert\Optional([
                    new Assert\Type('string'),
                ]),
            ], [
                'files' => function(string $value) use($organization)
                {
                    $ids = [];
                    $files = $this->fileRepository->findByIds($organization, explode(',', $value));

                    foreach($files as $file)
                    {
                        $ids[] = $file->getId();
                    }

                    return $ids;
                }
            ]);

            return $note;
        }
        catch (CrudException $e)
        {
            throw new NotesException($e->getMessage());
        }
    }

    public function update(NoteEntity $note, UserEntity $user, array $data): void
    {
        $organization = $note->getOrganization();

        $note->setUpdatedUser($user);

        try 
        {
            $this->crudManager->update($note, $data, [
                'title' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['min' => 2, 'max' => 255]),
                ]),
                'content' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['min' => 2, 'max' => 20000]),
                ]),
                'tags' => new Assert\Optional([
                    new Assert\Type('array'),
                ]),
                'files' => new Assert\Optional([
                    new Assert\Type('string'),
                ]),
            ], [
                'files' => function(string $value) use($organization)
                {
                    $ids = [];
                    $files = $this->fileRepository->findByIds($organization, explode(',', $value));

                    foreach($files as $file)
                    {
                        $ids[] = $file->getId();
                    }

                    return $ids;
                }
            ]);
        }
        catch (CrudException $e)
        {
            throw new NotesException($e->getMessage());
        }
    }
}