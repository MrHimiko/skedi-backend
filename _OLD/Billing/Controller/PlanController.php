<?php

namespace App\Plugins\Billing\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

use App\Plugins\Billing\Service\PlanService;

use App\Service\ResponseService;

use App\Plugins\Billing\Exception\BillingException;

#[Route('/api/billing')]
class PlanController extends AbstractController
{
    private ResponseService $responseService;
    private PlanService $planService;

    public function __construct(
        ResponseService $responseService, 
        PlanService $planService
    )
    {
        $this->responseService = $responseService;
        $this->planService = $planService;
    }

    #[Route('/plans', name: 'billing_plans_get_many', methods: ['GET'])]
    public function getPlans(Request $request): JsonResponse
    {
        try 
        {
            $plans = $this->planService->getMany(
                $request->attributes->get('filters'),
                $request->attributes->get('page'),
                $request->attributes->get('limit')
            );

            foreach ($plans as &$plan) 
            {
                $plan = $plan->toArray();
            }

            return $this->responseService->json(true, 'retrieve', $plans);
        } 
        catch(BillingException $e) 
        {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        }
        catch(\Exception $e) 
        {
            return $this->responseService->json(false, $e);
        }
    }

    #[Route('/plans/{id}', name: 'billing_plans_get_one', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getPlan(int $id, Request $request): JsonResponse
    {
        if(!$plan = $this->planService->getOne($id))
        {
            return $this->responseService->json(false, 'not-found');
        }

        return $this->responseService->json(true, 'retrieve', $plan->toArray());
    }
}