<?php

namespace App\Plugins\Teams\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Service\ResponseService;
use App\Plugins\Teams\Service\TeamService;
use App\Plugins\Teams\Service\UserTeamService;
use App\Plugins\Teams\Exception\TeamsException;
use App\Plugins\Organizations\Service\UserOrganizationService;

#[Route('/api/organizations/{organization_id}', requirements: ['organization_id' => '\d+'])]
class TeamController extends AbstractController
{
    private ResponseService $responseService;
    private TeamService $teamService;
    private UserTeamService $userTeamService;
    private UserOrganizationService $userOrganizationService;

    public function __construct(
        ResponseService $responseService,
        TeamService $teamService,
        UserTeamService $userTeamService,
        UserOrganizationService $userOrganizationService
    ) {
        $this->responseService = $responseService;
        $this->teamService = $teamService;
        $this->userTeamService = $userTeamService;
        $this->userOrganizationService = $userOrganizationService;
    }

    #[Route('/teams', name: 'teams_get_many#', methods: ['GET'])]
    public function getTeams(int $organization_id, Request $request): JsonResponse
    {
        $user = $request->attributes->get('user');

        try 
        {
        
            $organization = $this->userOrganizationService->getOrganizationByUser($organization_id, $user);
       
            if(!$organization) {
                return $this->responseService->json(false, 'Organization was not found.');
            }
            

            // Get teams within this organization
            $teams = $this->teamService->getTeamsByOrganization($organization->entity);

            foreach($teams as &$team)
            {
                $team = $team->toArray();
            }
       
            return $this->responseService->json(true, 'Teams retrieved successfully.', $teams);
        } 
        catch (TeamsException $e) 
        {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        } 
        catch (\Exception $e) 
        {
            return $this->responseService->json(false, $e, null, 500);
        }
    }

    #[Route('/teams/{id}', name: 'teams_get_one#', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getTeamById(int $organization_id, int $id, Request $request): JsonResponse
    {
        $user = $request->attributes->get('user');
        
        try 
        {
            // Check if user has access to this organization
            if(!$organization = $this->userOrganizationService->getOrganizationByUser($organization_id, $user)) {
                return $this->responseService->json(false, 'Organization was not found.');
            }
            
            // Get team by ID ensuring it belongs to the organization
            if(!$team = $this->teamService->getTeamByIdAndOrganization($id, $organization->entity)) {
                return $this->responseService->json(false, 'Team was not found.');
            }

            return $this->responseService->json(true, 'Team retrieved successfully.', $team->toArray());
        } 
        catch (TeamsException $e) {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        } catch (\Exception $e) {
            return $this->responseService->json(false, $e, null, 500);
        }
    }

    #[Route('/teams', name: 'teams_create#', methods: ['POST'])]
    public function createTeam(int $organization_id, Request $request): JsonResponse
    {
        $user = $request->attributes->get('user');
        $data = $request->attributes->get('data');

        try 
        {
            // Check if user has admin access to this organization
            if(!$organization = $this->userOrganizationService->getOrganizationByUser($organization_id, $user)) {
                return $this->responseService->json(false, 'Organization was not found.');
            }
            
            if($organization->role !== 'admin') {
                return $this->responseService->json(false, 'You do not have permission to create teams in this organization.');
            }

            // Get the parent_id from query parameter if it exists
            if ($request->query->has('parent_team_id')) {
                $data['parent_team_id'] = $request->query->get('parent_team_id');
            }
            
            
            // Create team with organization set in callback
            $team = $this->teamService->create($data, function($team) use ($organization, $data) {
                $team->setOrganization($organization->entity);
                
                // Explicitly set parent team if provided
                if(isset($data['parent_team_id']) && $data['parent_team_id']) {
                    $parentTeam = $this->teamService->getTeamByIdAndOrganization($data['parent_team_id'], $organization->entity);
                    if($parentTeam) {
                        $team->setParentTeam($parentTeam);
                    }
                }
            });



            $userTeam = $this->userTeamService->create([], function($userTeam) use($user, $team) {
                $userTeam->setUser($user);
                $userTeam->setTeam($team);
                $userTeam->setRole('admin');
            });

            return $this->responseService->json(true, 'Team created successfully.', $team->toArray(), 201);
        } 
        catch (TeamsException $e)
        {
            global $team;

            if($team?->getId())
            {
                $this->teamService->delete($team, true);
            }

            return $this->responseService->json(false, $e->getMessage(), null, 400);
        } 
        catch (\Exception $e) 
        {
            return $this->responseService->json(false, $e, null, 500);
        }
    }

    #[Route('/teams/{id}', name: 'teams_update#', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function updateTeam(int $organization_id, int $id, Request $request): JsonResponse
    {
        $user = $request->attributes->get('user');
        $data = $request->attributes->get('data');

        try 
        {
            // Check if user has admin access to this organization
            if(!$organization = $this->userOrganizationService->getOrganizationByUser($organization_id, $user)) {
                return $this->responseService->json(false, 'Organization was not found.');
            }
            
            if($organization->role !== 'admin') {
                return $this->responseService->json(false, 'You do not have permission to update teams in this organization.');
            }
            
            // Get team by ID ensuring it belongs to the organization
            if(!$team = $this->teamService->getTeamByIdAndOrganization($id, $organization->entity)) {
                return $this->responseService->json(false, 'Team was not found.');
            }
            
            // Handle parent_team_id if it's being updated
            if(isset($data['parent_team_id']) && $data['parent_team_id']) {
                // Verify the parent team exists and belongs to this organization
                if(!$this->teamService->getTeamByIdAndOrganization($data['parent_team_id'], $organization->entity)) {
                    return $this->responseService->json(false, 'Parent team not found in this organization.');
                }
                
                // Prevent circular nesting
                if($data['parent_team_id'] == $id) {
                    return $this->responseService->json(false, 'A team cannot be its own parent.');
                }
            }

            $this->teamService->update($team, $data);

            return $this->responseService->json(true, 'Team updated successfully.', $team->toArray());
        } catch (TeamsException $e) {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        } catch (\Exception $e) {
            return $this->responseService->json(false, $e, null, 500);
        }
    }

    #[Route('/teams/{id}', name: 'teams_delete#', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function deleteTeam(int $organization_id, int $id, Request $request): JsonResponse
    {
        $user = $request->attributes->get('user');

        try 
        {
            // Check if user has admin access to this organization
            if(!$organization = $this->userOrganizationService->getOrganizationByUser($organization_id, $user)) {
                return $this->responseService->json(false, 'Organization was not found.');
            }
            
            if($organization->role !== 'admin') {
                return $this->responseService->json(false, 'You do not have permission to delete teams in this organization.');
            }
            
            // Get team by ID ensuring it belongs to the organization
            if(!$team = $this->teamService->getTeamByIdAndOrganization($id, $organization->entity)) {
                return $this->responseService->json(false, 'Team was not found.');
            }
            
            // Check if team has child teams before deleting
            if($this->teamService->hasChildTeams($team)) {
                return $this->responseService->json(false, 'Cannot delete a team that has child teams. Please delete or reassign child teams first.');
            }

            $this->teamService->delete($team);

            return $this->responseService->json(true, 'Team soft-deleted successfully.', $team->toArray());
        } catch (TeamsException $e) {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        } catch (\Exception $e) {
            return $this->responseService->json(false, 'Unexpected error occurred.', null, 500);
        }
    }
}