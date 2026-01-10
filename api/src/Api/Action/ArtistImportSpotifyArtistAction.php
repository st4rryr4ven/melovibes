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
     * @param SpotifyCatalogService $catalog
     */
    public function __construct(
        private readonly ArtistActionsService $service,
        private readonly SpotifyCatalogService $catalog
    ){}

    /**
     * @param string $spotifyArtistId
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(string $spotifyArtistId, Request $request): JsonResponse
    {
        return $this->service->importSpotifyArtist($spotifyArtistId, $request, $this->catalog);
    }
}
