<?php

namespace App\Plugins\Countries\Service;

use Symfony\Component\Validator\Constraints as Assert;
use App\Plugins\Countries\Entity\CountryEntity;
use App\Service\CrudManager;
use App\Exception\CrudException;
use App\Plugins\Countries\Exception\CountriesException;

class CountryService
{
    private CrudManager $crudManager;

    public function __construct(
        CrudManager $crudManager
    )
    {
        $this->crudManager = $crudManager;
    }

    public function getMany(array $filters, int $page, int $limit): array
    {
        try 
        {
            return $this->crudManager->findMany(CountryEntity::class, $filters, $page, $limit);
        }
        catch(CrudException $e)
        {
            throw new CountriesException($e->getMessage());
        }
    }

    public function getOne(int $id): ?CountryEntity
    {
        return $this->crudManager->findOne(CountryEntity::class, $id);
    }
}