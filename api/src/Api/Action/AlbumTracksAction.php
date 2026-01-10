<?php

namespace App\Api\Action;

use App\Repository\MusicRepository;
use App\Service\Api\MusicActionsService;
use App\Service\Spotify\SpotifyApiClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API Platform custom operation controller for /api/albums/spotify/{spotifyAlbumId}/tracks.
 */
final class AlbumTracksAction extends AbstractController
{
    public function __construct(
        private readonly MusicActionsService $service,
        private readonly SpotifyApiClient $spotify,
        private readonly MusicRepository $musicRepository
    ){}

    public function __invoke(string $spotifyAlbumId, Request $request): JsonResponse
    {
        return $this->service->albumTracks($spotifyAlbumId, $request, $this->spotify, $this->musicRepository);
    }
}
