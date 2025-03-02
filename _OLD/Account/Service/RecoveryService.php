<?php

namespace App\Plugins\Account\Service;

use Symfony\Component\Validator\Constraints as Assert;

use Doctrine\ORM\EntityManagerInterface;
use App\Plugins\Account\Entity\UserEntity;
use App\Plugins\Account\Entity\OrganizationEntity;
use App\Plugins\Account\Exception\AccountException;
use App\Plugins\Mailer\Service\EmailService;
use App\Plugins\Account\Service\UserService;
use App\Service\ValidatorService;

use App\Plugins\Mailer\Exception\MailerException;

class RecoveryService
{
    private EntityManagerInterface $entityManager;
    private EmailService $emailService;
    private UserService $userService;
    private ValidatorService $validatorService;

    public function __construct(
        EntityManagerInterface $entityManager, 
        EmailService $emailService, 
        UserService $userService,
        ValidatorService $validatorService
    )
    {
        $this->entityManager = $entityManager;
        $this->emailService = $emailService;
        $this->userService = $userService;
        $this->validatorService = $validatorService;
    }

    public function request(OrganizationEntity $organization, array $data): string
    {
        $constraints = new Assert\Collection([
            'email' => [
                new Assert\NotBlank(['message' => 'Email is required.']),
                new Assert\Email(['message' => 'Invalid email format.']),
            ]
        ]);

        if($errors = $this->validatorService->toArray($constraints, $data)) 
        {
            throw new AccountException(implode(' | ', $errors));
        }

        if(!$user = $this->userService->getOneByEmail($organization, $data['email']))
        {
            throw new AccountException('Invalid email address.');
        }

        $id = $user->getId();
        $token = bin2hex(random_bytes(32));
        $expires = (new \DateTime('+1 hour'))->getTimestamp();

        $user->setRecovery(base64_encode("$id:$token:$expires"));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        try 
        {
            $this->emailService->send(
                $user->getOrganization(),
                $user,
                $user->getEmail(),
                'recovery.request',
                [
                    'name' => $user->getName(),
                    'token' => $token,
                    'expires' => (new \DateTime())->setTimestamp($expires)->format('Y-m-d H:i:s')
                ]
            );
        }
        catch(MailerException $e)
        {
            throw new AccountException('Failed to send recovery email.');
        }

        return $user->getRecovery();
    }

    public function recover(OrganizationEntity $organization, array $data): void
    {
        $constraints = new Assert\Collection([
            'token' => [
                new Assert\NotBlank(['message' => 'Token is required.']),
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

        $parts = explode(':', base64_decode($data['token']));

        if(count($parts) !== 3)
        {
            throw new AccountException('Invalid recovery token format.');
        }

        if(!$user = $this->userService->getOne($organization, $parts[0]))
        {
            throw new AccountException('Invalid recovery token format.');
        }

        if($user->getRecovery() !== $data['token'])
        {
            throw new AccountException('Invalid recovery token.');
        }

        if($parts[2] < time())
        {
            throw new AccountException('Expired recovery token.');
        }

        $this->userService->update($user, ['password' => $data['password']]);

        $user->setRecovery(null);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        try 
        {
            $this->emailService->send(
                $user->getOrganization(),
                $user,
                $user->getEmail(),
                'recovery.recover',
                [
                    'name' => $user->getName(),
                    'datetime' => (new \DateTime())->format('Y-m-d H:i:s')
                ]
            );
        }
        catch(MailerException $e)
        {
            // We don't throw here since the password was already changed successfully
            // Just log the error or handle it as needed
        }
    }
}