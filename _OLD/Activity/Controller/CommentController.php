<?php

namespace App\Plugins\Activity\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

use App\Service\ResponseService;
use App\Exception\CrudException;

use App\Plugins\Activity\Exception\ActivityException;
use App\Plugins\Activity\Service\CommentService;

#[Route('/api/activity')]
class CommentController extends AbstractController
{
    private ResponseService $responseService;
    private CommentService $commentService;

    public function __construct(
        ResponseService $responseService, 
        CommentService $commentService
    )
    {
        $this->responseService = $responseService;
        $this->commentService   = $commentService;
    }

    #[Route('/comments', name: 'activity_comments_get_many#activity:comments:read:many', methods: ['GET'])]
    public function getComments(Request $request): JsonResponse
    {
        try 
        {
            $comments = $this->commentService->getMany(
                $request->attributes->get('organization'),
                $request->attributes->get('filters'),
                $request->attributes->get('page'),
                $request->attributes->get('limit')
            );

            foreach ($comments as &$comment) 
            {
                $comment = $this->commentService->processComment($comment);
            }

            return $this->responseService->json(true, 'retrieve', $comments);
        } 
        catch (ActivityException $e) 
        {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        }
        catch (\Exception $e) 
        {
            return $this->responseService->json(false, $e);
        }
    }

    #[Route('/comments/{id}', name: 'activity_comments_get_one#activity:comments:read:one', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getCommentById(int $id, Request $request): JsonResponse
    {
        if (! $comment = $this->commentService->getOne($request->attributes->get('organization'), $id)) 
        {
            return $this->responseService->json(false, 'not-found');
        }

        return $this->responseService->json(true, 'retrieve', $this->commentService->processComment($comment));
    }
}
