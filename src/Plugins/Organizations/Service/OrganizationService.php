<?php

namespace App\Plugins\Organizations\Service;

use App\Service\CrudManager;
use App\Exception\CrudException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use App\Service\SlugService; // <-- Import the SlugService

use App\Plugins\Organizations\Entity\OrganizationEntity;
use App\Plugins\Organizations\Exception\OrganizationsException;

class OrganizationService
{
    private CrudManager $crudManager;
    private EntityManagerInterface $entityManager;
    private SlugService $slugService;

    public function __construct(
        CrudManager $crudManager,
        EntityManagerInterface $entityManager,
        SlugService $slugService
    ) {
        $this->crudManager     = $crudManager;
        $this->entityManager   = $entityManager;
        $this->slugService     = $slugService;
    }


    public function getMany(array $filters, int $page, int $limit, array $criteria = []): array
    {
        try {
            return $this->crudManager->findMany(
                OrganizationEntity::class,
                $filters,
                $page,
                $limit,
                $criteria + [
                    'deleted' => false
                ]
            );
        } catch (CrudException $e) {
            throw new OrganizationsException($e->getMessage());
        }
    }

    public function getOne(int $id, array $criteria = []): ?OrganizationEntity
    {
        return $this->crudManager->findOne(OrganizationEntity::class, $id, $criteria + ['deleted' => false]);
    }

    public function create(array $data = [], ?callable $callback = null): OrganizationEntity
    {
        try 
        {
            $organization = new OrganizationEntity();

            if ($callback) 
            {
                $callback($organization);
            }

            if(!array_key_exists('slug', $data))
            {
                $data['slug'] = $data['name'] ?? null;
            }

            $data['slug'] = $this->slugService->generateSlug($data['slug']);

            if($this->getBySlug($data['slug']))
            {
                throw new OrganizationsException('Organization slug already exist.');
            }

            $contraints = [
                'name' => [
                    new Assert\Type('scalar'),
                    new Assert\Length(['min' => 2, 'max' => 255]),
                ],
                'slug' => [
                    new Assert\Type('scalar'),
                    new Assert\Length(['min' => 2, 'max' => 255]),
                ]
            ];

            $this->crudManager->create($organization, array_intersect_key($data, array_flip(['name', 'slug'])), $contraints);

            $this->update($organization, $data);

            return $organization;
        } 
        catch (CrudException $e) 
        {
            $this->delete($organization, true);

            throw new OrganizationsException($e->getMessage());
        }
    }

    public function update(OrganizationEntity $organization, array $data): void
    {
        $contraints = [
            'name' => new Assert\Optional([
                new Assert\Type('scalar'),
                new Assert\Length(['min' => 2, 'max' => 255]),
            ]),
            'slug' => new Assert\Optional([
                new Assert\NotBlank,
                new Assert\Type('scalar'),
                new Assert\Length(['min' => 2, 'max' => 255]),
            ]),
        ];

        $transform = [
            'slug' => function(string $value) use($organization)
            {
                $value = $this->slugService->generateSlug($value);

                if($this->getBySlug($value) && $organization->getSlug() !== $value)
                {
                    throw new OrganizationsException('Slug already exist.');
                }

                return $value;
            }
        ];

        try 
        {
            $this->crudManager->update($organization, $data, $contraints, $transform);
        } 
        catch (CrudException $e) 
        {
            throw new OrganizationsException($e->getMessage());
        }
    }

    public function delete(OrganizationEntity $organization, bool $hard = false): void
    {
        try {
            $this->crudManager->delete($organization, $hard);
        } catch (CrudException $e) {
            throw new OrganizationsException($e->getMessage());
        }
    }

    public function getBySlug(string $slug)
    {
        $organizations = $this->getMany([], 1, 1, ['slug' => $slug, 'deleted' => false]);

        return count($organizations) ? $organizations[0] : null;
    }
}
