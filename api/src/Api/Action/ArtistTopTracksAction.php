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
     * @param ArtistRepository $artistRepository
     * @param MusicRepository $musicRepository
     * @param SpotifyApiClient $spotify
     */
    public function __construct(private readonly ArtistActionsService $service,
                                private readonly ArtistRepository $artistRepository,
                                private readonly MusicRepository $musicRepository,
                                private readonly SpotifyApiClient $spotify
    )
    {
    }

    /**
     * @param int $artistId
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(int $artistId, Request $request): JsonResponse
    {
        return $this->service->topTracks($artistId, $request, $this->artistRepository, $this->musicRepository, $this->spotify);
    }
}
