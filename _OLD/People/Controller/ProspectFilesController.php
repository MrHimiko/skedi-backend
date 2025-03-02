<?php

namespace App\Plugins\People\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

use App\Plugins\People\Service\TenantService;
use App\Plugins\Storage\Service\FileControllerService;

#[Route('/api/people/prospects/{id}')]
class ProspectFilesController extends AbstractController
{
    private TenantService $tenantService;
    private FileControllerService $fileControllerService;

    public function __construct(
        TenantService $tenantService,
        FileControllerService $fileControllerService
    ) {
        $this->tenantService        = $tenantService;
        $this->fileControllerService = $fileControllerService;
    }

    #[Route('/files', name: 'prospects_files_get_many', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getProspectFiles(int $id, Request $request): JsonResponse
    {
        return $this->fileControllerService->getMany($request, 
        [
            'prospect' => $this->tenantService->getOne($request->attributes->get('organization'), $id, true)
        ]);
    }
}