<?php

namespace App\Plugins\Activity\Service;

use App\Service\CrudManager;
use App\Exception\CrudException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use App\Plugins\Activity\Entity\LogEntity;
use App\Plugins\Account\Entity\OrganizationEntity;
use App\Plugins\Account\Entity\UserEntity;
use App\Plugins\Activity\Exception\ActivityException;

class LogService
{
    private CrudManager $crudManager;
    private EntityManagerInterface $entityManager;

    public function __construct(
        CrudManager $crudManager,
        EntityManagerInterface $entityManager
    ) {
        $this->crudManager = $crudManager;
        $this->entityManager = $entityManager;
    }

    public function getMany(OrganizationEntity $organization, array $filters, int $page, int $limit, array $criteria = []): array
    {
        try 
        {
            return $this->crudManager->findMany(LogEntity::class, $filters, $page, $limit, [
                'partition' => $organization->getPartition(),
                'organization' => $organization
            ] + $criteria);
        }
        catch (CrudException $e)
        {
            throw new ActivityException($e->getMessage());
        }
    }

    public function getOne(OrganizationEntity $organization, int $id, array $criteria = []): ?LogEntity
    {
        return $this->crudManager->findOne(LogEntity::class, $id, [
            'partition' => $organization->getPartition(),
            'organization' => $organization
        ] + $criteria);
    }

    public function delete(LogEntity $log): void
    {
        try 
        {
            $this->crudManager->delete($log);
        }
        catch (CrudException $e)
        {
            throw new ActivityException($e->getMessage());
        }
    }

    public function create(OrganizationEntity $organization, ?UserEntity $user, array $data = [], ?callable $callback = null): LogEntity
    {
        try 
        {
            $log = new LogEntity();

            $log->setPartition($organization->getPartition());
            $log->setOrganization($organization);
            $log->setUser($user);

            if($callback)
            {
                $callback($log, $data);
            }

            $this->crudManager->create($log, $data, [
                'identifier' => [
                    new Assert\Type('int'),
                    new Assert\Positive(),
                ],
                'entity' => [
                    new Assert\Type('string'),
                    new Assert\Length(['min' => 2, 'max' => 255]),
                ],
                'data' => new Assert\Optional([
                    new Assert\Type('array')
                ])
            ]);

            return $log;
        }
        catch (CrudException $e)
        {
            throw new ActivityException($e->getMessage());
        }
    }

    public function update(LogEntity $log, array $data): LogEntity
    {
        try 
        {
            $this->crudManager->update($log, $data, [
                'identifier' => [
                    new Assert\Type('int'),
                    new Assert\Positive(),
                ],
                'entity' => [
                    new Assert\Type('string'),
                    new Assert\Length(['min' => 2, 'max' => 255]),
                ],
                'data' => new Assert\Optional([
                    new Assert\Type('array')
                ])
            ]);

            return $log;
        }
        catch (CrudException $e)
        {
            throw new ActivityException($e->getMessage());
        }
    }

    public function processLog(LogEntity $log): array
    {
        $entity = explode(':', $log->getEntity());
        $data = $log->getData();

        $resourceName =$data['resource']['name'];
        $resourceId =$data['resource']['id'];
        $userName = $data['user']['name'];
        $userId = $data['user']['id'];

        switch($data['action'])
        {
            case 'create':
                $message = $entity[1] . " #{$resourceId} '{$resourceName}' was created by #{$userId} {$userName}";
                break;
            case 'update':
                $changes = implode(', ', $data['changes']);
                $message = $entity[1]  . " #{$resourceId} '{$resourceName}' were updated by #{$userId} {$userName}. Changes made to: {$changes}";
                break;
            case 'delete':
                $message = $entity[1] . " #{$resourceId} '{$data['resource']['name']}' was deleted by #{$userId} {$data['user']['name']}";
                break;
            default:
                $message = 'Unknown action';
        }
       
        return [
            'id' => $log->getId(),
            'type' => $entity[1],
            'action' => $data['action'],
            'resource' =>$data['resource'],
            'user' => $data['user'],
            'message' => $message,
            'changes' => $data['changes'],
            'time' => $log->getCreated()->format('Y-m-d H:i:s')
        ];
    }
}