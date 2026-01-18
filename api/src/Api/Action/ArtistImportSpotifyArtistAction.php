<?php

namespace App\Api\Action;

use App\Repository\ArtistRepository;
use App\Service\Api\ArtistActionsService;
use App\Service\Spotify\SpotifyCatalogService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API Platform custom operation controller for importing a Spotify artist.
 *
 * This action is wired to a custom API Platform operation (typically /api/artist/import/spotify/{spotifyArtistId}).
 * It delegates all business rules and payload shaping to {@see ArtistActionsService::importSpotifyArtist()}.
 *
 * Route parameter:
 * - spotifyArtistId: Spotify artist identifier.
 *
 * Query parameters are forwarded to the underlying service (e.g. market).
 */
final class ArtistImportSpotifyArtistAction extends AbstractController
{
    /**
     * @param ArtistActionsService $service Application service that implements the import logic.
     * @param SpotifyCatalogService $catalog Service responsible for importing Spotify catalog data into local entities.
     * @param ArtistRepository $artistRepository Repository used to detect existing imported artists.
     */
    public function __construct(
        private readonly ArtistActionsService $service,
        private readonly SpotifyCatalogService $catalog,
        private readonly ArtistRepository $artistRepository
    ) {
    }

    /**
     * Invokes the operation.
     *
     * @param string $spotifyArtistId Spotify artist identifier.
     * @param Request $request Current HTTP request.
     *
     * @return JsonResponse Operation response.
     */
    public function __invoke(string $spotifyArtistId, Request $request): JsonResponse
    {
        return $this->service->importSpotifyArtist($spotifyArtistId, $request, $this->catalog, $this->artistRepository);
    }
}
