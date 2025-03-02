<?php

namespace App\Plugins\Storage\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

use Doctrine\ORM\EntityManagerInterface;

use App\Plugins\Storage\Exception\StorageException;

use App\Service\ResponseService;

use App\Plugins\Storage\Service\FileService;
use App\Plugins\Storage\Service\UploadService;

#[Route('/api/storage')]
class FileController extends AbstractController
{
    private ResponseService $responseService;
    private FileService $fileService;
    private UploadService $uploadService;

    public function __construct(
        ResponseService $responseService, 
        FileService $fileService,
        UploadService $uploadService,
    )
    {
        $this->responseService = $responseService;
        $this->fileService = $fileService;
        $this->uploadService = $uploadService;
    }

    #[Route('/files', name: 'storage_files_get_many#storage:files:read:many', methods: ['GET'])]
    public function getFiles(Request $request): JsonResponse
    {
        try 
        {
            $files = $this->fileService->getMany(
                $request->attributes->get('organization'), 
                $request->attributes->get('filters'),
                $request->attributes->get('page'),
                $request->attributes->get('limit')
            );
    
            foreach($files as &$file)
            {
                $file = $file->toArray();
            }
    
            return $this->responseService->json(true, 'retrieve', $files);
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

    #[Route('/files/{id}', name: 'storage_files_get_one#storage:files:read:one', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getFile(int $id, Request $request): JsonResponse
    {
        if(!$file = $this->fileService->getOne($request->attributes->get('organization'), $id))
        {
            return $this->responseService->json(false, 'not-found');
        }

        return $this->responseService->json(true, 'retrieve', $file->toArray());
    }

    #[Route('/files', name: 'storage_files_create#storage:files:create', methods: ['POST'])]
    public function createFile(Request $request): JsonResponse
    {
        $data = $request->attributes->get('data');

        try 
        {
            $file = null;

            if(isset($data['url']) && is_string($data['url']))
            {
                $file = $this->uploadService->uploadFromUrl($request->attributes->get('organization'), $data['url']);
            }

            else if (
                isset($data['content'], $data['name'], $data['mimetype']) && 
                is_string($data['content']) && 
                is_string($data['name']) && 
                is_string($data['mimetype'])
            ) 
            {
                $file = $this->uploadService->uploadContent($request->attributes->get('organization'), $data['content'], $data['name'], $data['mimetype']);
            }

            else if($request->files->get('file') && $request->files->get('file')->isValid())
            {
                $file = $this->uploadService->uploadFile($request->attributes->get('organization'), $request->files->get('file'));
            }

            if(!$file)
            {
                throw new StorageException("Please choose a way to upload. Provide 'url' or ('content', 'name', 'mimetype') or include file along the request.");
            }

            return $this->responseService->json(true, 'File uploaded successfully.', $file->toArray());
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

    #[Route('/files/{id}', name: 'storage_files_update#storage:files:update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function updateFile(int $id, Request $request): JsonResponse
    {
        if(!$file = $this->fileService->getOne($request->attributes->get('organization'), $id))
        {
            return $this->responseService->json(false, 'not-found');
        }

        try 
        {
            $this->fileService->update($file, $request->attributes->get('data'));

            return $this->responseService->json(true, 'update', $file->toArray());
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

    #[Route('/files/{id}', name: 'storage_files_delete#storage:files:delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function deleteFile(int $id, Request $request): JsonResponse
    {
        if(!$file = $this->fileService->getOne($request->attributes->get('organization'), $id))
        {
            return $this->responseService->json(false, 'not-found');
        }

        try 
        {
            $this->fileService->delete($file);

            return $this->responseService->json(true, 'delete', $file->toArray());
        }
        catch(\Exception $e) 
        {
            return $this->responseService->json(false, $e);
        }
    }
}
