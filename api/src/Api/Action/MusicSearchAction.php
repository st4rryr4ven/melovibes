<?php

namespace App\Api\Action;

use App\Repository\MusicRepository;
use App\Service\Api\MusicActionsService;
use App\Service\Spotify\SpotifyApiClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API Platform custom operation controller for searching musics.
 *
 * This action is wired to a custom API Platform operation (typically /api/music/search). It delegates the search
 * implementation to {@see MusicActionsService::search()}, which merges local database results with Spotify catalog
 * results and returns a unified payload.
 */
final class MusicSearchAction extends AbstractController
{
    /**
     * @param MusicActionsService $service Application service that implements the operation.
     * @param SpotifyApiClient $spotify Spotify API client used for remote catalog searches.
     * @param MusicRepository $musicRepository Repository used for local searches.
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
     * @param Request $request Current HTTP request (query parameters: q, limit, offset, market...).
     *
     * @return JsonResponse Operation response.
     */
    public function __invoke(Request $request): JsonResponse
    {
        return $this->service->search($request, $this->spotify, $this->musicRepository);
    }
}
