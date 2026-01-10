<?php

namespace App\Api\Action;

use App\Service\Api\ArtistActionsService;
use App\Service\Spotify\SpotifyCatalogService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API Platform custom operation controller for /api/artist/import/spotify/{spotifyArtistId}.
 */
final  class ArtistImportSpotifyArtistAction extends AbstractController
{
    /**
     * @param ArtistActionsService $service
     */
    public function __construct(private readonly ArtistActionsService $service)
    {
    }

    /**
     * @param string $spotifyArtistId
     * @param Request $request
     * @param SpotifyCatalogService $catalog
     * @return JsonResponse
     */
    public function __invoke(string $spotifyArtistId, Request $request, SpotifyCatalogService $catalog): JsonResponse
    {
        return $this->service->importSpotifyArtist($spotifyArtistId, $request, $catalog);
    }
}
