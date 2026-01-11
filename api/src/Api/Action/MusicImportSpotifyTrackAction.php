<?php

namespace App\Api\Action;

use App\Service\Api\MusicActionsService;
use App\Service\Spotify\SpotifyCatalogService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API Platform custom operation controller for /api/music/import/spotify/{spotifyTrackId}.
 */
final class MusicImportSpotifyTrackAction extends AbstractController
{
    public function __construct(
        private readonly MusicActionsService $service,
        private readonly SpotifyCatalogService $catalog
    ) {
    }

    public function __invoke(string $spotifyTrackId, Request $request): JsonResponse
    {
        return $this->service->importSpotifyTrack($spotifyTrackId, $request, $this->catalog);
    }
}

