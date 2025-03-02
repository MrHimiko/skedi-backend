<?php

namespace App\Plugins\Widgets\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

use App\Service\ResponseService;
use App\Exception\CrudException;
use App\Plugins\Widgets\Exception\WidgetsException;
use App\Plugins\Widgets\Service\ItemService;

#[Route('/api/widgets')]
class ItemController extends AbstractController
{
    private ResponseService $responseService;
    private ItemService $itemService;

    public function __construct(
        ResponseService $responseService,
        ItemService $itemService
    )
    {
        $this->responseService = $responseService;
        $this->itemService = $itemService;
    }

    #[Route('/items', name: 'widgets_items_get_many#widgets:items:read:many', methods: ['GET'])]
    public function getItems(Request $request): JsonResponse
    {
        try
        {
            $items = $this->itemService->getMany(
                $request->attributes->get('user'),
                $request->attributes->get('filters'),
                $request->attributes->get('page'),
                $request->attributes->get('limit')
            );

            foreach ($items as &$item)
            {
                $item = $item->toArray();
            }

            return $this->responseService->json(true, 'retrieve', $items);
        }
        catch(WidgetsException $e)
        {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        }
        catch(\Exception $e)
        {
            return $this->responseService->json(false, $e);
        }
    }

    #[Route('/items/{id}', name: 'widgets_items_get_one#widgets:items:read:one', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getItemById(int $id, Request $request): JsonResponse
    {
        if(!$item = $this->itemService->getOne($request->attributes->get('user'), $id))
        {
            return $this->responseService->json(false, 'not-found');
        }

        return $this->responseService->json(true, 'retrieve', $item->toArray());
    }

    #[Route('/items', name: 'widgets_items_create#widgets:items:create', methods: ['POST'])]
    public function createItem(Request $request): JsonResponse
    {
        try
        {
            $item = $this->itemService->create($request->attributes->get('user'), $request->attributes->get('data'));

            return $this->responseService->json(true, 'create', $item->toArray());
        }
        catch(WidgetsException $e)
        {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        }
        catch(\Exception $e)
        {
            return $this->responseService->json(false, $e);
        }
    }

    #[Route('/items/{id}', name: 'widgets_items_update#widgets:items:update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function updateItemById(int $id, Request $request): JsonResponse
    {
        if(!$item = $this->itemService->getOne($request->attributes->get('user'), $id))
        {
            return $this->responseService->json(false, 'not-found');
        }

        try
        {
            $this->itemService->update($item, $request->attributes->get('data'));

            return $this->responseService->json(true, 'update', $item->toArray());
        }
        catch(WidgetsException $e)
        {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        }
        catch(\Exception $e)
        {
            return $this->responseService->json(false, $e);
        }
    }

    #[Route('/items/{id}', name: 'widgets_items_delete#widgets:items:delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function deleteItemById(int $id, Request $request): JsonResponse
    {
        if(!$item = $this->itemService->getOne($request->attributes->get('user'), $id))
        {
            return $this->responseService->json(false, 'not-found');
        }

        try
        {
            $array = $item->toArray();

            $this->itemService->delete($item, true);

            return $this->responseService->json(true, 'delete', $array);
        }
        catch(WidgetsException $e)
        {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        }
        catch (\Exception $e)
        {
            return $this->responseService->json(false, $e);
        }
    }
}
