<?php

namespace App\Plugins\Account\EventListener;

use App\Plugins\Account\Repository\OrganizationRepository;
use App\Plugins\Account\Service\AuthenticatorService;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use App\Service\ResponseService;

class RequestEventListener
{
    private OrganizationRepository $organizationRepository;
    private ResponseService $responseService;

    public function __construct(
        OrganizationRepository $organizationRepository, 
        ResponseService $responseService,
        AuthenticatorService $authenticatorService
    )
    {
        $this->organizationRepository = $organizationRepository;
        $this->responseService = $responseService;
        $this->authenticatorService = $authenticatorService;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $domain = $request->getHost();

        if(!$organization = $this->organizationRepository->findOneByDomain($domain))
        {
            $event->setResponse($this->responseService->json(false, 'Organization not found for domain: ' . $domain, null, 404));
            return;
        }

        $route = explode('#', $request->attributes->get('_route'));

        if(count($route) === 2)
        {
            if(!$user = $this->authenticatorService->getUser($request->headers->get('Authorization'), empty($route[1]) ? null : $route[1])) 
            {
                $event->setResponse($this->responseService->json(false, 'deny', ['permission' => $route[1]]));
                return;
            }

            if($organization->getId() !== $user->getOrganization()->getId())
            {
                $event->setResponse($this->responseService->json(false, 'deny', ['permission' => $route[1]]));
                return;
            }

            $request->attributes->set('user', $user);
        }

        $request->attributes->set('organization', $organization);
    }
}