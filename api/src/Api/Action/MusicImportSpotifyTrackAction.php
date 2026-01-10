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
final  class MusicImportSpotifyTrackAction extends AbstractController
{
    /**
     * @param MusicActionsService $service
     * @param SpotifyCatalogService $catalog
     */
    public function __construct(private readonly MusicActionsService $service, SpotifyCatalogService $catalog)
    {
    }

    /**
     * @param string $spotifyTrackId
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(string $spotifyTrackId, Request $request): JsonResponse
    {
        return $this->service->importSpotifyTrack($spotifyTrackId, $request, $this->catalog);
    }
}
