<?php

namespace App\Plugins\Metrics\Service;

use Doctrine\ORM\EntityManagerInterface;
use App\Service\FilterService;
use App\Exception\FilterException;

use App\Plugins\Account\Entity\OrganizationEntity;
use App\Plugins\Account\Entity\UserEntity;

use App\Plugins\Metrics\Exception\MetricsException;

class MetricService
{
    private FilterService $filterService;
    private EntityManagerInterface $entityManager;

    public function __construct(
        FilterService $filterService,
        EntityManagerInterface $entityManager
    )
    {
        $this->filterService = $filterService;
        $this->entityManager = $entityManager;
    }

    private array $plugins = [
        'notes' => [
            'class'  => 'App\\Plugins\\Notes\\Entity\\NoteEntity',
            'table'  => 'notes',
            'prefix' => 'note_'
        ]
    ];

    public function total(string $plugin, array $filters, ?OrganizationEntity $organization = null, ?UserEntity $user = null): int
    {
        $entity = $this->entity($plugin);
        $queryBuilder = $this->entityManager->createQueryBuilder();

        $queryBuilder->select('COUNT(t1.id)')->from($entity['class'], 't1');

        if($organization)
        {
            $queryBuilder->andWhere("t1.organization = :organization")->setParameter('organization', $organization);
        }
        if($user)
        {
            $queryBuilder->andWhere("t1.user = :user")->setParameter('user', $user);
        }

        try
        {
            $this->filterService->apply($queryBuilder, $filters, $entity['class']);
        }
        catch (FilterException $e)
        {
            throw new MetricsException($e->getMessage());
        }

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function summary(string $plugin, array $filters, string $group, int $limit, ?OrganizationEntity $organization = null, ?ClientEntity $client = null): array
    {
        $entity = $this->entity($plugin);
        $queryBuilder = $this->entityManager->createQueryBuilder();

        $queryBuilder->select(sprintf(
            "TO_CHAR(t1.created, '%s') AS date, COUNT(t1.id) AS count",
            match ($group) {
                'minute' => 'YYYY-MM-DD HH24:MI',
                'hour' => 'YYYY-MM-DD HH24',
                'day' => 'YYYY-MM-DD',
                'week' => 'YYYY-IW',
                'month' => 'YYYY-MM',
                'year' => 'YYYY',
                default => throw new \InvalidArgumentException("Invalid group option: $group"),
            }
        ))
        ->from($entity['class'], 't1');

        if ($organization) {
            $queryBuilder->andWhere("t1.organization = :organization")->setParameter('organization', $organization);
        }
        if ($client) {
            $queryBuilder->andWhere("t1.client = :client")->setParameter('client', $client);
        }

        $queryBuilder->groupBy('date')->orderBy('date', 'DESC')->setMaxResults($limit);

        try
        {
            $this->filterService->apply($queryBuilder, $filters, $entity['class']);
        }
        catch (FilterException $e)
        {
            throw new MetricsException($e->getMessage());
        }

        try {
            return $queryBuilder->getQuery()->getResult();
        } catch (\Doctrine\DBAL\Exception $e) {
            throw new MetricsException("Database error: " . $e->getMessage(), 0, $e);
        }
    }

    private function entity(string $plugin): array
    {
        if(!isset($this->plugins[$plugin]))
        {
            throw new MetricsException("Invalid plugin: $plugin");
        }

        return $this->plugins[$plugin];
    }
}