<?php

namespace App\Plugins\Account\Service;

use Symfony\Component\Validator\Constraints as Assert;
use App\Plugins\Account\Entity\UserEntity;
use App\Plugins\Account\Entity\RoleEntity;
use App\Plugins\Account\Repository\UserRepository;
use App\Plugins\Account\Repository\RoleRepository;
use App\Plugins\Billing\Repository\PlanRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Plugins\Account\Entity\OrganizationEntity;
use App\Plugins\Account\Exception\AccountException;

use App\Service\CrudManager;
use App\Exception\CrudException;

class UserService
{
    private CrudManager $crudManager;
    private UserRepository $userRepository;
    private RoleRepository $roleRepository;
    private PlanRepository $planRepository;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(
        CrudManager $crudManager,
        UserRepository $userRepository,
        RoleRepository $roleRepository,
        PlanRepository $planRepository,
        UserPasswordHasherInterface $passwordHasher
    ) {
        $this->crudManager = $crudManager;
        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
        $this->planRepository = $planRepository;
        $this->passwordHasher = $passwordHasher;
    }

    public function getMany(OrganizationEntity $organization, array $filters, int $page, int $limit): array
    {
        try 
        {
            return $this->crudManager->findMany(UserEntity::class, $filters, $page, $limit, [
                'deleted' => false,
                'partition' => $organization->getPartition(),
                'organization' => $organization
            ]);
        }
        catch(CrudException $e)
        {
            throw new AccountException($e->getMessage());
        }
    }

    public function getOne(OrganizationEntity $organization, int $id): ?UserEntity
    {
        return $this->crudManager->findOne(UserEntity::class, $id, [
            'deleted' => false,
            'partition' => $organization->getPartition(),
            'organization' => $organization
        ]);
    }

    public function getOneByEmail(OrganizationEntity $organization, string $email): ?UserEntity
    {
        return $this->userRepository->findOneBy([
            'deleted' => false,
            'email' => $email,
            'partition' => $organization->getPartition(),
            'organization' => $organization
        ]);
    }

    public function delete(UserEntity $user): void
    {
        try 
        {
            $this->crudManager->delete($user);
        }
        catch(CrudException $e)
        {
            throw new AccountException($e->getMessage());
        }
    }

    public function update(UserEntity $user, array $data = []): void
    {
        try 
        {
            $this->crudManager->update($user, $data, 
            [
                'name' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['min' => 2, 'max' => 255]),
                ]),
                'type' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['min' => 2, 'max' => 255]),
                ]),
                'extensions' => new Assert\Optional([
                    new Assert\All([
                        new Assert\Type('integer'),
                    ]),
                ]),
                'password' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['min' => 8]),
                ])
            ], $this->callbacks($user));
        }
        catch(CrudException $e)
        {
            throw new AccountException($e->getMessage());
        }
    }

    public function create(OrganizationEntity $organization, array $data = []): UserEntity
    {
        $data['role'] = 1;

        if($data['email'] && $this->getOneByEmail($organization, $data['email']))
        {
            throw new AccountException('Member with given email already exist.');
        }

        try 
        {
            $user = new UserEntity();

            $user->setPartition($organization->getPartition());
            $user->setOrganization($organization);

            $this->crudManager->create($user, $data, 
            [
                'name' => [
                    new Assert\NotBlank(),
                    new Assert\Type('string'),
                    new Assert\Length(['min' => 2, 'max' => 255]),
                ],
                'role' => [
                    new Assert\NotBlank(),
                    new Assert\Type('integer'),
                ],
                'type' => [
                    new Assert\NotBlank(),
                    new Assert\Type('string'),
                    new Assert\Length(['min' => 2, 'max' => 255]),
                ],
                'email' => [
                    new Assert\NotBlank(),
                    new Assert\Email(),
                    new Assert\Length(['max' => 255]),
                ],
                'password' => [
                    new Assert\Optional([
                        new Assert\NotBlank(),
                        new Assert\Type('string'),
                        new Assert\Length(['min' => 8]),
                    ]),
                ],
                'extensions' => new Assert\Optional([
                    new Assert\All([
                        new Assert\Type('integer'),
                    ]),
                ]),
            ], $this->callbacks($user));

            return $user;
        }
        catch(CrudException $e)
        {
            throw new AccountException($e->getMessage());
        }
    }

    private function callbacks(UserEntity $user)
    {
        return [
            'type' => function(string $value): string 
            {
                return in_array($value, ['staff', 'vendor', 'owner', 'tenant', 'prospect']) ? $value : 'prospect';
            },
            'password' => function(string $value) use ($user): string 
            {
                return $this->passwordHasher->hashPassword($user, $value);
            },
            'role' => function(int $value): RoleEntity
            {
                if(!$role = $this->roleRepository->find($value)) 
                {
                    throw new AccountException("Role with ID {$value} does not exist.");
                }

                return $role;
            }
        ];
    }
}
