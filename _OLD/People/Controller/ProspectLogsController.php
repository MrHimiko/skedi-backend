<?php

namespace App\Plugins\People\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

use App\Plugins\People\Service\TenantService;
use App\Plugins\Activity\Service\LogControllerService;

#[Route('/api/people/prospects/{id}')]
class ProspectLogsController extends AbstractController
{
    private TenantService $tenantService;
    private LogControllerService $logControllerService;

    public function __construct(
        TenantService $tenantService,
        LogControllerService $logControllerService
    ) {
        $this->tenantService         = $tenantService;
        $this->logControllerService  = $logControllerService;
    }

    #[Route('/logs', name: 'prospects_logs_get_many', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getProspectLogs(int $id, Request $request): JsonResponse
    {
        return $this->logControllerService->getMany($request, 
        [
            'prospect' => $this->tenantService->getOne($request->attributes->get('organization'), $id, true)
        ]);
    }
}