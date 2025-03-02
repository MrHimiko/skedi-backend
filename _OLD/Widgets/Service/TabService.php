<?php

namespace App\Plugins\Widgets\Service;

use App\Service\CrudManager;
use App\Exception\CrudException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use App\Plugins\Widgets\Entity\TabEntity;
use App\Plugins\Account\Entity\UserEntity;
use App\Plugins\Widgets\Exception\WidgetsException;

class TabService
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
            return $this->crudManager->findMany(TabEntity::class, $filters, $page, $limit, [
                'organization' => $user->getOrganization(),
                'user' => $user
            ]);
        }
        catch (CrudException $e)
        {
            throw new WidgetsException($e->getMessage());
        }
    }

    public function getOne(UserEntity $user, int $id): ?TabEntity
    {
        return $this->crudManager->findOne(TabEntity::class, $id, [
            'organization' => $user->getOrganization(),
            'user' => $user
        ]);
    }

    public function delete(TabEntity $widget, bool $hard = false): void
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

    public function create(UserEntity $user, array $data = []): TabEntity
    {
        try 
        {
            $tab = new TabEntity();
            
            $tab->setOrganization($user->getOrganization());
            $tab->setUser($user);
            $tab->setOrder(1);

            $this->crudManager->create($tab, $data, [
                'name' => [
                    new Assert\Type('string'),
                    new Assert\Length(['min' => 2, 'max' => 255]),
                ],
                'order' => [
                    new Assert\Type('int'),
                    new Assert\Positive(),
                ],
            ]);

            return $tab;
        }
        catch (CrudException $e)
        {
            throw new WidgetsException($e->getMessage());
        }
    }

    public function update(TabEntity $tab, array $data): void
    {
        try 
        {
            $this->crudManager->update($tab, $data, [
                'name' => [
                    new Assert\Optional([
                        new Assert\Type('string'),
                        new Assert\Length(['min' => 2, 'max' => 255]),
                    ]),
                ],
                'order' => [
                    new Assert\Optional([
                        new Assert\Type('int'),
                        new Assert\Positive(),
                    ]),
                ],
            ]);
        }
        catch (CrudException $e)
        {
            throw new WidgetsException($e->getMessage());
        }
    }
}
