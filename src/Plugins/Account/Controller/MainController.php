<?php

namespace App\Plugins\Account\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Service\ResponseService;
use App\Plugins\Account\Service\LoginService;
use App\Plugins\Account\Service\UserService;
use App\Plugins\Teams\Service\TeamService;
use App\Plugins\Account\Exception\AccountException;

#[Route('/api/account')]
class MainController extends AbstractController
{
    private ResponseService $responseService;
    private LoginService $loginService;
    private UserService $userService;
    private TeamService $teamService;

    public function __construct(
        ResponseService $responseService,
        LoginService $loginService,
        UserService $userService,
        TeamService $teamService
    ) {
        $this->responseService = $responseService;
        $this->loginService = $loginService;
        $this->userService = $userService;
        $this->teamService = $teamService;
    }

    #[Route('/login', name: 'account_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        try {
            $token = $this->loginService->login($request->attributes->get('data'));

            return $this->responseService->json(true, 'Login successful.', [
                'token' => $token->getValue(),
                'expires' => $token->getExpires()->format('Y-m-d H:i:s')
            ]);
        } catch (AccountException $e) {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        } catch (\Exception $e) {
            return $this->responseService->json(false, $e, null, 400);
        }
    }

    #[Route('/user', name: 'account_get_user#', methods: ['GET'])]
    public function getAccountUser(Request $request): JsonResponse
    {
        $user = $request->attributes->get('user');
        $organizations = $request->attributes->get('organizations');
        $directTeams = $request->attributes->get('teams');

        // Process organizations
        foreach ($organizations as $organization) {
            $organization->entity = $organization->entity->toArray();
        }

        // Process and expand teams with all accessible teams
        $allTeams = $this->getAllAccessibleTeams($directTeams, $organizations);

        return $this->responseService->json(true, 'retrieve', $user->toArray() + [
            'organizations' => $organizations,
            'teams' => $allTeams
        ]);
    }

    /**
     * Get all teams a user has access to through different means:
     * 1. Direct team membership
     * 2. Organization membership
     * 3. Parent-child team relationships
     */
    private function getAllAccessibleTeams(array $directTeams, array $organizations): array
    {
        // Prepare a map to prevent duplicates using team IDs as keys
        $teamsMap = [];
        
        // 1. First, add direct teams the user is a member of
        foreach ($directTeams as $team) {
            $teamId = $team->entity->getId();
            if (!isset($teamsMap[$teamId])) {
                // Convert entity to array if it's not already
                if (is_object($team->entity) && method_exists($team->entity, 'toArray')) {
                    $team->entity = $team->entity->toArray();
                }
                $teamsMap[$teamId] = $team;
            }
        }
        
        // 2. Add teams from organizations the user is a member of
        foreach ($organizations as $organization) {
            $orgId = $organization->entity['id'];
            
            try {
                // Get all teams in this organization
                $orgTeams = $this->teamService->getMany([], 1, 1000, [
                    'organization' => $orgId,
                    'deleted' => false
                ]);
                
                foreach ($orgTeams as $orgTeam) {
                    $teamId = $orgTeam->getId();
                    if (!isset($teamsMap[$teamId])) {
                        // Create an object with the same structure as direct teams
                        $teamsMap[$teamId] = (object) [
                            'entity' => $orgTeam->toArray(),
                            'role' => 'member', // Default role for teams accessed via organization
                            'access_via' => 'organization'
                        ];
                    }
                }
            } catch (\Exception $e) {
                // Log error but continue processing other organizations
                // (we don't want one bad organization to break the entire request)
                continue;
            }
        }
        
        // 3. Process child teams for all teams we have so far
        $processedTeamIds = array_keys($teamsMap);
        $teamIdsToProcess = $processedTeamIds;
        
        while (!empty($teamIdsToProcess)) {
            $currentTeamId = array_shift($teamIdsToProcess);
            
            try {
                // Get all child teams for this team
                $childTeams = $this->teamService->getMany([], 1, 1000, [
                    'parentTeam' => $currentTeamId,
                    'deleted' => false
                ]);
                
                foreach ($childTeams as $childTeam) {
                    $childTeamId = $childTeam->getId();
                    
                    // If we haven't processed this team yet
                    if (!isset($teamsMap[$childTeamId])) {
                        // Add to map
                        $teamsMap[$childTeamId] = (object) [
                            'entity' => $childTeam->toArray(),
                            'role' => $teamsMap[$currentTeamId]->role, // Inherit role from parent
                            'access_via' => 'parent_team'
                        ];
                        
                        // Add to processing queue to find its children
                        $teamIdsToProcess[] = $childTeamId;
                    }
                }
            } catch (\Exception $e) {
                // Log error but continue processing other teams
                continue;
            }
        }
        
        // Convert map back to indexed array
        return array_values($teamsMap);
    }
}