<?php

namespace App\Plugins\Mailer\Service;

use App\Service\CrudManager;
use App\Exception\CrudException;

use Symfony\Component\Validator\Constraints as Assert;

use App\Plugins\Mailer\Entity\LogEntity;
use App\Plugins\Account\Entity\OrganizationEntity;
use App\Plugins\Account\Entity\UserEntity;

use App\Plugins\Mailer\Exception\MailerException;

use App\Plugins\Billing\Repository\PlanRepository;

class LogService
{
    private CrudManager $crudManager;
    private OrganizationEntity $organizationRepository;
    private PlanRepository $planRepository;

    public function __construct(
        CrudManager $crudManager, 
        OrganizationEntity $organizationRepository,
        PlanRepository $planRepository
    )
    {
        $this->crudManager = $crudManager;
        $this->organizationRepository = $organizationRepository;
        $this->planRepository = $planRepository;
    }

    public function getMany(OrganizationEntity $organization, array $filters, int $page, int $limit): array
    {
        try 
        {
            return $this->crudManager->findMany(LogEntity::class, $filters, $page, $limit, [
                'organization' => $organization
            ]);
        }
        catch(CrudException $e)
        {
            throw new MailerException($e->getMessage());
        }
    }

    public function getOne(int $id): ?LogEntity
    {
        return $this->crudManager->findOne(LogEntity::class, $id);
    }

    public function delete(LogEntity $log): void
    {
        try 
        {
            $this->crudManager->delete($log);
        }
        catch(CrudException $e)
        {
            throw new MailerException($e->getMessage());
        }
    }

    public function create(OrganizationEntity $organization, ?UserEntity $user, array $data = []): LogEntity
    {
        try 
        {
            $log = new LogEntity();

            $log->setOrganization($organization);
            $log->setUser($user);

            $this->crudManager->create($log, $data, [
                'email' => [
                    new Assert\Type('string'),
                    new Assert\Length(['min' => 2, 'max' => 255]),
                ],
                'template' => [
                    new Assert\Type('string'),
                    new Assert\Length(['min' => 2, 'max' => 255]),
                ],
                'data' => new Assert\Optional([
                    new Assert\Type('object')
                ])
            ]);

            return $log;
        }
        catch(CrudException $e)
        {
            throw new MailerException($e->getMessage());
        }
    }
}
