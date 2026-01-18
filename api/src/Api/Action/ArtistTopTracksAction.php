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
 * API Platform custom operation controller for retrieving an artist's Spotify top tracks.
 *
 * This action is wired to a custom API Platform operation (typically /api/artist/{artistId}/top-tracks). It delegates
 * to {@see ArtistActionsService::topTracks()} which calls Spotify, annotates tracks with local import metadata, and
 * shapes the JSON response.
 */
final class ArtistTopTracksAction extends AbstractController
{
    /**
     * @param ArtistActionsService $service Application service that implements the operation.
     * @param ArtistRepository $artistRepository Repository used to load the artist entity.
     * @param MusicRepository $musicRepository Repository used to detect already imported tracks.
     * @param SpotifyApiClient $spotify Spotify API client used to fetch top tracks.
     */
    public function __construct(
        private readonly ArtistActionsService $service,
        private readonly ArtistRepository $artistRepository,
        private readonly MusicRepository $musicRepository,
        private readonly SpotifyApiClient $spotify
    ) {
    }

    /**
     * Invokes the operation.
     *
     * @param int $artistId Local artist identifier.
     * @param Request $request Current HTTP request (query parameters such as market, limit).
     *
     * @return JsonResponse Operation response.
     */
    public function __invoke(int $artistId, Request $request): JsonResponse
    {
        return $this->service->topTracks($artistId, $request, $this->artistRepository, $this->musicRepository, $this->spotify);
    }
}
