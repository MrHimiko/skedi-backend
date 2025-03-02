<?php

namespace App\Plugins\Users\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityManagerInterface;

use App\Service\ResponseService;
use App\Service\FilterService;

use App\Plugins\Account\Service\UserService;
use App\Plugins\Account\Service\AuthenticatorService;
use App\Plugins\Account\Entity\UserEntity;

use App\Plugins\Account\Exception\AccountException;

#[Route('/api')]
class UserController extends AbstractController
{
    private ResponseService $responseService;
    private AuthenticatorService $authenticatorService;
    private FilterService $filterService;
    private EntityManagerInterface $entityManager;
    private UserService $userService;

    public function __construct(
        ResponseService $responseService, 
        AuthenticatorService $authenticatorService,
        FilterService $filterService,
        EntityManagerInterface $entityManager,
        UserService $userService
    )
    {
        $this->responseService = $responseService;
        $this->authenticatorService = $authenticatorService;
        $this->filterService = $filterService;
        $this->entityManager = $entityManager;
        $this->userService = $userService;
    }

    #[Route('/users', name: 'users_get_many#users:read:many', methods: ['GET'])]
    public function getUsers(Request $request): JsonResponse
    {
        try
        {
            $users = $this->userService->getMany(
                $request->attributes->get('organization'),
                $request->attributes->get('filters'),
                $request->attributes->get('page'),
                $request->attributes->get('limit')
            );

            foreach ($users as &$user)
            {
                $user = $user->toArray();
            }

            return $this->responseService->json(true, 'retrieve', $users);
        }
        catch(AccountException $e)
        {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        }
        catch(\Exception $e)
        {
            return $this->responseService->json(false, $e);
        }
    }

    #[Route('/users/{id}', 'users_get_one#users:read:one', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getUserById(int $id, Request $request): JsonResponse
    {
        if(!$user = $this->userService->getOne($request->attributes->get('organization'), $id))
        {
            return $this->responseService->json(false, 'not-found');
        }

        return $this->responseService->json(true, 'retrieve', $user->toArray());
    }

    #[Route('/users', name: 'users_create#users:create', methods: ['POST'])]
    public function createNote(Request $request): JsonResponse
    {
        try
        {
            $user = $this->userService->create($request->attributes->get('organization'), $request->attributes->get('data'));

            return $this->responseService->json(true, 'create', $user->toArray());
        }
        catch(AccountException $e)
        {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        }
        catch(\Exception $e)
        {
            return $this->responseService->json(false, $e);
        }
    }

    #[Route('/users/{id}', name: 'users_delete#users:delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function deleteUserById(int $id, Request $request): JsonResponse
    {
        if(!$user = $this->userService->getOne($request->attributes->get('organization'), $id))
        {
            return $this->responseService->json(false, 'not-found');
        }

        if($user->getId() === $request->attributes->get('user')->getId())
        {
            return $this->responseService->json(false, 'You are not allowed to delete your own account.', null, 400);
        }
 
        try
        {
            $this->userService->delete($user);

            return $this->responseService->json(true, 'delete', $user->toArray());
        }
        catch(AccountException $e)
        {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        }
        catch (\Exception $e)
        {
            return $this->responseService->json(false, $e);
        }
    }
}