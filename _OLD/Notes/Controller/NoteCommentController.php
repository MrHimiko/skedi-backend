<?php

namespace App\Plugins\Notes\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

use App\Service\ResponseService;

use App\Plugins\Notes\Service\NoteService;
use App\Plugins\Activity\Service\CommentControllerService;

use App\Plugins\Activity\Entity\CommentEntity;

#[Route('/api/notes/{id}')]
class NoteCommentController extends AbstractController
{
    private ResponseService $responseService;
    private NoteService $noteService;
    private CommentControllerService $commentControllerService;

    public function __construct(
        ResponseService $responseService,
        NoteService $noteService,
        CommentControllerService $commentControllerService
    ) {
        $this->responseService          = $responseService;
        $this->noteService              = $noteService;
        $this->commentControllerService = $commentControllerService;
    }

    #[Route('/comments', name: 'notes_comments_get_many#', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getNoteComments(int $id, Request $request): JsonResponse
    {
        if(!$note = $this->noteService->getOne($request->attributes->get('organization'), $id))
        {
            return $this->responseService->json(false, 'not-found');
        }

        return $this->commentControllerService->getMany($request, ['note' => $note]);
    }

    #[Route('/comments', name: 'notes_comments_create#', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function createNoteComment(int $id, Request $request): JsonResponse
    {
        if(!$note = $this->noteService->getOne($request->attributes->get('organization'), $id))
        {
            return $this->responseService->json(false, 'not-found');
        }

        return $this->commentControllerService->create($request, function(CommentEntity $comment) use($note)
        {
            $comment->setNote($note);
        });
    }
}