<?php

namespace App\Plugins\Account\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Constraints as Assert;
use App\Service\ValidatorService;
use App\Plugins\Account\Entity\UserEntity;
use App\Plugins\Account\Entity\OrganizationEntity;
use App\Plugins\Account\Exception\AccountException;
use App\Exception\CrudException;

use App\Plugins\Mailer\Exception\MailerException;
use App\Plugins\Mailer\Service\EmailService;

class RegisterService
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;
    private ValidatorService $validatorService;
    private UserService $userService;
    private OrganizationService $organizationService;
    private EmailService $emailService;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorService $validatorService,
        UserService $userService,
        OrganizationService $organizationService,
        EmailService $emailService
    ) {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        $this->validatorService = $validatorService;
        $this->userService = $userService;
        $this->organizationService = $organizationService;  
        $this->emailService = $emailService;
    }

    public function register(?OrganizationEntity $organization, array $data): UserEntity
    {
        $this->validate($data);
        
        $user = $this->userService->create($organization, $data + ['role' => 1]);

        try 
        {
            $this->emailService->send($organization, $user, $user->getEmail(), 'welcome', [
                'name' => $user->getName(),
            ]);
        }
        catch(MailerException $e)
        {
            throw new AccountException($e->getMessage());
        }

        return $user;
    }

    private function validate(array $data = []): void
    {
        $constraints = new Assert\Collection([
            'email' => [
                new Assert\NotBlank(['message' => 'Email is required.']),
                new Assert\Email(['message' => 'Invalid email format.']),
            ],
            'name' => [
                new Assert\NotBlank(['message' => 'Name is required.']),
                new Assert\Length(['min' => 2, 'max' => 255]),
            ],
            'password' => [
                new Assert\NotBlank(['message' => 'Password is required.']),
                new Assert\Length(['min' => 8]),
            ]
        ]);

        if($errors = $this->validatorService->toArray($constraints, $data)) 
        {
            throw new AccountException(implode(' | ', $errors));
        }
    }
}
