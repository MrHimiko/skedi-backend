<?php

namespace App\Plugins\Notes\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

use App\Service\ResponseService;
use App\Exception\CrudException;

use App\Plugins\Notes\Exception\NotesException;
use App\Plugins\Notes\Service\NoteService;

use App\Plugins\Storage\Repository\FileRepository;

#[Route('/api')]
class NoteFileController extends AbstractController
{
    private ResponseService $responseService;
    private NoteService $noteService;
    private FileRepository $fileRepository;

    public function __construct(
        ResponseService $responseService,
        NoteService $noteService,
        FileRepository $fileRepository
    )
    {
        $this->responseService = $responseService;
        $this->noteService = $noteService;
        $this->fileRepository = $fileRepository;
    }
 
    #[Route('/notes/{id}/files', name: 'notes_files_get_many#', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getNoteFiles(int $id, Request $request): JsonResponse
    {
        if(!$note = $this->noteService->getOne($request->attributes->get('organization'), $id))
        {
            return $this->responseService->json(false, 'not-found');
        }

        try
        {
            $files = $this->fileRepository->findByIds($note->getOrganization(), $note->getFiles());

            foreach($files as &$file)
            {
                $file = $file->toArray();
            }

            return $this->responseService->json(true, 'retrieve', array_values($files));
        }
        catch(\Exception $e)
        {
            return $this->responseService->json(false, $e);
        }
    }
}