<?php

namespace App\Api\Action;

use App\Repository\ArtistRepository;
use App\Repository\MusicRepository;
use App\Service\Api\ArtistActionsService;
use App\Service\Spotify\SpotifyApiClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API Platform custom operation controller for /api/artist/{artistId}/top-tracks.
 */
final  class ArtistTopTracksAction extends AbstractController
{
    /**
     * @param ArtistActionsService $service
     */
    public function __construct(private readonly ArtistActionsService $service)
    {
    }

    /**
     * @param int $artistId
     * @param Request $request
     * @param ArtistRepository $artistRepository
     * @param MusicRepository $musicRepository
     * @param SpotifyApiClient $spotify
     * @return JsonResponse
     */
    public function __invoke(int $artistId, Request $request, ArtistRepository $artistRepository, MusicRepository $musicRepository, SpotifyApiClient $spotify): JsonResponse
    {
        return $this->service->topTracks($artistId, $request, $artistRepository, $musicRepository, $spotify);
    }
}
