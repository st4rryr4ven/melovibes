<?php

namespace App\Api\Action;

use App\Service\Api\MusicActionsService;
use App\Service\Spotify\SpotifyCatalogService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API Platform custom operation controller for importing a Spotify track as a local music.
 *
 * This action is wired to a custom API Platform operation (typically /api/music/import/spotify/{spotifyTrackId}).
 * It delegates the import logic to {@see MusicActionsService::importSpotifyTrack()}.
 *
 * Route parameter:
 * - spotifyTrackId: Spotify track identifier.
 *
 * Query parameters are forwarded to the underlying service (e.g. market).
 */
final class MusicImportSpotifyTrackAction extends AbstractController
{
    /**
     * @param MusicActionsService $service Application service that implements the operation.
     * @param SpotifyCatalogService $catalog Service responsible for importing Spotify catalog data into local entities.
     */
    public function __construct(
        private readonly MusicActionsService $service,
        private readonly SpotifyCatalogService $catalog
    ) {
    }

    /**
     * Invokes the operation.
     *
     * @param string $spotifyTrackId Spotify track identifier.
     * @param Request $request Current HTTP request.
     *
     * @return JsonResponse Operation response.
     */
    public function __invoke(string $spotifyTrackId, Request $request): JsonResponse
    {
        return $this->service->importSpotifyTrack($spotifyTrackId, $request, $this->catalog);
    }
}
