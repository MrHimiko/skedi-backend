<?php

namespace App\Plugins\Countries\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

use App\Plugins\Countries\Service\CountryService;
use App\Service\ResponseService;
use App\Plugins\Countries\Exception\CountriesException;

#[Route('/api/countries')]
class CountryController extends AbstractController
{
    private ResponseService $responseService;
    private CountryService $countryService;

    public function __construct(
        ResponseService $responseService, 
        CountryService $countryService
    )
    {
        $this->responseService = $responseService;
        $this->countryService = $countryService;
    }

    #[Route('', name: 'countries_get_many', methods: ['GET'])]
    public function getCountries(Request $request): JsonResponse
    {
        try 
        {
            $countries = $this->countryService->getMany(
                $request->attributes->get('filters'),
                $request->attributes->get('page'),
                $request->attributes->get('limit')
            );

            foreach ($countries as &$country) 
            {
                $country = $country->toArray();
            }

            return $this->responseService->json(true, 'retrieve', $countries);
        } 
        catch(CountriesException $e) 
        {
            return $this->responseService->json(false, $e->getMessage(), null, 400);
        }
        catch(\Exception $e) 
        {
            return $this->responseService->json(false, $e);
        }
    }

    #[Route('/{id}', name: 'countries_get_one', methods: ['GET'], requirements: ['id' => '\\d+'])]
    public function getCountry(int $id, Request $request): JsonResponse
    {
        if(!$country = $this->countryService->getOne($id))
        {
            return $this->responseService->json(false, 'not-found');
        }

        return $this->responseService->json(true, 'retrieve', $country->toArray());
    }
}
