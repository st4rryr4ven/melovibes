<?php

namespace App\Api\Action;

use App\Repository\MusicRepository;
use App\Service\Api\MusicActionsService;
use App\Service\Spotify\SpotifyApiClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API Platform custom operation controller for listing tracks of a Spotify album.
 *
 * This action is registered as a custom API Platform operation and delegates the actual work to
 * {@see MusicActionsService::albumTracks()}. The service is responsible for calling Spotify, enriching the response
 * with local import information, and shaping the JSON payload.
 *
 * Route parameter:
 * - spotifyAlbumId: Spotify album identifier.
 */
final class AlbumTracksAction extends AbstractController
{
    /**
     * @param MusicActionsService $service Application service that implements the operation.
     * @param SpotifyApiClient $spotify Spotify API client used to fetch album tracks.
     * @param MusicRepository $musicRepository Repository used to detect already imported tracks.
     */
    public function __construct(
        private readonly MusicActionsService $service,
        private readonly SpotifyApiClient $spotify,
        private readonly MusicRepository $musicRepository
    ) {
    }

    /**
     * Invokes the operation.
     *
     * @param string $spotifyAlbumId Spotify album identifier.
     * @param Request $request Current HTTP request (query parameters are forwarded).
     *
     * @return JsonResponse Operation response.
     */
    public function __invoke(string $spotifyAlbumId, Request $request): JsonResponse
    {
        return $this->service->albumTracks($spotifyAlbumId, $request, $this->spotify, $this->musicRepository);
    }
}
