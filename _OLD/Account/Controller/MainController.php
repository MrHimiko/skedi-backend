<?php

namespace App\Plugins\Account\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;

use App\Service\ResponseService;
use App\Plugins\Account\Service\LoginService;
use App\Plugins\Account\Service\RegisterService;
use App\Plugins\Account\Service\LogoutService;
use App\Plugins\Account\Service\RecoveryService;
use App\Plugins\Account\Service\UserService;
use App\Plugins\Account\Service\OrganizationService;

use App\Plugins\Account\Exception\AccountException;

#[Route('/api/account')]
class MainController extends AbstractController
{
    private ResponseService $responseService;
    private LoginService $loginService;
    private RegisterService $registerService;
    private LogoutService $logoutService;
    private RecoveryService $recoveryService;
    private UserService $userService;
    private OrganizationService $organizationService;

    public function __construct(
        ResponseService $responseService, 
        LoginService $loginService, 
        RegisterService $registerService, 
        LogoutService $logoutService,
        RecoveryService $recoveryService,
        UserService $userService,
        OrganizationService $organizationService
    )
    {
        $this->responseService = $responseService;
        $this->loginService = $loginService;
        $this->registerService = $registerService;
        $this->logoutService = $logoutService;
        $this->recoveryService = $recoveryService;
        $this->userService = $userService;
        $this->organizationService = $organizationService;
    }

    #[Route('/login', name: 'account_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        try 
        {
            $token = $this->loginService->login($request->attributes->get('organization'), $request->attributes->get('data'));

            return $this->responseService->json(true, 'Login successful.', [
                'token'   => $token->getValue(),
                'expires' => $token->getExpires()->format('Y-m-d H:i:s')
            ]);
        } 
        catch (AccountException $e) 
        {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        }
        catch (\Exception $e) 
        {
            return $this->responseService->json(false, $e, null, 400);
        }
    }

    #[Route('/register', name: 'account_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        try 
        {
            $user = $this->registerService->register(null, $request->attributes->get('data'));

            return $this->responseService->json(true, 'Register successful.', $user->toArray());
        } 
        catch (AccountException $e) 
        {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        }
        catch (\Exception $e) 
        {
            return $this->responseService->json(false, $e, null, 400);
        }
    }

    #[Route('/logout', name: 'account_logout#', methods: ['POST'])]
    public function logout(Request $request): JsonResponse
    {
        try 
        {
            $this->logoutService->logout($request->attributes->get('user'));

            return $this->responseService->json(false, 'Successfully logged out.');
        }
        catch (\Exception $e) 
        {
            return $this->responseService->json(false, $e, null, 400);
        }
    }

    #[Route('/recovery/request', name: 'account_recovery_request', methods: ['POST'])]
    public function recoveryRequest(Request $request): JsonResponse
    {
        try 
        {
            $token = $this->recoveryService->request($request->attributes->get('organization'), $request->attributes->get('data'));

            return $this->responseService->json(true, 'Successfully requested password recovery.', ['token' => $token]);
        }
        catch (AccountException $e) 
        {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        }
        catch (\Exception $e) 
        {
            return $this->responseService->json(false, $e, null, 400);
        }
    }

    #[Route('/recovery/recover', name: 'account_recovery_recover', methods: ['POST'])]
    public function recoveryRecover(Request $request): JsonResponse
    {
        try 
        {
            $this->recoveryService->recover($request->attributes->get('organization'), $request->attributes->get('data'));

            return $this->responseService->json(true, 'Successfully recovered account.');
        }
        catch (AccountException $e) 
        {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        }
        catch (\Exception $e) 
        {
            return $this->responseService->json(false, $e, null, 400);
        }
    }

    #[Route('/user', name: 'account_get_user#', methods: ['GET'])]
    public function getAccountUser(Request $request): JsonResponse
    {
        $user = $request->attributes->get('user');

        return $this->responseService->json(true, 'retrieve', $user->toArray());
    }

    #[Route('/user', name: 'account_update_user#', methods: ['PUT'])]
    public function updateAccountUser(Request $request): JsonResponse
    {
        $user = $request->attributes->get('user');

        try 
        {
            $this->userService->update($user, $request->attributes->get('data'));

            return $this->responseService->json(true, 'update', $user->toArray());
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

    #[Route('/organization', name: 'account_update_organization#', methods: ['PUT'])]
    public function updateAccountOrganization(Request $request): JsonResponse
    {
        $organization = $request->attributes->get('organization');

        try 
        {
            $this->organizationService->update($organization, $request->attributes->get('data'));

            return $this->responseService->json(true, 'update', $organization->toArray());
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
}