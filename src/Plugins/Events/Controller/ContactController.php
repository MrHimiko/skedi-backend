<?php

namespace App\Plugins\Events\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Service\ResponseService;
use App\Plugins\Events\Service\ContactService;
use App\Plugins\Events\Exception\EventsException;
use App\Plugins\Organizations\Service\UserOrganizationService;

#[Route('/api/organizations/{organization_id}', requirements: ['organization_id' => '\d+'])]
class ContactController extends AbstractController
{
    private ResponseService $responseService;
    private ContactService $contactService;
    private UserOrganizationService $userOrganizationService;

    public function __construct(
        ResponseService $responseService,
        ContactService $contactService,
        UserOrganizationService $userOrganizationService
    ) {
        $this->responseService = $responseService;
        $this->contactService = $contactService;
        $this->userOrganizationService = $userOrganizationService;
    }

    #[Route('/contacts', name: 'contacts_get_many#', methods: ['GET'])]
    public function getContacts(int $organization_id, Request $request): JsonResponse
    {
        $user = $request->attributes->get('user');
        $filters = $request->attributes->get('filters');
        $page = $request->attributes->get('page');
        $limit = $request->attributes->get('limit');

        try {
            // Check if user has access to this organization
            if (!$organization = $this->userOrganizationService->getOrganizationByUser($organization_id, $user)) {
                return $this->responseService->json(false, 'Organization was not found.');
            }
            
            // Get contacts
            $contacts = $this->contactService->getMany($filters, $page, $limit);
            
            $result = [];
            foreach ($contacts as $contact) {
                $result[] = $contact->toArray();
            }
            
            return $this->responseService->json(true, 'Contacts retrieved successfully.', $result);
        } catch (EventsException $e) {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        } catch (\Exception $e) {
            return $this->responseService->json(false, $e, null, 500);
        }
    }

    #[Route('/contacts/{id}', name: 'contacts_get_one#', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getContactById(int $organization_id, int $id, Request $request): JsonResponse
    {
        $user = $request->attributes->get('user');
        
        try {
            // Check if user has access to this organization
            if (!$organization = $this->userOrganizationService->getOrganizationByUser($organization_id, $user)) {
                return $this->responseService->json(false, 'Organization was not found.');
            }
            
            // Get contact by ID
            $contact = $this->contactService->getOne($id);
            
            if (!$contact) {
                return $this->responseService->json(false, 'Contact was not found.');
            }
            
            return $this->responseService->json(true, 'Contact retrieved successfully.', $contact->toArray());
        } catch (EventsException $e) {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        } catch (\Exception $e) {
            return $this->responseService->json(false, $e, null, 500);
        }
    }

    #[Route('/contacts', name: 'contacts_create#', methods: ['POST'])]
    public function createContact(int $organization_id, Request $request): JsonResponse
    {
        $user = $request->attributes->get('user');
        $data = $request->attributes->get('data');

        try {
            // Check if user has access to this organization
            if (!$organization = $this->userOrganizationService->getOrganizationByUser($organization_id, $user)) {
                return $this->responseService->json(false, 'Organization was not found.');
            }
            
            // Set last_assignee to current user if not provided
            if (empty($data['last_assignee_id'])) {
                $data['last_assignee_id'] = $user->getId();
            }
            
            // Create contact
            $contact = $this->contactService->create($data);
            
            return $this->responseService->json(true, 'Contact created successfully.', $contact->toArray(), 201);
        } catch (EventsException $e) {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        } catch (\Exception $e) {
            return $this->responseService->json(false, $e, null, 500);
        }
    }

    #[Route('/contacts/{id}', name: 'contacts_update#', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function updateContact(int $organization_id, int $id, Request $request): JsonResponse
    {
        $user = $request->attributes->get('user');
        $data = $request->attributes->get('data');

        try {
            // Check if user has access to this organization
            if (!$organization = $this->userOrganizationService->getOrganizationByUser($organization_id, $user)) {
                return $this->responseService->json(false, 'Organization was not found.');
            }
            
            // Get contact by ID
            $contact = $this->contactService->getOne($id);
            
            if (!$contact) {
                return $this->responseService->json(false, 'Contact was not found.');
            }
            
            // Update contact
            $this->contactService->update($contact, $data);
            
            return $this->responseService->json(true, 'Contact updated successfully.', $contact->toArray());
        } catch (EventsException $e) {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        } catch (\Exception $e) {
            return $this->responseService->json(false, $e, null, 500);
        }
    }

    #[Route('/contacts/{id}', name: 'contacts_delete#', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function deleteContact(int $organization_id, int $id, Request $request): JsonResponse
    {
        $user = $request->attributes->get('user');

        try {
            // Check if user has access to this organization
            if (!$organization = $this->userOrganizationService->getOrganizationByUser($organization_id, $user)) {
                return $this->responseService->json(false, 'Organization was not found.');
            }
            
            // Get contact by ID
            $contact = $this->contactService->getOne($id);
            
            if (!$contact) {
                return $this->responseService->json(false, 'Contact was not found.');
            }
            
            // Delete contact
            $this->contactService->delete($contact);
            
            return $this->responseService->json(true, 'Contact deleted successfully.');
        } catch (EventsException $e) {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        } catch (\Exception $e) {
            return $this->responseService->json(false, $e, null, 500);
        }
    }
}