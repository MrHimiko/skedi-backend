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

use App\Plugins\Storage\Service\FileService;

use App\Plugins\Activity\Service\LogService;
use App\Plugins\Activity\Service\CommentService;
use App\Plugins\Activity\Exception\ActivityException;

#[Route('/api')]
class NoteController extends AbstractController
{
    private FileService $fileService;
    private ResponseService $responseService;
    private NoteService $noteService;
    private LogService $logService;
    private CommentService $commentService;

    public function __construct(
        FileService $fileService,
        ResponseService $responseService,
        NoteService $noteService,
        LogService $logService,
        CommentService $commentService

    )
    {
        $this->fileService = $fileService;
        $this->responseService = $responseService;
        $this->noteService = $noteService;
        $this->logService = $logService;
        $this->commentService = $commentService;
    }

    #[Route('/notes', name: 'notes_get_many#notes:read:many', methods: ['GET'])]
    public function getNotes(Request $request): JsonResponse
    {
        try
        {
            $notes = $this->noteService->getMany(
                $request->attributes->get('organization'),
                $request->attributes->get('filters'),
                $request->attributes->get('page'),
                $request->attributes->get('limit')
            );

            foreach ($notes as &$note)
            {
                $note = $note->toArray();
            }

            return $this->responseService->json(true, 'retrieve', $notes);
        }
        catch(NotesException $e)
        {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        }
        catch(\Exception $e)
        {
            return $this->responseService->json(false, $e);
        }
    }

    #[Route('/notes/{id}', name: 'notes_get_one#notes:read:one', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getNoteById(int $id, Request $request): JsonResponse
    {
        if(!$note = $this->noteService->getOne($request->attributes->get('organization'), $id))
        {
            return $this->responseService->json(false, 'not-found');
        }

        return $this->responseService->json(true, 'retrieve', $note->toArray());
    }

    #[Route('/notes', name: 'notes_create#notes:create', methods: ['POST'])]
    public function createNote(Request $request): JsonResponse
    {
        try
        {
            $note = $this->noteService->create($request->attributes->get('user'), $request->attributes->get('data'));

            $this->fileService->convert($note->getOrganization(), [$note], 'Files');

            return $this->responseService->json(true, 'create', $note->toArray());
        }
        catch(NotesException $e)
        {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        }
        catch(\Exception $e)
        {
            return $this->responseService->json(false, $e);
        }
    }

    #[Route('/notes/{id}', name: 'notes_update#notes:update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function updateNoteById(int $id, Request $request): JsonResponse
    {
        if(!$note = $this->noteService->getOne($request->attributes->get('organization'), $id))
        {
            return $this->responseService->json(false, 'not-found');
        }

        try
        {
            $this->noteService->update($note, $request->attributes->get('user'), $request->attributes->get('data'));

            return $this->responseService->json(true, 'update', $note->toArray());
        }
        catch(NotesException $e)
        {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        }
        catch(\Exception $e)
        {
            return $this->responseService->json(false, $e);
        }
    }

    #[Route('/notes/{id}', name: 'notes_delete#notes:delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function deleteNoteById(int $id, Request $request): JsonResponse
    {
        if(!$note = $this->noteService->getOne($request->attributes->get('organization'), $id))
        {
            return $this->responseService->json(false, 'not-found');
        }

        try
        {
            $this->noteService->delete($note);

            return $this->responseService->json(true, 'delete', $note->toArray());
        }
        catch(NotesException $e)
        {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        }
        catch (\Exception $e)
        {
            return $this->responseService->json(false, $e);
        }
    }
}