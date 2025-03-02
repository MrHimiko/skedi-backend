<?php

namespace App\Plugins\Activity\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

use App\Service\ResponseService;
use App\Exception\CrudException;

use App\Plugins\Activity\Exception\ActivityException;

use App\Plugins\Activity\Service\LogService;

#[Route('/api/activity')]
class LogController extends AbstractController
{
    private ResponseService $responseService;
    private LogService $logService;

    public function __construct(
        ResponseService $responseService, 
        LogService $logService
    ) {
        $this->responseService = $responseService;
        $this->logService = $logService;
    }

    #[Route('/logs', name: 'activity_logs_get_many', methods: ['GET'])]
    public function getLogs(Request $request): JsonResponse
    {
        try 
        {
            $logs = $this->logService->getMany(
                $request->attributes->get('organization'), 
                $request->attributes->get('filters'),
                $request->attributes->get('page'),
                $request->attributes->get('limit')
            );

            foreach ($logs as &$log) 
            {
                $log = $this->logService->processLog($log);
            }

            return $this->responseService->json(true, 'retrieve', $logs);
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

    #[Route('/logs/{id}', name: 'activity_logs_get_one#activity:logs:read:many', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getLogById(int $id, Request $request): JsonResponse
    {
        if(!$log = $this->logService->getOne($request->attributes->get('organization'), $id)) 
        {
            return $this->responseService->json(false, 'not-found');
        }

        return $this->responseService->json(true, 'retrieve', $this->logService->processLog($log));
    }
}
