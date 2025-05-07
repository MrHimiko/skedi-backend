<?php

namespace App\Plugins\Integrations\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ResponseService;
use App\Plugins\Integrations\Service\IntegrationService;
use App\Plugins\Integrations\Service\GoogleCalendarService;
use App\Plugins\Integrations\Exception\IntegrationException;
use DateTime;

#[Route('/api')]
class IntegrationsController extends AbstractController
{
    private ResponseService $responseService;
    private IntegrationService $integrationService;
    private GoogleCalendarService $googleCalendarService;
    
    public function __construct(
        ResponseService $responseService,
        IntegrationService $integrationService,
        GoogleCalendarService $googleCalendarService
    ) {
        $this->responseService = $responseService;
        $this->integrationService = $integrationService;
        $this->googleCalendarService = $googleCalendarService;
    }
    
    /**
     * Get available integration providers
     */
    #[Route('/user/integrations/providers', name: 'integrations_providers#', methods: ['GET'])]
    public function getProviders(Request $request): JsonResponse
    {
        $user = $request->attributes->get('user');
        
        try {
            $providers = $this->integrationService->getAvailableProviders();
            
            return $this->responseService->json(true, 'retrieve', $providers);
        } catch (\Exception $e) {
            return $this->responseService->json(false, $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Get user integrations
     */
    #[Route('/user/integrations', name: 'user_integrations_get#', methods: ['GET'])]
    public function getUserIntegrations(Request $request): JsonResponse
    {
        $user = $request->attributes->get('user');
        $provider = $request->query->get('provider');
        
        try {
            $integrations = $this->integrationService->getUserIntegrations($user, $provider);
            
            $result = array_map(function($integration) {
                return $integration->toArray();
            }, $integrations);
            
            return $this->responseService->json(true, 'retrieve', $result);
        } catch (\Exception $e) {
            return $this->responseService->json(false, $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Get Google OAuth URL
     */
    #[Route('/user/integrations/google/auth', name: 'google_auth_url#', methods: ['GET'])]
    public function getGoogleAuthUrl(Request $request): JsonResponse
    {
        try {
            $authUrl = $this->googleCalendarService->getAuthUrl();
            
            return $this->responseService->json(true, 'retrieve', [
                'auth_url' => $authUrl
            ]);
        } catch (\Exception $e) {
            return $this->responseService->json(false, $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Handle Google OAuth callback
     */
    #[Route('/user/integrations/google/callback', name: 'google_auth_callback#', methods: ['POST'])]
    public function handleGoogleCallback(Request $request): JsonResponse
    {
        $user = $request->attributes->get('user');
        $data = $request->attributes->get('data');
        
        if (!isset($data['code'])) {
            return $this->responseService->json(false, 'Code parameter is required', null, 400);
        }
        
        try {
            $integration = $this->googleCalendarService->handleAuthCallback($user, $data['code']);
            
            return $this->responseService->json(true, 'Google Calendar connected successfully', $integration->toArray());
        } catch (IntegrationException $e) {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        } catch (\Exception $e) {
            return $this->responseService->json(false, 'An error occurred: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Sync Google Calendar events
     */
    #[Route('/user/integrations/{id}/sync', name: 'integration_sync#', methods: ['POST'])]
    public function syncIntegration(int $id, Request $request): JsonResponse
    {
        $user = $request->attributes->get('user');
        $data = $request->attributes->get('data');
        
        // Default to syncing the next 30 days
        $startDate = new DateTime($data['start_date'] ?? 'now');
        $endDate = new DateTime($data['end_date'] ?? '+30 days');
        
        try {
            $integration = $this->integrationService->getIntegration($id, $user);
            
            if (!$integration) {
                return $this->responseService->json(false, 'Integration not found', null, 404);
            }
            
            if ($integration->getProvider() === 'google_calendar') {
                $events = $this->googleCalendarService->syncEvents($integration, $startDate, $endDate);
                
                return $this->responseService->json(true, 'Events synced successfully', [
                    'integration' => $integration->toArray(),
                    'events_count' => count($events),
                    'sync_range' => [
                        'start' => $startDate->format('Y-m-d H:i:s'),
                        'end' => $endDate->format('Y-m-d H:i:s')
                    ]
                ]);
            } else {
                return $this->responseService->json(false, 'Unsupported integration provider', null, 400);
            }
        } catch (IntegrationException $e) {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        } catch (\Exception $e) {
            return $this->responseService->json(false, 'An error occurred: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Delete integration
     */
    #[Route('/user/integrations/{id}', name: 'integration_delete#', methods: ['DELETE'])]
    public function deleteIntegration(int $id, Request $request): JsonResponse
    {
        $user = $request->attributes->get('user');
        
        try {
            $integration = $this->integrationService->getIntegration($id, $user);
            
            if (!$integration) {
                return $this->responseService->json(false, 'Integration not found', null, 404);
            }
            
            $this->integrationService->deleteIntegration($integration);
            
            return $this->responseService->json(true, 'Integration deleted successfully');
        } catch (\Exception $e) {
            return $this->responseService->json(false, $e->getMessage(), null, 500);
        }
    }
}