<?php

namespace App\Plugins\Widgets\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

use App\Service\ResponseService;
use App\Exception\CrudException;
use App\Plugins\Widgets\Exception\WidgetsException;
use App\Plugins\Widgets\Service\WidgetService;

#[Route('/api/widgets')]
class WidgetController extends AbstractController
{
    private ResponseService $responseService;
    private WidgetService $widgetService;

    public function __construct(
        ResponseService $responseService,
        WidgetService $widgetService
    )
    {
        $this->responseService = $responseService;
        $this->widgetService = $widgetService;
    }

    #[Route('/', name: 'widgets_get_many', methods: ['GET'])]
    public function getWidgets(Request $request): JsonResponse
    {
        try
        {
            $widgets = $this->widgetService->getMany(
                $request->attributes->get('filters'),
                $request->attributes->get('page'),
                $request->attributes->get('limit')
            );

            foreach ($widgets as &$widget)
            {
                $widget = $widget->toArray();
            }

            return $this->responseService->json(true, 'retrieve', $widgets);
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
}
