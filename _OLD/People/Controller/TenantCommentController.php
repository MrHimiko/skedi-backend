<?php

namespace App\Plugins\People\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

use App\Service\ResponseService;

use App\Plugins\People\Service\TenantService;
use App\Plugins\Activity\Service\CommentControllerService;

use App\Plugins\Activity\Entity\CommentEntity;

#[Route('/api/people/tenants/{id}')]
class TenantCommentController extends AbstractController
{
    private ResponseService $responseService;
    private TenantService $tenantService;
    private CommentControllerService $commentControllerService;

    public function __construct(
        ResponseService $responseService,
        TenantService $tenantService,
        CommentControllerService $commentControllerService
    ) {
        $this->responseService          = $responseService;
        $this->tenantService            = $tenantService;
        $this->commentControllerService = $commentControllerService;
    }

    #[Route('/comments', name: 'tenants_comments_get_many#', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getTenantComments(int $id, Request $request): JsonResponse
    {
        if(!$tenant = $this->tenantService->getOne($request->attributes->get('organization'), $id))
        {
            return $this->responseService->json(false, 'not-found');
        }

        return $this->commentControllerService->getMany($request, ['tenant' => $tenant]);
    }

    #[Route('/comments', name: 'tenants_comments_create#', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function createTenantComment(int $id, Request $request): JsonResponse
    {
        if(!$tenant = $this->tenantService->getOne($request->attributes->get('organization'), $id))
        {
            return $this->responseService->json(false, 'not-found');
        }

        return $this->commentControllerService->create($request, function(CommentEntity $comment) use($tenant)
        {
            $comment->setTenant($tenant);
        });
    }
}