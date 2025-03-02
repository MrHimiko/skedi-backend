<?php

namespace App\Plugins\Widgets\Service;

use App\Service\CrudManager;
use App\Exception\CrudException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use App\Plugins\Widgets\Entity\ItemEntity;
use App\Plugins\Account\Entity\UserEntity;
use App\Plugins\Widgets\Exception\WidgetsException;

class ItemService
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
            return $this->crudManager->findMany(ItemEntity::class, $filters, $page, $limit, [
                'organization' => $user->getOrganization(),
                'user' => $user
            ]);
        }
        catch (CrudException $e)
        {
            throw new WidgetsException($e->getMessage());
        }
    }

    public function getOne(UserEntity $user, int $id): ?ItemEntity
    {
        return $this->crudManager->findOne(ItemEntity::class, $id, [
            'organization' => $user->getOrganization(),
            'user' => $user
        ]);
    }

    public function delete(ItemEntity $widget, bool $hard = false): void
    {
        try 
        {
            $this->crudManager->delete($widget, $hard);
        }
        catch (CrudException $e)
        {
            throw new WidgetsException($e->getMessage());
        }
    }

    public function create(UserEntity $user, array $data = [], ?TabEntity $tab = null): ItemEntity
    {
        try 
        {
            $item = new ItemEntity();

            $item->setOrganization($user->getOrganization());
            $item->setUser($user);
            $item->setTab($tab);
            $item->setOrder(1);
            $item->setWidth(2);

            $this->crudManager->create($item, $data, [
                'name' => [
                    new Assert\Type('string'),
                    new Assert\Length(['min' => 2, 'max' => 255]),
                ],
                'title' => [
                    new Assert\Type('string'),
                    new Assert\Length(['min' => 2, 'max' => 255]),
                ],
                'description' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['min' => 2, 'max' => 1000]),
                ]),
                'width' => new Assert\Optional([
                    new Assert\Type('int'),
                    new Assert\Choice([
                        'choices' => [1, 2, 3, 4],
                        'message' => 'The value must be one of 1, 2, 3, or 4.',
                    ]),
                ]),
                'order' => new Assert\Optional([
                    new Assert\Type('int'),
                ]),
            ]);

            return $item;
        }
        catch (CrudException $e)
        {
            throw new WidgetsException($e->getMessage());
        }
    }

    public function update(ItemEntity $item, array $data): void
    {
        try 
        {
            $this->crudManager->update($item, $data, [
                'name' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['min' => 2, 'max' => 255]),
                ]),
                'title' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['min' => 2, 'max' => 255]),
                ]),
                'description' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['min' => 2, 'max' => 1000]),
                ]),
                'width' => new Assert\Optional([
                    new Assert\Type('int'),
                    new Assert\Choice([
                        'choices' => [1, 2, 3, 4],
                        'message' => 'The value must be one of 1, 2, 3, or 4.',
                    ]),
                ]),
                'order' => new Assert\Optional([
                    new Assert\Type('int'),
                ]),
            ]);
        }
        catch (CrudException $e)
        {
            throw new WidgetsException($e->getMessage());
        }
    }
}
