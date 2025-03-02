<?php

namespace App\Plugins\Activity\Service;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use App\Service\ResponseService;

use App\Plugins\Activity\Service\LogService;
use App\Plugins\Activity\Exception\ActivityException;

class LogControllerService
{
    private ResponseService $responseService;
    private LogService $logService;

    public function __construct(
        ResponseService $responseService,
        LogService $logService
    ) {
        $this->responseService = $responseService;
        $this->logService      = $logService;
    }

    public function getMany(Request $request, array $criteria = []): JsonResponse
    {
        foreach($criteria as $value)
        {
            if(!$value)
            {
                return $this->responseService->json(false, 'not-found');
            }
        }

        try 
        {
            $logs = $this->logService->getMany(
                $request->attributes->get('organization'), 
                $request->attributes->get('filters'),
                $request->attributes->get('page'),
                $request->attributes->get('limit'),
                $criteria
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
}
