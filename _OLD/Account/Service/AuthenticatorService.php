<?php

namespace App\Plugins\Account\Service;

use App\Plugins\Account\Entity\UserEntity;
use App\Plugins\Account\Entity\OrganizationEntity;

use App\Plugins\Account\Repository\TokenRepository;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

class AuthenticatorService
{
    private TokenRepository $tokenRepository;

    public function __construct(TokenRepository $tokenRepository)
    {
        $this->tokenRepository = $tokenRepository;
    }

    public function getUser(?string $authorization, ?string $permission): ?UserEntity
    {
        if(!$authorization = $this->getAuthorization($authorization))
        {
            return null;
        }

        if(!$token = $this->tokenRepository->findOneBy(['id' => $authorization->id, 'organization' => $authorization->organization]))
        {
            return null;
        }

        if($token->getValue() !== $authorization->token)
        {
            return null;
        }


        $user = $token->getUser();
        $permissions = count($user->getPermissions()) ? $user->getPermissions() : $user->getRole()->getPermissions();

        return $permission === null ? $user : (in_array($permission, $permissions) ? $user : null);
    }

    private function getAuthorization(?string $authorization): ?object
    {
        if($authorization === null)
        {
            return null;
        }

        if(!str_starts_with($authorization, 'Bearer ')) 
        {
            return null;
        }

        $token = substr($authorization, 7);
        $parts = explode(':', base64_decode($token));

        if(count($parts) !== 4)
        {
            return null;
        }

        return (object) [
            'id'           => (int) $parts[0],
            'organization' => (int) $parts[1],
            'value'        => $parts[2],
            'expires'      => (int) $parts[3],
            'token'        => $token
        ];
    }
}
