<?php

namespace App\Plugins\Integrations\Service;

use Doctrine\ORM\EntityManagerInterface;
use App\Plugins\Integrations\Repository\IntegrationRepository;
use App\Plugins\Account\Service\UserAvailabilityService;
use App\Service\CrudManager;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Plugins\Integrations\Entity\IntegrationEntity;
use App\Plugins\Account\Entity\UserEntity;
use App\Plugins\Integrations\Exception\IntegrationException;
use DateTime;

// Add these Google API imports
use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleCalendar;
use Google\Service\Oauth2;

class GoogleCalendarService extends IntegrationService
{
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;
    
    public function __construct(
        EntityManagerInterface $entityManager,
        IntegrationRepository $integrationRepository,
        UserAvailabilityService $userAvailabilityService,
        CrudManager $crudManager,
        ParameterBagInterface $parameterBag
    ) {
        parent::__construct($entityManager, $integrationRepository, $userAvailabilityService, $crudManager);
        
        // Try to get the parameters and log them
        try {
            $this->clientId = $parameterBag->get('google.client_id');
            error_log("Client ID from parameters: " . $this->clientId);
        } catch (\Exception $e) {
            error_log("Error getting google.client_id: " . $e->getMessage());
            // Temporary fallback for testing
            $this->clientId = '263415563843-iisvu1oericu0v5mvc7bl2c1p3obq2mq.apps.googleusercontent.com';
            error_log("Using fallback client ID: " . $this->clientId);
        }
        
        try {
            $this->clientSecret = $parameterBag->get('google.client_secret');
        } catch (\Exception $e) {
            error_log("Error getting google.client_secret: " . $e->getMessage());
            // Temporary fallback
            $this->clientSecret = 'eGOCSPX-SapXgkbRvjsdclVCALHQiK05W9la';
        }
        
        try {
            $this->redirectUri = $parameterBag->get('google.redirect_uri');
            error_log("Redirect URI from parameters: " . $this->redirectUri);
        } catch (\Exception $e) {
            error_log("Error getting google.redirect_uri: " . $e->getMessage());
            // Temporary fallback
            $this->redirectUri = 'https://app.skedi.com/oauth/google/callback';
            error_log("Using fallback redirect URI: " . $this->redirectUri);
        }
    }

    /**
     * Get Google Client instance
     */
    private function getGoogleClient(?IntegrationEntity $integration = null): GoogleClient
    {
        $client = new GoogleClient();
        
        // Hardcode values temporarily until we fix the parameter loading
        $client->setClientId('263415563843-iisvu1oericu0v5mvc7bl2c1p3obq2mq.apps.googleusercontent.com');
        $client->setClientSecret('eGOCSPX-SapXgkbRvjsdclVCALHQiK05W9la');
        $client->setRedirectUri('https://app.skedi.com/oauth/google/callback');
        
        $client->setScopes([
            'https://www.googleapis.com/auth/calendar.readonly',
            'https://www.googleapis.com/auth/calendar.events'
        ]);
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        
        if ($integration && $integration->getAccessToken()) {
            // Rest of your existing code
        }
        
        return $client;
    }

    /**
     * Get OAuth URL
     */
    public function getAuthUrl(): string
    {
        $client = $this->getGoogleClient();
        
        return $client->createAuthUrl();
    }

    /**
     * Handle OAuth callback
     */
    public function handleAuthCallback(UserEntity $user, string $code): IntegrationEntity
    {
        try {
            $client = $this->getGoogleClient();
            $accessToken = $client->fetchAccessTokenWithAuthCode($code);
            
            if (isset($accessToken['error'])) {
                throw new IntegrationException('Failed to get access token: ' . $accessToken['error']);
            }
            
            // Get user info to use as account name
            $oauth2 = new \Google\Service\Oauth2($client);
            $userInfo = $oauth2->userinfo->get();
            $name = $userInfo->getEmail() ?: 'Google Calendar';
            
            // Create expiration date
            $expiresIn = isset($accessToken['expires_in']) ? $accessToken['expires_in'] : 3600;
            $expiresAt = new DateTime();
            $expiresAt->modify("+{$expiresIn} seconds");
            
            // Create integration record
            return $this->createIntegration(
                $user,
                'google_calendar',
                $name,
                $userInfo->getId(),
                $accessToken['access_token'],
                $accessToken['refresh_token'] ?? null,
                $expiresAt,
                implode(',', $client->getScopes()),
                [
                    'email' => $userInfo->getEmail(),
                    'picture' => $userInfo->getPicture()
                ]
            );
        } catch (\Exception $e) {
            throw new IntegrationException('Failed to authenticate with Google: ' . $e->getMessage());
        }
    }

    /**
     * Refresh token
     */
    private function refreshToken(IntegrationEntity $integration, GoogleClient $client = null): void
    {
        if (!$client) {
            $client = $this->getGoogleClient($integration);
        }
        
        if (!$integration->getRefreshToken()) {
            throw new IntegrationException('No refresh token available');
        }
        
        try {
            $accessToken = $client->fetchAccessTokenWithRefreshToken($integration->getRefreshToken());
            
            if (isset($accessToken['error'])) {
                throw new IntegrationException('Failed to refresh token: ' . $accessToken['error']);
            }
            
            // Update token in database
            $expiresIn = isset($accessToken['expires_in']) ? $accessToken['expires_in'] : 3600;
            $expiresAt = new DateTime();
            $expiresAt->modify("+{$expiresIn} seconds");
            
            $this->updateIntegration($integration, [
                'access_token' => $accessToken['access_token'],
                'token_expires' => $expiresAt,
                // Only update refresh token if a new one was provided
                'refresh_token' => $accessToken['refresh_token'] ?? $integration->getRefreshToken()
            ]);
        } catch (\Exception $e) {
            throw new IntegrationException('Failed to refresh token: ' . $e->getMessage());
        }
    }

    /**
     * Sync calendar events
     */
    public function syncEvents(IntegrationEntity $integration, DateTime $startDate, DateTime $endDate): array
    {
        try {
            $client = $this->getGoogleClient($integration);
            $service = new GoogleCalendar($client);
            
            // Get calendar list
            $calendarList = $service->calendarList->listCalendarList();
            $events = [];
            
            // Format dates for Google API query
            $timeMin = $startDate->format('c');
            $timeMax = $endDate->format('c');
            
            // Loop through each calendar
            foreach ($calendarList->getItems() as $calendarListEntry) {
                $calendarId = $calendarListEntry->getId();
                
                // Skip calendars user doesn't own or can't write to
                $accessRole = $calendarListEntry->getAccessRole();
                if (!in_array($accessRole, ['owner', 'writer'])) {
                    continue;
                }
                
                // Get events from this calendar
                $eventsResult = $service->events->listEvents($calendarId, [
                    'timeMin' => $timeMin,
                    'timeMax' => $timeMax,
                    'singleEvents' => true,
                    'orderBy' => 'startTime'
                ]);
                
                foreach ($eventsResult->getItems() as $event) {
                    // Skip events where the user is not attending
                    $attendees = $event->getAttendees();
                    if ($attendees) {
                        $userEmail = $integration->getConfig()['email'] ?? null;
                        $isAttending = false;
                        
                        foreach ($attendees as $attendee) {
                            if ($attendee->getEmail() === $userEmail && $attendee->getResponseStatus() !== 'declined') {
                                $isAttending = true;
                                break;
                            }
                        }
                        
                        if (!$isAttending) {
                            continue;
                        }
                    }
                    
                    // Format event data
                    $eventData = [
                        'id' => $event->getId(),
                        'summary' => $event->getSummary(),
                        'description' => $event->getDescription(),
                        'location' => $event->getLocation(),
                        'calendar_id' => $calendarId,
                        'calendar_name' => $calendarListEntry->getSummary(),
                        'created' => $event->getCreated(),
                        'updated' => $event->getUpdated(),
                        'status' => $event->getStatus()
                    ];
                    
                    // Handle date/time (all-day vs timed events)
                    $start = $event->getStart();
                    $end = $event->getEnd();
                    
                    if ($start->dateTime) {
                        // This is a timed event
                        $eventData['start_time'] = new DateTime($start->dateTime);
                        $eventData['end_time'] = new DateTime($end->dateTime);
                        $eventData['all_day'] = false;
                    } else {
                        // This is an all-day event
                        $eventData['start_time'] = new DateTime($start->date);
                        $eventData['end_time'] = new DateTime($end->date);
                        $eventData['all_day'] = true;
                    }
                    
                    $events[] = $eventData;
                    
                    // Create availability record in our system
                    $this->createAvailabilityFromEvent($integration->getUser(), $eventData);
                }
            }
            
            // Update last synced timestamp
            $this->updateIntegration($integration, [
                'last_synced' => new DateTime()
            ]);
            
            return $events;
        } catch (\Exception $e) {
            throw new IntegrationException('Failed to sync calendar events: ' . $e->getMessage());
        }
    }

    /**
     * Create availability record from event
     */
    private function createAvailabilityFromEvent(UserEntity $user, array $eventData): void
    {
        try {
            // Skip tentative or cancelled events
            if ($eventData['status'] !== 'confirmed') {
                return;
            }
            
            // Create a source ID that uniquely identifies this event
            $sourceId = 'google_' . $eventData['calendar_id'] . '_' . $eventData['id'];
            
            // Use the availability service to create/update availability
            $this->userAvailabilityService->createExternalAvailability(
                $user,
                $eventData['summary'] ?: 'Busy',
                $eventData['start_time'],
                $eventData['end_time'],
                'google_calendar',
                $sourceId,
                $eventData['description'],
                'confirmed'
            );
        } catch (\Exception $e) {
            // Log error but continue processing other events
            error_log('Failed to create availability from Google event: ' . $e->getMessage());
        }
    }
}