<?php

namespace App\Plugins\Activity\Service;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use App\Service\ResponseService;

use App\Plugins\Activity\Service\CommentService;
use App\Plugins\Activity\Exception\ActivityException;

class CommentControllerService
{
    private ResponseService $responseService;
    private CommentService $commentService;

    public function __construct(
        ResponseService $responseService,
        CommentService $commentService
    ) {
        $this->responseService = $responseService;
        $this->commentService  = $commentService;
    }

    public function getMany(Request $request, array $criteria = []): JsonResponse
    {
        try 
        {
            $comments = $this->commentService->getMany(
                $request->attributes->get('organization'), 
                $request->attributes->get('filters'),
                $request->attributes->get('page'),
                $request->attributes->get('limit'),
                $criteria
            );

            foreach ($comments as &$comment) 
            {
                $comment = $comment->toArray();
            }

            return $this->responseService->json(true, 'retrieve', $comments);
        } 
        catch(ActivityException $e) 
        {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        }
        catch(\Exception $e) 
        {
            return $this->responseService->json(false, $e);
        }
    }

    public function create(Request $request, ?callable $callback = null): JsonResponse
    {
        try 
        {
            $comment = $this->commentService->create(
                $request->attributes->get('user'),
                $request->attributes->get('data'),
                $callback
            );

            return $this->responseService->json(true, 'create', $comment->toArray());
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
}
