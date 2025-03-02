<?php

namespace App\Plugins\Notifications\Service;

use App\Service\CrudManager;
use App\Exception\CrudException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Validator\Constraints as Assert;
use App\Plugins\Notifications\Entity\NotificationEntity;
use App\Plugins\Account\Entity\UserEntity;
use App\Plugins\Notifications\Exception\NotificationsException;

class NotificationService
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

    public function getMany(UserEntity $user, array $filters, int $page, int $limit): array
    {
        try 
        {
            return $this->crudManager->findMany(NotificationEntity::class, $filters, $page, $limit, [], function(QueryBuilder $qb) use($user) 
            {
               
            });
        }
        catch (CrudException $e)
        {
            throw new NotificationsException($e->getMessage());
        }
    }

    public function getOne(UserEntity $user, int $id): ?NotificationEntity
    {
        return $this->crudManager->findOne(NotificationEntity::class, $id);
    }
   
    public function create(array $data = []): NotificationEntity
    {
        try 
        {
            $notification = new NotificationEntity();

            $this->crudManager->create($notification, $data, [
                'organizations' => new Assert\Optional([
                    new Assert\All([
                        new Assert\Type('integer'),
                    ]),
                ]),
                'users' => new Assert\Optional([
                    new Assert\All([
                        new Assert\Type('integer'),
                    ]),
                ]),
                'userRoles' => new Assert\Optional([
                    new Assert\All([
                        new Assert\Type('integer'),
                    ]),
                ]),
                'userTypes' => new Assert\Optional([
                    new Assert\All([
                        new Assert\Type('string'),
                    ]),
                ]),
                'billingPlans' => new Assert\Optional([
                    new Assert\All([
                        new Assert\Type('integer'),
                    ]),
                ]),
                'group' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['min' => 2, 'max' => 255]),
                ]),
                'title' => [
                    new Assert\Type('string'),
                    new Assert\Length(['min' => 2, 'max' => 255]),
                ],
                'description' => [
                    new Assert\Type('string'),
                    new Assert\Length(['min' => 2, 'max' => 1000]),
                ],
                'link' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['max' => 255]),
                ]),
                'entity' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['max' => 255]),
                ]),
                'identifier' => new Assert\Optional([
                    new Assert\Type('integer'),
                ]),
                'content' => new Assert\Optional([
                    new Assert\Type('array'),
                ]),
                'email' => new Assert\Optional([
                    new Assert\Type('bool'),
                ]),
                'sms' => new Assert\Optional([
                    new Assert\Type('bool'),
                ]),
                'push' => new Assert\Optional([
                    new Assert\Type('bool'),
                ]),
            ]);
            

            return $notification;
        }
        catch (CrudException $e)
        {
            throw new NotificationsException($e->getMessage());
        }
    }
}
