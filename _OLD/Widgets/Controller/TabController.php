<?php

namespace App\Plugins\Widgets\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

use App\Service\ResponseService;
use App\Exception\CrudException;
use App\Plugins\Widgets\Exception\WidgetsException;
use App\Plugins\Widgets\Service\TabService;

#[Route('/api/widgets')]
class TabController extends AbstractController
{
    private ResponseService $responseService;
    private TabService $tabService;

    public function __construct(
        ResponseService $responseService,
        TabService $tabService
    )
    {
        $this->responseService = $responseService;
        $this->tabService = $tabService;
    }

    #[Route('/tabs', name: 'widgets_tabs_get_many#widgets:tabs:read:many', methods: ['GET'])]
    public function getTabs(Request $request): JsonResponse
    {
        try
        {
            $tabs = $this->tabService->getMany(
                $request->attributes->get('user'),
                $request->attributes->get('filters'),
                $request->attributes->get('page'),
                $request->attributes->get('limit')
            );

            foreach ($tabs as &$tab)
            {
                $tab = $tab->toArray();
            }

            return $this->responseService->json(true, 'retrieve', $tabs);
        }
        catch (WidgetsException $e)
        {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        }
        catch (\Exception $e)
        {
            return $this->responseService->json(false, $e);
        }
    }

    #[Route('/tabs/{id}', name: 'widgets_tabs_get_one#widgets:tabs:read:one', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getTabById(int $id, Request $request): JsonResponse
    {
        if(!$tab = $this->tabService->getOne($request->attributes->get('user'), $id))
        {
            return $this->responseService->json(false, 'not-found');
        }

        return $this->responseService->json(true, 'retrieve', $tab->toArray());
    }

    #[Route('/tabs', name: 'widgets_tabs_create#widgets:tabs:create', methods: ['POST'])]
    public function createTab(Request $request): JsonResponse
    {
        try
        {
            $tab = $this->tabService->create($request->attributes->get('user'), $request->attributes->get('data'));

            return $this->responseService->json(true, 'create', $tab->toArray());
        }
        catch (WidgetsException $e)
        {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        }
        catch (\Exception $e)
        {
            return $this->responseService->json(false, $e);
        }
    }

    #[Route('/tabs/{id}', name: 'widgets_tabs_update#widgets:tabs:update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function updateTabById(int $id, Request $request): JsonResponse
    {
        if(!$tab = $this->tabService->getOne($request->attributes->get('user'), $id))
        {
            return $this->responseService->json(false, 'not-found');
        }

        try
        {
            $this->tabService->update($tab, $request->attributes->get('data'));

            return $this->responseService->json(true, 'update', $tab->toArray());
        }
        catch (WidgetsException $e)
        {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        }
        catch (\Exception $e)
        {
            return $this->responseService->json(false, $e);
        }
    }

    #[Route('/tabs/{id}', name: 'widgets_tabs_delete#widgets:tabs:delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function deleteTabById(int $id, Request $request): JsonResponse
    {
        if(!$tab = $this->tabService->getOne($request->attributes->get('user'), $id))
        {
            return $this->responseService->json(false, 'not-found');
        }

        try
        {
            $array = $tab->toArray();

            $this->tabService->delete($tab, true);

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
