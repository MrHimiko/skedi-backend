<?php

namespace App\Plugins\Notes\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

use App\Plugins\Notes\Service\NoteService;
use App\Plugins\Activity\Service\LogControllerService;

#[Route('/api/notes/{id}')]
class NoteLogsController extends AbstractController
{
    private NoteService $noteService;
    private LogControllerService $logControllerService;

    public function __construct(
        NoteService $noteService,
        LogControllerService $logControllerService
    ) {
        $this->noteService = $noteService;
        $this->logControllerService  = $logControllerService;
    }

    #[Route('/logs', name: 'notes_logs_get_many', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getProspectLogs(int $id, Request $request): JsonResponse
    {
        return $this->logControllerService->getMany($request, 
        [
            'note' => $this->noteService->getOne($request->attributes->get('organization'), $id)
        ]);
    }
}