<?php

namespace App\Plugins\Account\Service;

use App\Service\CrudManager;
use App\Exception\CrudException;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Url;

use App\Plugins\Account\Entity\OrganizationEntity;
use App\Plugins\Account\Repository\OrganizationRepository;

use App\Plugins\Billing\Repository\PlanRepository;
use App\Plugins\Billing\Entity\PlanEntity;

use App\Plugins\Account\Exception\AccountException;

class OrganizationService
{
    private CrudManager $crudManager;
    private OrganizationRepository $organizationRepository;
    private PlanRepository $planRepository;

    public function __construct(
        CrudManager $crudManager, 
        OrganizationRepository $organizationRepository,
        PlanRepository $planRepository
    )
    {
        $this->crudManager = $crudManager;
        $this->organizationRepository = $organizationRepository;
        $this->planRepository = $planRepository;
    }

    public function getMany(array $filters, int $page, int $limit): array
    {
        try 
        {
            return $this->crudManager->findMany(OrganizationEntity::class, $filters, $page, $limit);
        }
        catch(CrudException $e)
        {
            throw new AccountException($e->getMessage());
        }
    }

    public function getOne(int $id): ?OrganizationEntity
    {
        return $this->crudManager->findOne(OrganizationEntity::class, $id);
    }

    public function create(array $data = []): OrganizationEntity
    {
        try 
        {
            $organization = new OrganizationEntity();

            $this->crudManager->create($organization, $data, [
                'name' => [
                    new Assert\Type('string'),
                    new Assert\Length(['min' => 2, 'max' => 255]),
                ],
                'domain' => [
                    new Assert\Type('string'),
                    new Assert\Length(['max' => 255]),
                    new Assert\Regex([
                        'pattern' => '/^(?!http:\/\/|https:\/\/)([a-zA-Z0-9-_]+\.)+[a-zA-Z]{2,6}$/',
                        'message' => 'The domain must be a valid domain name.',
                    ]),
                ],
                'description' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['max' => 1000]),
                ]),
                'website' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Url(),
                ]),
                'color' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['max' => 255]),
                    new Assert\Regex([
                        'pattern' => '/^rgba\((\d{1,3}),\s?(\d{1,3}),\s?(\d{1,3}),\s?(0|0?\.\d+|1)\)$/',
                        'message' => 'The color must be in RGBA format.',
                    ]),
                ]),
                'plan' => [
                    new Assert\Type('integer'),
                ],
                'extensions' => new Assert\Optional([
                    new Assert\All([
                        new Assert\Type('integer'),
                    ]),
                ]),
            ], $this->callbacks());

            return $organization;
        }
        catch(CrudException $e)
        {
            throw new AccountException($e->getMessage());
        }
    }

    public function update(OrganizationEntity $organization, array $data = []): void
    {
        try 
        {
            $this->crudManager->update($organization, $data, [
                'name' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['min' => 2, 'max' => 255]),
                ]),
                'domain' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['max' => 255]),
                    new Assert\Regex([
                        'pattern' => '/^(?!://)([a-zA-Z0-9-_]+\.)+[a-zA-Z]{2,6}$/',
                        'message' => 'The domain must be a valid domain name.',
                    ]),
                ]),
                'description' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['max' => 1000]),
                ]),
                'website' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Url(),
                ]),
                'color' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['max' => 255]),
                    new Assert\Regex([
                        'pattern' => '/^rgba\((\d{1,3}),\s?(\d{1,3}),\s?(\d{1,3}),\s?(0|0?\.\d+|1)\)$/',
                        'message' => 'The color must be in RGBA format.',
                    ]),
                ]),
                'extensions' => new Assert\Optional([
                    new Assert\All([
                        new Assert\Type('integer'),
                    ]),
                ]),
            ], $this->callbacks());
        }
        catch(CrudException $e)
        {
            throw new AccountException($e->getMessage());
        }
    }

    public function delete(OrganizationEntity $organization, bool $hard = false): void
    {
        try 
        {
            $this->crudManager->delete($organization, $hard);
        }
        catch(CrudException $e)
        {
            throw new AccountException($e->getMessage());
        }
    }


    private function callbacks()
    {
        return [
            'domain' => function(string $value): string
            {
                $value = str_replace('.rentsera.com', '', $value);
                $counter = null;

                while($this->organizationRepository->findOneByDomain($value . ($counter === null ? '' : $counter) . '.rentsera.com'))
                {
                    $counter++;
                }

                return $value . ($counter === null ? '' : $counter) . '.rentsera.com';
            },
            'plan' => function(int $value): PlanEntity
            {
                if(!$plan = $this->planRepository->find($value)) 
                {
                    throw new AccountException("Plan with ID {$value} does not exist.");
                }

                return $plan;
            }
        ];
    }
}
