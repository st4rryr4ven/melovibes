<?php

namespace App\Api\Action;

use App\Repository\ArtistRepository;
use App\Service\Api\ArtistActionsService;
use App\Service\Spotify\SpotifyApiClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API Platform custom operation controller for /api/artist/search.
 */
final  class ArtistSearchAction extends AbstractController
{
    /**
     * @param ArtistActionsService $service
     */
    public function __construct(private readonly ArtistActionsService $service)
    {
    }

    /**
     * @param Request $request
     * @param SpotifyApiClient $spotify
     * @param ArtistRepository $artistRepository
     * @return JsonResponse
     */
    public function __invoke(Request $request, SpotifyApiClient $spotify, ArtistRepository $artistRepository): JsonResponse
    {
        return $this->service->search($request, $spotify, $artistRepository);
    }
}
