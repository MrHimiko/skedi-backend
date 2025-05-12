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

// Google API imports
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
        
        // Get parameters from parameter bag
        $this->clientId = $parameterBag->get('google.client_id');
        $this->clientSecret = $parameterBag->get('google.client_secret');
        $this->redirectUri = $parameterBag->get('google.redirect_uri');
    }

    /**
     * Get Google Client instance
     */
    private function getGoogleClient(?IntegrationEntity $integration = null): GoogleClient
    {
        $client = new GoogleClient();
        
        $client->setClientId($this->clientId);
        $client->setClientSecret($this->clientSecret);
        $client->setRedirectUri($this->redirectUri);
        $client->setScopes([
            'https://www.googleapis.com/auth/calendar.readonly',
            'https://www.googleapis.com/auth/calendar.events'
        ]);
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        
        if ($integration && $integration->getAccessToken()) {
            $client->setAccessToken([
                'access_token' => $integration->getAccessToken(),
                'refresh_token' => $integration->getRefreshToken(),
                'expires_in' => $integration->getTokenExpires() ? $integration->getTokenExpires()->getTimestamp() - time() : 0
            ]);
            
            // Refresh token if needed
            if ($client->isAccessTokenExpired()) {
                $this->refreshToken($integration, $client);
            }
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
            // Clean the code
            $code = stripslashes($code);
            
            // Create client using class properties
            $client = new GoogleClient();
            $client->setClientId($this->clientId);
            $client->setClientSecret($this->clientSecret);
            $client->setRedirectUri($this->redirectUri);
            
            // Exchange code for token
            $accessToken = $client->fetchAccessTokenWithAuthCode($code);
            
            if (isset($accessToken['error'])) {
                throw new IntegrationException('Failed to get access token: ' . $accessToken['error']);
            }
            
            if (!isset($accessToken['access_token'])) {
                throw new IntegrationException('Access token not found in response');
            }
            
            // Create a stub user info for now
            $email = 'user@example.com'; // Default placeholder
            $userId = rand(1000000, 9999999); // Random placeholder
            
            // For the integration record, use what we have
            $expiresIn = isset($accessToken['expires_in']) ? $accessToken['expires_in'] : 3600;
            $expiresAt = new DateTime();
            $expiresAt->modify("+{$expiresIn} seconds"); 
            
            // Create integration with the token but minimal other info
            $integration = $this->createIntegration(
                $user,
                'google_calendar',
                $email, // Will be updated later
                (string)$userId, // Will be updated later
                $accessToken['access_token'],
                $accessToken['refresh_token'] ?? null,
                $expiresAt,
                'calendar.readonly,calendar.events',
                [
                    'email' => $email,
                    'needs_update' => true // Flag to update user info later
                ]
            );
            
            return $integration;
            
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