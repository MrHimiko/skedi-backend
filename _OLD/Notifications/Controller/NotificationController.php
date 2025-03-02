<?php

namespace App\Plugins\Notifications\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

use Doctrine\ORM\Query\Lexer;


use App\Service\ResponseService;
use App\Exception\CrudException;
use App\Plugins\Notifications\Exception\NotificationsException;
use App\Plugins\Notifications\Service\NotificationService;

#[Route('/api/notifications')]
class NotificationController extends AbstractController
{
    private ResponseService $responseService;
    private NotificationService $notificationService;

    public function __construct(
        ResponseService $responseService,
        NotificationService $notificationService
    )
    {
        $this->responseService = $responseService;
        $this->notificationService = $notificationService;
    }

    #[Route('/', name: 'notifications_get_many#notifications:read:many', methods: ['GET'])]
    public function getNotifications(Request $request): JsonResponse
    {
        try
        {
            $notifications = $this->notificationService->getMany(
                $request->attributes->get('user'),
                $request->attributes->get('filters'),
                $request->attributes->get('page'),
                $request->attributes->get('limit')
            );

            foreach ($notifications as &$notification)
            {
                $notification = $notification->toArray();
            }

            return $this->responseService->json(true, 'retrieve', $notifications);
        }
        catch (NotificationsException $e)
        {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        }
        catch (\Exception $e)
        {
            return $this->responseService->json(false, $e);
        }
    }

    #[Route('/{id}', name: 'notifications_get_one#notifications:read:one', methods: ['GET'], requirements: ['id' => '\\d+'])]
    public function getNotificationById(int $id, Request $request): JsonResponse
    {
        if(!$notification = $this->notificationService->getOne($request->attributes->get('user'), $id))
        {
            return $this->responseService->json(false, 'not-found');
        }

        return $this->responseService->json(true, 'retrieve', $notification->toArray());
    }
}
