<?php

namespace App\Plugins\Extensions\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

use App\Service\ResponseService;

use App\Plugins\Extensions\Service\ExtensionService;
use App\Plugins\Extensions\Exception\ExtensionsException;

use App\Plugins\Account\Service\UserService;
use App\Plugins\Account\Service\OrganizationService;

#[Route('/api')]
class ExtensionController extends AbstractController
{
    private ResponseService $responseService;
    private ExtensionService $extensionService;
    private UserService $userService;
    private OrganizationService $organizationService;

    public function __construct(
        ResponseService $responseService, 
        ExtensionService $extensionService,
        UserService $userService,
        OrganizationService $organizationService
    )
    {
        $this->responseService = $responseService;
        $this->extensionService = $extensionService;
        $this->userService = $userService;
        $this->organizationService = $organizationService;
    }

    #[Route('/extensions', name: 'extensions_get_many', methods: ['GET'])]
    public function getExtensions(Request $request): JsonResponse
    {
        try
        {
            $extensions = $this->extensionService->getMany(
                $request->attributes->get('filters'),
                $request->attributes->get('page'),
                $request->attributes->get('limit')
            );

            foreach ($extensions as &$extension)
            {
                $extension = $extension->toArray();
            }

            return $this->responseService->json(true, 'retrieve', $extensions);
        }
        catch(ExtensionsException $e)
        {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        }
        catch(\Exception $e)
        {
            return $this->responseService->json(false, $e);
        }
    }

    #[Route('/extensions/{id}', name: 'extensions_get_one', methods: ['GET'], requirements: ['id' => '\\d+'])]
    public function getExtensionById(int $id): JsonResponse
    {
        if(!$extension = $this->extensionService->getOne($id))
        {
            return $this->responseService->json(false, 'not-found');
        }

        return $this->responseService->json(true, 'retrieve', $extension->toArray());
    }

    #[Route('/extensions/{id}', name: 'extensions_create#', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function createExtensionById(int $id, Request $request): JsonResponse
    {
        if(!$extension = $this->extensionService->getOne($id))
        {
            return $this->responseService->json(false, 'not-found');
        }

        try
        {
            if($extension->getPanel() === 'user')
            {
                $organization = $request->attributes->get('organization');
                $extensions = (array) $organization->getExtensions();
            
            }
            else 
            {
                $user = $request->attributes->get('user');
                $extensions = (array) $user->getExtensions();
            }
         
            $extensions[] = $extension->getId();

            if($extension->getPanel() === 'user')
            {
                $this->organizationService->update($organization, ['extensions' => array_unique($extensions)]);
            }
            else 
            {
                $this->userService->update($user, ['extensions' => array_unique($extensions)]);
            }

            return $this->responseService->json(true, 'create', $extension->toArray());
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

    #[Route('/extensions/{id}', name: 'extensions_delete#', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function deleteExtensionById(int $id, Request $request): JsonResponse
    {
        if(!$extension = $this->extensionService->getOne($id))
        {
            return $this->responseService->json(false, 'not-found');
        }

        if(!$extension = $this->extensionService->getOne($id))
        {
            return $this->responseService->json(false, 'not-found');
        }

        try
        {
            if($extension->getPanel() === 'user')
            {
                $organization = $request->attributes->get('organization');
                $extensions = (array) $organization->getExtensions();
            
            }
            else 
            {
                $user = $request->attributes->get('user');
                $extensions = (array) $user->getExtensions();
            }

            $extensions = array_filter($extensions, function($id) use($extension) 
            {
                return $id !== $extension->getId();
            });

            if($extension->getPanel() === 'user')
            {
                $this->organizationService->update($organization, ['extensions' => array_unique($extensions)]);
            }
            else 
            {
                $this->userService->update($user, ['extensions' => array_unique($extensions)]);
            }

            return $this->responseService->json(true, 'delete', $extension->toArray());
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
