<?php

namespace App\Plugins\People\Service;

use App\Service\CrudManager;
use App\Exception\CrudException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use App\Plugins\People\Entity\TenantEntity;
use App\Plugins\People\Exception\PeopleException;
use App\Plugins\Account\Entity\OrganizationEntity;
use App\Plugins\Account\Entity\UserEntity;
use App\Plugins\Countries\Entity\CountryEntity;
use App\Plugins\Storage\Entity\FileEntity;

use App\Plugins\Storage\Service\UploadService;

class TenantService
{
    private CrudManager $crudManager;
    private EntityManagerInterface $entityManager;
    private UploadService $uploadService;

    public function __construct(
        CrudManager $crudManager,
        EntityManagerInterface $entityManager,
        UploadService $uploadService
    )
    {
        $this->crudManager   = $crudManager;
        $this->entityManager = $entityManager;
        $this->uploadService = $uploadService;
    }

    public function getMany(OrganizationEntity $organization, array $filters, int $page, int $limit, bool $prospect = false): array
    {
        try
        {
            return $this->crudManager->findMany(
                TenantEntity::class,
                $filters,
                $page,
                $limit,
                [
                    'deleted'      => false,
                    'partition'    => $organization->getPartition(),
                    'organization' => $organization,
                    'prospect'     => $prospect
                ]
            );
        }
        catch (CrudException $e)
        {
            throw new PeopleException($e->getMessage());
        }
    }

    public function getOne(OrganizationEntity $organization, int $id, bool $prospect = false): ?TenantEntity
    {
        return $this->crudManager->findOne(
            TenantEntity::class,
            $id,
            [
                'deleted'      => false,
                'partition'    => $organization->getPartition(),
                'organization' => $organization,
                'prospect'     => $prospect
            ]
        );
    }

    public function delete(TenantEntity $tenant, bool $hard = false): void
    {
        try
        {
            $this->crudManager->delete($tenant, $hard);
        }
        catch (CrudException $e)
        {
            throw new PeopleException($e->getMessage());
        }
    }

    public function create(OrganizationEntity $organization, UserEntity $user, array $data = [], bool $prospect = false): TenantEntity
    {
        try
        {
            $tenant = new TenantEntity();

            $tenant->setPartition($organization->getPartition());
            $tenant->setOrganization($organization);
            $tenant->setCreatedUser($user);
            $tenant->setProspect($prospect);

            $constraints = [
                'firstName' => [
                    new Assert\NotBlank(),
                    new Assert\Type('string'),
                    new Assert\Length(['min' => 2, 'max' => 255]),
                ],
                'middleName' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['max' => 255]),
                ]),
                'lastName' => [
                    new Assert\NotBlank(),
                    new Assert\Type('string'),
                    new Assert\Length(['min' => 2, 'max' => 255]),
                ],
                'companyName' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['max' => 255]),
                ]),
                'jobTitle' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['max' => 255]),
                ]),
                'street11' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['max' => 255]),
                ]),
                'street12' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['max' => 255]),
                ]),
                'city1' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['max' => 255]),
                ]),
                'state1' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['max' => 255]),
                ]),
                'zipCode1' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['max' => 10]),
                ]),
                'street21' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['max' => 255]),
                ]),
                'street22' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['max' => 255]),
                ]),
                'city2' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['max' => 255]),
                ]),
                'state2' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['max' => 255]),
                ]),
                'zipCode2' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['max' => 10]),
                ]),
                'leadStatus' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['max' => 255]),
                ]),
                'leadSource' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['max' => 255]),
                ]),
                'leadMedium' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['max' => 255]),
                ]),
                'creditScore' => new Assert\Optional([
                    new Assert\Type('numeric'),
                    new Assert\Range(['min' => 0, 'max' => 999]),
                ]),
                'monthlyIncome' => new Assert\Optional([
                    new Assert\Type('numeric'),
                    new Assert\GreaterThanOrEqual(0),
                ]),
                'searchMinBedrooms' => new Assert\Optional([
                    new Assert\Type('integer'),
                ]),
                'searchMinBathrooms' => new Assert\Optional([
                    new Assert\Type('integer'),
                ]),
                'searchMaxRent' => new Assert\Optional([
                    new Assert\Type('numeric'),
                    new Assert\GreaterThanOrEqual(0),
                ]),
                'searchMoveIn' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\DateTime(),
                ]),
                'welcomeSms' => new Assert\Optional([
                    new Assert\Type('bool'),
                ]),
                'photo' => new Assert\Optional([
                    new Assert\File([
                        'maxSize' => '5M', 
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/jpeg', 'image/svg'], 
                        'mimeTypesMessage' => 'Please upload a valid image file (JPEG, JPG, PNG, or SVG).',
                    ]),
                ]),
                'assignedUser' => new Assert\Optional([
                    new Assert\Type('numeric'),
                ]),
                'phones' => new Assert\Optional([
                    new Assert\Type('array'),
                    new Assert\All([
                        new Assert\Collection([
                            'type' => [
                                new Assert\NotBlank(),
                                new Assert\Type('string'),
                            ],
                            'phone' => [
                                new Assert\NotBlank(),
                                new Assert\Type('string'),
                            ],
                        ])
                    ])
                ]),
                'emails' => [
                    new Assert\NotBlank(),
                    new Assert\Type('array'),
                    new Assert\All([
                        new Assert\Collection([
                            'type' => [
                                new Assert\NotBlank(),
                                new Assert\Type('string'),
                            ],
                            'email' => [
                                new Assert\NotBlank(),
                                new Assert\Email(), 
                            ],
                        ])
                    ])
                ],
                'pets' => new Assert\Optional([
                    new Assert\Type('array'),
                ]),
                'vehicles' => new Assert\Optional([
                    new Assert\Type('array'),
                ]),
                'dependents' => new Assert\Optional([
                    new Assert\Type('array'),
                ]),
                'country1' => new Assert\Optional([
                    new Assert\Type('numeric'),
                ]),
                'country2' => new Assert\Optional([
                    new Assert\Type('numeric'),
                ]),
            ];

            $transformers = [
                'photo' => function()
                {
                    return null;
                }
            ];

            $this->crudManager->create($tenant, $data, $constraints, $transformers);

            if($data['photo'])
            {
                $this->update($tenant, ['photo' => $data['photo']]);
            }

            return $tenant;
        }
        catch (CrudException $e)
        {
            throw new PeopleException($e->getMessage());
        }
    }

    public function update(TenantEntity $tenant, array $data): void
    {
        try
        {
            $constraints = [
                'firstName' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['min' => 2, 'max' => 255]),
                ]),
                'middleName' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['max' => 255]),
                ]),
                'lastName' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['min' => 2, 'max' => 255]),
                ]),
                'companyName' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['max' => 255]),
                ]),
                'jobTitle' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['max' => 255]),
                ]),
                'street11' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['max' => 255]),
                ]),
                'street12' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['max' => 255]),
                ]),
                'city1' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['max' => 255]),
                ]),
                'state1' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['max' => 255]),
                ]),
                'zipCode1' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['max' => 10]),
                ]),
                'street21' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['max' => 255]),
                ]),
                'street22' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['max' => 255]),
                ]),
                'city2' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['max' => 255]),
                ]),
                'state2' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['max' => 255]),
                ]),
                'zipCode2' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['max' => 10]),
                ]),
                'leadStatus' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['max' => 255]),
                ]),
                'leadSource' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['max' => 255]),
                ]),
                'leadMedium' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['max' => 255]),
                ]),
                'creditScore' => new Assert\Optional([
                    new Assert\Type('numeric'),
                    new Assert\Range(['min' => 0, 'max' => 999]),
                ]),
                'monthlyIncome' => new Assert\Optional([
                    new Assert\Type('numeric'),
                    new Assert\GreaterThanOrEqual(0),
                ]),
                'searchMinBedrooms' => new Assert\Optional([
                    new Assert\Type('integer'),
                ]),
                'searchMinBathrooms' => new Assert\Optional([
                    new Assert\Type('integer'),
                ]),
                'searchMaxRent' => new Assert\Optional([
                    new Assert\Type('numeric'),
                    new Assert\GreaterThanOrEqual(0),
                ]),
                'searchMoveIn' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\DateTime(),
                ]),
                'welcomeSms' => new Assert\Optional([
                    new Assert\Type('bool'),
                ]),
                'photo' => new Assert\Optional([
                    new Assert\File([
                        'maxSize' => '5M', 
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/jpeg', 'image/svg'], 
                        'mimeTypesMessage' => 'Please upload a valid image file (JPEG, JPG, PNG, or SVG).',
                    ]),
                ]),
                'assignedUser' => new Assert\Optional([
                    new Assert\Type('numeric'),
                ]),
                'phones' => new Assert\Optional([
                    new Assert\Type('array'),
                    new Assert\All([
                        new Assert\Collection([
                            'type' => [
                                new Assert\NotBlank(),
                                new Assert\Type('string'),
                            ],
                            'email' => [
                                new Assert\NotBlank(),
                                new Assert\Type('string'),
                            ],
                        ])
                    ])
                ]),
                'emails' => new Assert\Optional([
                    new Assert\Type('array'),
                    new Assert\All([
                        new Assert\Collection([
                            'type' => [
                                new Assert\NotBlank(),
                                new Assert\Type('string'),
                            ],
                            'email' => [
                                new Assert\NotBlank(),
                                new Assert\Email(), 
                            ],
                        ])
                    ])
                ]),
                'pets' => new Assert\Optional([
                    new Assert\Type('array'),
                ]),
                'vehicles' => new Assert\Optional([
                    new Assert\Type('array'),
                ]),
                'dependents' => new Assert\Optional([
                    new Assert\Type('array'),
                ]),
                'country1' => new Assert\Optional([
                    new Assert\Type('numeric'),
                ]),
                'country2' => new Assert\Optional([
                    new Assert\Type('numeric'),
                ]),
            ];

            $transformers = [
                'photo' => function ($value) use($tenant)
                {
                    if(!$value)
                    {
                        return null;
                    }

                    return $this->uploadService->uploadFile($tenant->getOrganization(), $value, null, function($file) use($tenant)
                    {
                        if($tenant->getProspect())
                        {
                            $file->setProspect($tenant);
                        }
                        else 
                        {
                            $file->setTenant($tenant);
                        }
                    });
                }
            ];


            // $transformers = [
            //     // 'searchMoveIn' => function (string $value)
            //     // {
            //     //     return new \DateTime($value);
            //     // },
            //     // 'country1' => function ($value)
            //     // {
            //     //     return $this->entityManager->getReference(CountryEntity::class, $value);
            //     // },
            //     // 'country2' => function ($value)
            //     // {
            //     //     return $this->entityManager->getReference(CountryEntity::class, $value);
            //     // },
            //     // 'photo' => function ($value)
            //     // {
            //     //     if (!$value)
            //     //     {
            //     //         return null;
            //     //     }
            //     //     return $this->entityManager->getReference(FileEntity::class, $value);
            //     // },
            //     // 'assignedUser' => function ($value)
            //     // {
            //     //     return $this->entityManager->getReference(UserEntity::class, $value);
            //     // },
            //     // 'emails' => function ($value)
            //     // {
            //     //     return is_array($value) ? $value : [];
            //     // },
            // ];
            $this->crudManager->update($tenant, $data, $constraints, $transformers);
        }
        catch (CrudException $e)
        {
            throw new PeopleException($e->getMessage());
        }
    }
}
