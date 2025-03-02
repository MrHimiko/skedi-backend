<?php

namespace App\Plugins\Billing\Service;

use Symfony\Component\Validator\Constraints as Assert;

use App\Plugins\Billing\Entity\PlanEntity;

use App\Service\CrudManager;
use App\Exception\CrudException;

use App\Plugins\Billing\Exception\BillingException;

class PlanService
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
            return $this->crudManager->findMany(PlanEntity::class, $filters, $page, $limit);
        }
        catch(CrudException $e)
        {
            throw new BillingException($e->getMessage());
        }
    }

    public function getOne(int $id): ?PlanEntity
    {
        return $this->crudManager->findOne(PlanEntity::class, $id);
    }
}