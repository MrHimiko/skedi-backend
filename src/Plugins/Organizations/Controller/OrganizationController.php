<?php

namespace App\Plugins\Organizations\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Service\ResponseService;
use App\Plugins\Organizations\Service\OrganizationService;
use App\Plugins\Organizations\Service\UserOrganizationService;
use App\Plugins\Organizations\Exception\OrganizationsException;

#[Route('/api')]
class OrganizationController extends AbstractController
{
    private ResponseService $responseService;
    private OrganizationService $organizationService;
    private UserOrganizationService $userOrganizationService;

    public function __construct(
        ResponseService $responseService,
        OrganizationService $organizationService,
        UserOrganizationService $userOrganizationService,
    ) {
        $this->responseService = $responseService;
        $this->organizationService = $organizationService;
        $this->userOrganizationService = $userOrganizationService;
    }

    #[Route('/organizations', name: 'organizations_get_many#', methods: ['GET'])]
    public function getOrganizations(Request $request): JsonResponse
    {
        $user = $request->attributes->get('user');

        try 
        {
            $organizations = $this->userOrganizationService->getOrganizationsByUser($user);

            foreach($organizations as &$organization)
            {
                $organization = $organization->entity->toArray();
            }
       
            return $this->responseService->json(true, 'Organizations retrieved successfully.', $organizations);
        } 
        catch (OrganizationsException $e) 
        {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        } 
        catch (\Exception $e) 
        {
            return $this->responseService->json(false, $e, null, 500);
        }
    }

    #[Route('/organizations/{id}', name: 'organizations_get_one#', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getOrganizationById(int $id, Request $request): JsonResponse
    {
        $user = $request->attributes->get('user');
        
        try 
        {
            if(!$organization = $this->userOrganizationService->getOrganizationByUser($id, $user))
            {
                return $this->responseService->json(false, 'Organization was not found.');
            }

            return $this->responseService->json(true, 'Organization retrieved successfully.', $organization->entity->toArray());
        } 
        catch (OrganizationsException $e) {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        } catch (\Exception $e) {
            return $this->responseService->json(false, $e, null, 500);
        }
    }

    #[Route('/organizations', name: 'organizations_create#', methods: ['POST'])]
    public function createOrganization(Request $request): JsonResponse
    {
        $user = $request->attributes->get('user');
        $data = $request->attributes->get('data');

        try 
        {
            $organization = $this->organizationService->create($data, function($organization) 
            {
                // $organization->setUser($user);
            });

            $userOrganization = $this->userOrganizationService->create([], function($userOrganization) use($user, $organization)
            {
                $userOrganization->setUser($user);
                $userOrganization->setOrganization($organization);
                $userOrganization->setRole('admin');
            });

            return $this->responseService->json(true, 'Organization created successfully.', $organization->toArray(), 201);
        } 
        catch (OrganizationsException $e)
        {
            global $organization;

            if($organization?->getId())
            {
                $this->organizationService->delete($organization, true);
            }

            return $this->responseService->json(false, $e->getMessage(), null, 400);
        } 
        catch (\Exception $e) 
        {
            return $this->responseService->json(false, $e, null, 500);
        }
    }

    #[Route('/organizations/{id}', name: 'organizations_update#', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function updateOrganization(int $id, Request $request): JsonResponse
    {
        $user = $request->attributes->get('user');
        $data = $request->attributes->get('data');

        try 
        {
            if(!$organization = $this->userOrganizationService->getOrganizationByUser($id, $user))
            {
                return $this->responseService->json(false, 'Organization was not found.');
            }

            if($organization->role !== 'admin')
            {
                return $this->responseService->json(false, 'Organizatdasdasdaasdion was not found.');
            }

            $this->organizationService->update($organization->entity, $data);

            return $this->responseService->json(true, 'Organization updated successfully.', $organization->entity->toArray());
        } catch (OrganizationsException $e) {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        } catch (\Exception $e) {
            return $this->responseService->json(false, $e, null, 500);
        }
    }

    #[Route('/organizations/{id}', name: 'organizations_delete#', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function deleteOrganization(int $id, Request $request): JsonResponse
    {
        $user = $request->attributes->get('user');

        try 
        {
            if(!$organization = $this->userOrganizationService->getOrganizationByUser($id, $user))
            {
                return $this->responseService->json(false, 'Organization was not found.');
            }

            if($organization->role !== 'admin')
            {
                return $this->responseService->json(false, 'Organization was not found.');
            }

            $this->organizationService->delete($organization->entity);

            return $this->responseService->json(true, 'Organization soft-deleted successfully.', $organization->entity->toArray());
        } catch (OrganizationsException $e) {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        } catch (\Exception $e) {
            return $this->responseService->json(false, 'Unexpected error occurred.', null, 500);
        }
    }
}
