<?php

namespace App\Plugins\Storage\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

use App\Service\ResponseService;
use App\Exception\CrudException;

use App\Plugins\Storage\Service\FolderService;

#[Route('/api/storage')]
class FolderController extends AbstractController
{
    private ResponseService $responseService;
    private FolderService $folderService;

    public function __construct(
        ResponseService $responseService, 
        FolderService $folderService,
    )
    {
        $this->responseService = $responseService;
        $this->folderService = $folderService;
    }

    #[Route('/folders', name: 'storage_folders_get_many#storage:folders:read:many', methods: ['GET'])]
    public function getFolders(Request $request): JsonResponse
    {
        try 
        {
            $folders = $this->folderService->getMany(
                $request->attributes->get('organization'), 
                $request->attributes->get('filters'),
                $request->attributes->get('page'),
                $request->attributes->get('limit')
            );

            foreach ($folders as &$folder) 
            {
                $folder = $folder->toArray();
            }

            return $this->responseService->json(true, 'retrieve', $folders);
        } 
        catch(CrudException $e) 
        {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        }
        catch(\Exception $e) 
        {
            return $this->responseService->json(false, $e);
        }
    }

    #[Route('/folders/{id}', name: 'storage_folders_get_one#storage:folders:read:one', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getFolderById(int $id, Request $request): JsonResponse
    {
        if(!$folder = $this->folderService->getOne($request->attributes->get('organization'), $id)) 
        {
            return $this->responseService->json(false, 'not-found');
        }

        return $this->responseService->json(true, 'retrieve', $folder->toArray());
    }

    #[Route('/folders', name: 'storage_folders_create#', methods: ['POST'])]
    public function createFolder(Request $request): JsonResponse
    {
        try 
        {
            $folder = $this->folderService->create($request->attributes->get('organization'), $request->attributes->get('data'));

            return $this->responseService->json(true, 'create', $folder->toArray());
        } 
        catch(CrudException $e) 
        {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        }
        catch(\Exception $e) 
        {
            return $this->responseService->json(false, $e);
        }
    }

    #[Route('/folders/{id}', name: 'storage_folders_update#storage:folders:update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function updateFolderById(int $id, Request $request): JsonResponse
    {
        if(!$folder = $this->folderService->getOne($request->attributes->get('organization'), $id)) 
        {
            return $this->responseService->json(false, 'not-found');
        }

        try 
        {
            $this->folderService->update($folder, $request->attributes->get('data'));

            return $this->responseService->json(true, 'update', $folder->toArray());
        } 
        catch(CrudException $e) 
        {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        }
        catch(\Exception $e) 
        {
            return $this->responseService->json(false, $e);
        }
    }

    #[Route('/folders/{id}', name: 'storage_folders_delete#storage:folders:delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function deleteFolderById(int $id, Request $request): JsonResponse
    {
        if(!$folder = $this->folderService->getOne($request->attributes->get('organization'), $id)) 
        {
            return $this->responseService->json(false, 'not-found');
        }

        try 
        {
            $this->folderService->delete($folder);

            return $this->responseService->json(true, 'delete', $folder->toArray());
        } 
        catch(\Exception $e) 
        {
            return $this->responseService->json(false, $e);
        }
    }
}