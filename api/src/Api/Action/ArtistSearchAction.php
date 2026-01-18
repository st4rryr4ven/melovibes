<?php

namespace App\Api\Action;

use App\Repository\ArtistRepository;
use App\Service\Api\ArtistActionsService;
use App\Service\Spotify\SpotifyApiClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API Platform custom operation controller for searching artists.
 *
 * This action is wired to a custom API Platform operation (typically /api/artist/search). It delegates the search
 * implementation to {@see ArtistActionsService::search()}, which merges local database results with Spotify catalog
 * results and returns a unified payload.
 */
final class ArtistSearchAction extends AbstractController
{
    /**
     * @param ArtistActionsService $service Application service that implements the operation.
     * @param SpotifyApiClient $spotify Spotify API client used for remote catalog searches.
     * @param ArtistRepository $artistRepository Repository used for local searches.
     */
    public function __construct(
        private readonly ArtistActionsService $service,
        private readonly SpotifyApiClient $spotify,
        private readonly ArtistRepository $artistRepository
    ) {
    }

    /**
     * Invokes the operation.
     *
     * @param Request $request Current HTTP request (query parameters: q, limit, offset, market...).
     *
     * @return JsonResponse Operation response.
     */
    public function __invoke(Request $request): JsonResponse
    {
        return $this->service->search($request, $this->spotify, $this->artistRepository);
    }
}
