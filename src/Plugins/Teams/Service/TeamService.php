<?php

namespace App\Plugins\Teams\Service;

use App\Service\CrudManager;
use App\Exception\CrudException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use App\Service\SlugService;

use App\Plugins\Teams\Exception\TeamsException;
use App\Plugins\Organizations\Entity\OrganizationEntity;
use App\Plugins\Teams\Entity\TeamEntity;


class TeamService
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
                TeamEntity::class,
                $filters,
                $page,
                $limit,
                $criteria + [
                    'deleted' => false
                ]
            );
        } catch (CrudException $e) {
            throw new TeamsException($e->getMessage());
        }
    }

    public function getOne(int $id, array $criteria = []): ?TeamEntity
    {
        return $this->crudManager->findOne(TeamEntity::class, $id, $criteria + ['deleted' => false]);
    }


    public function create(array $data = [], ?callable $callback = null): TeamEntity
    {
        try 
        {
            $team = new TeamEntity();

            if ($callback) 
            {
                $callback($team);
            }

            if(!array_key_exists('slug', $data))
            {
                $data['slug'] = $data['name'] ?? null;
            }

            $data['slug'] = $this->slugService->generateSlug($data['slug']);

            if($this->getBySlug($data['slug']))
            {
                throw new TeamsException('Team slug already exist.');
            }

            $contraints = [
                'name' => [
                    new Assert\Type('scalar'),
                    new Assert\Length(['min' => 2, 'max' => 255]),
                ],
                'slug' => [
                    new Assert\Type('scalar'),
                    new Assert\Length(['min' => 2, 'max' => 255]),
                ],
                'parent_team_id' => new Assert\Optional([
                    new Assert\Type('numeric')
                ]),
                'color' => new Assert\Optional([
                    new Assert\Type('scalar'),
                    new Assert\Length(['min' => 2, 'max' => 30]),
                ])
                
            ];

            $this->crudManager->create($team, array_intersect_key($data, array_flip(['name', 'slug', 'parent_team_id', 'color'])), $contraints);

            $this->update($team, $data);

            return $team;
        } 
        catch (CrudException $e) 
        {
            $this->delete($team, true);

            throw new TeamsException($e->getMessage());
        }
    }

    public function update(TeamEntity $team, array $data): void
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
            'parent_team_id' => new Assert\Optional([
                new Assert\Type('numeric'),
            ]),
            'color' => new Assert\Optional([
                new Assert\Type('scalar'),
                new Assert\Length(['min' => 2, 'max' => 30]),
            ])
        ];

        $transform = [
            'slug' => function(string $value) use($team)
            {
                $value = $this->slugService->generateSlug($value);

                if($this->getBySlug($value) && $team->getSlug() !== $value)
                {
                    throw new TeamsException('Slug already exist.');
                }

                return $value;
            },
            'parent_team_id' => function($value) use($team)
            {
                if ($value) {
                    $parentTeam = $this->getOne($value);
                    if (!$parentTeam) {
                        throw new TeamsException('Parent team not found.');
                    }
                    
                    // Check for circular reference
                    if ($parentTeam->getId() === $team->getId()) {
                        throw new TeamsException('A team cannot be its own parent.');
                    }
                    
                    // Ensure parent team belongs to the same organization
                    if ($parentTeam->getOrganization()->getId() !== $team->getOrganization()->getId()) {
                        throw new TeamsException('Parent team must belong to the same organization.');
                    }
                    
                    return $parentTeam;
                }
                return null;
            }
        ];

        try 
        {
            $this->crudManager->update($team, $data, $contraints, $transform);
        } 
        catch (CrudException $e) 
        {
            throw new TeamsException($e->getMessage());
        }
    }

    public function delete(TeamEntity $team, bool $hard = false): void
    {
        try {
            // Check if team has child teams before deleting
            if (!$hard && $this->hasChildTeams($team)) {
                throw new TeamsException('Cannot delete a team that has child teams. Please delete or reassign child teams first.');
            }
            
            $this->crudManager->delete($team, $hard);
        } catch (CrudException $e) {
            throw new TeamsException($e->getMessage());
        }
    }

    private function getBySlug(string $slug)
    {
        $teams = $this->getMany([], 1, 1, ['slug' => $slug, 'deleted' => false]);

        return count($teams) ? $teams[0] : null;
    }


    
    public function getChildTeams(int $teamId): array
    {
        $parentTeam = $this->getOne($teamId);
        
        if (!$parentTeam) {
            return [];
        }
        
        try {
            return $this->getMany([], 1, 1000, ['parentTeam' => $parentTeam]);
        } catch (CrudException $e) {
            throw new TeamsException($e->getMessage());
        }
    }
    

    public function getTeamsByOrganization($organization): array
    {
        $organizationEntity = is_object($organization) && property_exists($organization, 'entity') 
            ? $organization->entity 
            : $organization;
        
        try {
            return $this->getMany([], 1, 1000, ['organization' => $organizationEntity, 'deleted' => false]);
        } catch (CrudException $e) {
            throw new TeamsException($e->getMessage());
        }
    }


    public function getTeamByIdAndOrganization(int $id, OrganizationEntity $organization): ?TeamEntity
    {
        return $this->getOne($id, ['organization' => $organization]);
    }

    public function hasChildTeams(TeamEntity $team): bool
    {
        $children = $this->getMany([], 1, 1, ['parentTeam' => $team]);
        return count($children) > 0;
    }

    
}