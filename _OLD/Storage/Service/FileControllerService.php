<?php

namespace App\Plugins\Storage\Service;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use App\Service\ResponseService;

use App\Plugins\Storage\Service\FileService;
use App\Plugins\Storage\Exception\StorageException;

class FileControllerService
{
    private ResponseService $responseService;
    private FileService $fileService;

    public function __construct(
        ResponseService $responseService,
        FileService $fileService
    ) {
        $this->responseService = $responseService;
        $this->fileService     = $fileService;
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
            $notes = $this->fileService->getMany(
                $request->attributes->get('organization'), 
                $request->attributes->get('filters'),
                $request->attributes->get('page'),
                $request->attributes->get('limit'),
                $criteria
            );

            foreach ($notes as &$note) 
            {
                $note = $note->toArray();
            }

            return $this->responseService->json(true, 'retrieve', $notes);
        } 
        catch(StorageException $e) 
        {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        }
        catch(\Exception $e) 
        {
            return $this->responseService->json(false, $e);
        }
    }
}
