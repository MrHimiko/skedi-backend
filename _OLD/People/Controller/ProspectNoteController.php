<?php

namespace App\Plugins\People\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

use App\Service\ResponseService;

use App\Plugins\People\Service\TenantService;
use App\Plugins\Notes\Service\NoteControllerService;

use App\Plugins\Notes\Entity\NoteEntity;

#[Route('/api/people/prospects/{id}')]
class ProspectNoteController extends AbstractController
{
    private ResponseService $responseService;
    private TenantService $tenantService;
    private NoteControllerService $noteControllerService;

    public function __construct(
        ResponseService $responseService,
        TenantService $tenantService,
        NoteControllerService $noteControllerService
    ) {
        $this->responseService       = $responseService;
        $this->tenantService         = $tenantService;
        $this->noteControllerService = $noteControllerService;
    }

    #[Route('/notes', name: 'prospects_notes_get_many', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getProspectNotes(int $id, Request $request): JsonResponse
    {
        if(!$prospect = $this->tenantService->getOne($request->attributes->get('organization'), $id, true))
        {
            return $this->responseService->json(false, 'not-found');
        }

        return $this->noteControllerService->getMany($request, ['prospect' => $prospect]);
    }

    #[Route('/notes', name: 'prospects_notes_create#', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function createProspectNote(int $id, Request $request): JsonResponse
    {
        if(!$prospect = $this->tenantService->getOne($request->attributes->get('organization'), $id, true))
        {
            return $this->responseService->json(false, 'not-found');
        }

        return $this->noteControllerService->create($request, function(NoteEntity $note) use($prospect)
        {
            $note->setProspect($prospect);
        });
    }
}