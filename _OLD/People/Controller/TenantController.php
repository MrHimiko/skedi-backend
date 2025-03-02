<?php

namespace App\Plugins\People\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

use App\Service\ResponseService;
use App\Plugins\People\Exception\PeopleException;
use App\Plugins\People\Service\TenantService;

#[Route('/api/people')]
class TenantController extends AbstractController
{
    private ResponseService $responseService;
    private TenantService $tenantService;

    public function __construct(
        ResponseService $responseService,
        TenantService $tenantService
    ) {
        $this->responseService = $responseService;
        $this->tenantService = $tenantService;
    }

    #[Route('/tenants', name: 'tenants_get_many#', methods: ['GET'])]
    public function getTenants(Request $request): JsonResponse
    {
        try {
            $tenants = $this->tenantService->getMany(
                $request->attributes->get('organization'),
                $request->attributes->get('filters'),
                $request->attributes->get('page'),
                $request->attributes->get('limit'),
            );

            foreach ($tenants as &$tenant) 
            {
                $tenant = $tenant->toArray();
            }

            return $this->responseService->json(true, 'retrieve', $tenants);
        } 
        catch (PeopleException $e) 
        {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        } 
        catch (\Exception $e) 
        {
            return $this->responseService->json(false, $e);
        }
    }

    #[Route('/tenants/{id}', name: 'tenants_get_one#', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getTenantById(int $id, Request $request): JsonResponse
    {
        if(!$tenant = $this->tenantService->getOne($request->attributes->get('organization'), $id)) 
        {
            return $this->responseService->json(false, 'not-found');
        }

        return $this->responseService->json(true, 'retrieve', $tenant->toArray());
    }

    #[Route('/tenants', name: 'tenants_create#', methods: ['POST'])]
    public function createTenant(Request $request): JsonResponse
    {
        try 
        {
            $tenant = $this->tenantService->create(
                $request->attributes->get('organization'),
                $request->attributes->get('user'),
                $request->attributes->get('data')
            );

            return $this->responseService->json(true, 'create', $tenant->toArray());
        } 
        catch (PeopleException $e) 
        {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        } 
        catch (\Exception $e) 
        {
            return $this->responseService->json(false, $e);
        }
    }

    #[Route('/tenants/{id}', name: 'tenants_update#', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function updateTenantById(int $id, Request $request): JsonResponse
    {
        if(!$tenant = $this->tenantService->getOne($request->attributes->get('organization'), $id)) 
        {
            return $this->responseService->json(false, 'not-found');
        }

        try 
        {
            $this->tenantService->update($tenant, $request->attributes->get('data'));

            return $this->responseService->json(true, 'update', $tenant->toArray());
        } 
        catch (PeopleException $e) 
        {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        } 
        catch (\Exception $e) 
        {
            return $this->responseService->json(false, $e);
        }
    }

    #[Route('/tenants/{id}', name: 'tenants_delete#', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function deleteTenantById(int $id, Request $request): JsonResponse
    {
        if (!$tenant = $this->tenantService->getOne($request->attributes->get('organization'), $id)) {
            return $this->responseService->json(false, 'not-found');
        }

        try {
            $this->tenantService->delete($tenant);

            return $this->responseService->json(true, 'delete', $tenant->toArray());
        } catch (PeopleException $e) {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        } catch (\Exception $e) {
            return $this->responseService->json(false, $e);
        }
    }
}
