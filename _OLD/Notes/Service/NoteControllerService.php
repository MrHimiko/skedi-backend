<?php

namespace App\Plugins\Notes\Service;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use App\Service\ResponseService;

use App\Plugins\Notes\Service\NoteService;
use App\Plugins\Notes\Exception\NotesException;

class NoteControllerService
{
    private ResponseService $responseService;
    private NoteService $noteService;

    public function __construct(
        ResponseService $responseService,
        NoteService $noteService
    ) {
        $this->responseService = $responseService;
        $this->noteService     = $noteService;
    }

    public function getMany(Request $request, array $criteria = []): JsonResponse
    {
        try 
        {
            $notes = $this->noteService->getMany(
                $request->attributes->get('organization'), 
                $request->attributes->get('filters'),
                $request->attributes->get('page'),
                $request->attributes->get('limit'),
                $criteria
            );

            foreach ($notes as &$note) 
            {
                $note = $note->toArray();
            }

            return $this->responseService->json(true, 'retrieve', $notes);
        } 
        catch(NotesException $e) 
        {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        }
        catch(\Exception $e) 
        {
            return $this->responseService->json(false, $e);
        }
    }

    public function create(Request $request, ?callable $callback = null): JsonResponse
    {
        try 
        {
            $note = $this->noteService->create(
                $request->attributes->get('user'),
                $request->attributes->get('data'),
                $callback
            );

            return $this->responseService->json(true, 'create', $note->toArray());
        } 
        catch (ActivityException $e) 
        {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        } 
        catch (\Exception $e) 
        {
            return $this->responseService->json(false, $e);
        }
    }
}
