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
class ProspectController extends AbstractController
{
    private ResponseService $responseService;
    private TenantService $tenantService;

    public function __construct(
        ResponseService $responseService,
        TenantService $tenantService
    ) {
        $this->responseService = $responseService;
        $this->tenantService   = $tenantService;
    }

    #[Route('/prospects', name: 'prospects_get_many#', methods: ['GET'])]
    public function getProspects(Request $request): JsonResponse
    {
        try {
            $prospects = $this->tenantService->getMany(
                $request->attributes->get('organization'),
                $request->attributes->get('filters') ,
                $request->attributes->get('page'),
                $request->attributes->get('limit'),
                true
            );

            foreach ($prospects as &$prospect) 
            {
                $prospect = $prospect->toArray();
            }

            return $this->responseService->json(true, 'retrieve', $prospects);
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

    #[Route('/prospects/{id}', name: 'prospects_get_one#', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getProspectById(int $id, Request $request): JsonResponse
    {
        if(!$prospect = $this->tenantService->getOne($request->attributes->get('organization'), $id, true))
        {
            return $this->responseService->json(false, 'not-found');
        }

        return $this->responseService->json(true, 'retrieve', $prospect->toArray());
    }

    #[Route('/prospects', name: 'prospects_create#', methods: ['POST'])]
    public function createProspect(Request $request): JsonResponse
    {
        try 
        {
            $prospect = $this->tenantService->create(
                $request->attributes->get('organization'),
                $request->attributes->get('user'),
                $request->attributes->get('data'),
                true
            );

            return $this->responseService->json(true, 'create', $prospect->toArray());
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

    #[Route('/prospects/{id}', name: 'prospects_update#', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function updateProspectById(int $id, Request $request): JsonResponse
    {
        if(!$prospect = $this->tenantService->getOne($request->attributes->get('organization'), $id, true))
        {
            return $this->responseService->json(false, 'not-found');
        }

        try 
        {
            $this->tenantService->update($prospect, $request->attributes->get('data'));
            return $this->responseService->json(true, 'update', $prospect->toArray());
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

    #[Route('/prospects/{id}', name: 'prospects_delete#', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function deleteProspectById(int $id, Request $request): JsonResponse
    {
        if(!$prospect = $this->tenantService->getOne($request->attributes->get('organization'), $id, true))
        {
            return $this->responseService->json(false, 'not-found');
        }

        try 
        {
            $this->tenantService->delete($prospect);
            return $this->responseService->json(true, 'delete', $prospect->toArray());
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
}
