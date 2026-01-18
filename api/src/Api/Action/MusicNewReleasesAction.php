<?php

namespace App\Api\Action;

use App\Service\Api\MusicActionsService;
use App\Service\Spotify\SpotifyApiClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API Platform custom operation controller for listing Spotify new releases.
 *
 * This action is wired to a custom API Platform operation (typically /api/music/new-releases). It delegates the
 * implementation to {@see MusicActionsService::newReleases()} which calls Spotify and shapes the JSON response.
 */
final class MusicNewReleasesAction extends AbstractController
{
    /**
     * @param MusicActionsService $service Application service that implements the operation.
     * @param SpotifyApiClient $spotify Spotify API client used to fetch new releases.
     */
    public function __construct(
        private readonly MusicActionsService $service,
        private readonly SpotifyApiClient $spotify
    ) {
    }

    /**
     * Invokes the operation.
     *
     * @param Request $request Current HTTP request (query parameters such as country, limit, offset).
     *
     * @return JsonResponse Operation response.
     */
    public function __invoke(Request $request): JsonResponse
    {
        return $this->service->newReleases($request, $this->spotify);
    }
}
