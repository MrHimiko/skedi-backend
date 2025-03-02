<?php

namespace App\Plugins\Activity\Service;

use App\Service\CrudManager;
use App\Exception\CrudException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use App\Plugins\Activity\Entity\CommentEntity;
use App\Plugins\Account\Entity\OrganizationEntity;
use App\Plugins\Account\Entity\UserEntity;
use App\Plugins\Activity\Exception\ActivityException;

class CommentService
{
    private CrudManager $crudManager;
    private EntityManagerInterface $entityManager;

    public function __construct(
        CrudManager $crudManager,
        EntityManagerInterface $entityManager
    ) {
        $this->crudManager    = $crudManager;
        $this->entityManager  = $entityManager;
    }

    public function getMany(OrganizationEntity $organization, array $filters, int $page, int $limit, array $criteria = []): array
    {
        try 
        {
            return $this->crudManager->findMany(CommentEntity::class, $filters, $page, $limit, $criteria + [
                'partition'    => $organization->getPartition(),
                'organization' => $organization
            ]);
        } 
        catch (CrudException $e) 
        {
            throw new ActivityException($e->getMessage());
        }
    }

    public function getOne(OrganizationEntity $organization, int $id): ?CommentEntity
    {
        return $this->crudManager->findOne(
            CommentEntity::class, 
            $id, 
            [
                'partition'    => $organization->getPartition(),
                'organization' => $organization
            ]
        );
    }

    public function delete(CommentEntity $comment): void
    {
        try 
        {
            $this->crudManager->delete($comment);
        } 
        catch (CrudException $e) 
        {
            throw new ActivityException($e->getMessage());
        }
    }

    public function create(UserEntity $user, array $data = [], ?callable $callback = null): CommentEntity
    {
        $organization = $user->getOrganization();

        try 
        {
            $comment = new CommentEntity();

            $comment->setPartition($organization->getPartition());
            $comment->setOrganization($organization);
            $comment->setUser($user);

            if($callback)
            {
                $callback($comment);
            }

            $this->crudManager->create(
                $comment, 
                $data, 
                [
                    'message' => [
                        new Assert\Type('string'),
                        new Assert\Length(['min' => 1])
                    ]
                ]
            );

            return $comment;
        } 
        catch (CrudException $e) 
        {
            throw new ActivityException($e->getMessage());
        }
    }

    public function update(CommentEntity $comment, array $data): CommentEntity
    {
        try 
        {
            $this->crudManager->update(
                $comment, 
                $data, 
                [
                    'message' => [
                        new Assert\Type('string'),
                        new Assert\Length(['min' => 1])
                    ]
                ]
            );

            return $comment;
        } 
        catch (CrudException $e) 
        {
            throw new ActivityException($e->getMessage());
        }
    }
}
