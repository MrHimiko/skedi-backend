<?php

namespace App\Plugins\Metrics\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

use App\Service\ResponseService;

use App\Plugins\Metrics\Exception\MetricsException;
use App\Plugins\Metrics\Service\MetricService;

#[Route('/api/metrics')]
class MainController extends AbstractController
{
    private ResponseService $responseService;
    private MetricService $metricService;

    public function __construct(
        ResponseService $responseService, 
        MetricService $metricService
    )
    {
        $this->responseService = $responseService;
        $this->metricService   = $metricService;
    }

    #[Route('/{plugin}/total', name: 'metrics_total', methods: ['GET'], requirements: ['plugin' => '\w+'])]
    public function total(string $plugin, Request $request): JsonResponse
    {
        try 
        {
            $total = $this->metricService->total($plugin, $request->attributes->get('filters'));

            return $this->responseService->json(true, 'retreive', $total);
        }
        catch (MetricsExpcetion $e) 
        {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        }
        catch (\Exception $e) 
        {
            return $this->responseService->json(false, $e);
        }
    }

    #[Route('/{plugin}/summary', name: 'metrics_summary', methods: ['GET'], requirements: ['plugin' => '\w+'])]
    public function summary(string $plugin, Request $request): JsonResponse
    {
        try 
        {
            $summary = $this->metricService->summary($plugin, $request->attributes->get('filters'), $request->attributes->get('group'), $request->attributes->get('limit'));

            return $this->responseService->json(true, 'retreive', $summary);
        }
        catch (MetricsExpcetion $e) 
        {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        }
        catch (\Exception $e) 
        {
            return $this->responseService->json(false, $e);
        }
    }
}
