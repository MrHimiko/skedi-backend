<?php

namespace App\Plugins\Widgets\Service;

use Symfony\Component\Validator\Constraints as Assert;

use App\Plugins\Widgets\Entity\WidgetEntity;

use App\Service\CrudManager;
use App\Exception\CrudException;

use App\Plugins\Widgets\Exception\WidgetsException;

class WidgetService
{
    private CrudManager $crudManager;

    public function __construct(
        CrudManager $crudManager, 
    )
    {
        $this->crudManager = $crudManager;
    }

    public function getMany(array $filters, int $page, int $limit): array
    {
        try 
        {
            return $this->crudManager->findMany(WidgetEntity::class, $filters, $page, $limit);
        }
        catch(CrudException $e)
        {
            throw new WidgetsException($e->getMessage());
        }
    }

    public function getOne(int $id): ?WidgetEntity
    {
        return $this->crudManager->findOne(WidgetEntity::class, $id);
    }
}