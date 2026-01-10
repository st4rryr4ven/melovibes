<?php

namespace App\Api\Action;

use App\Service\Api\MusicActionsService;
use App\Service\Spotify\SpotifyApiClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API Platform custom operation controller for /api/music/new-releases.
 */
final class MusicNewReleasesAction extends AbstractController
{
    public function __construct(private readonly MusicActionsService $service)
    {
    }

    public function __invoke(Request $request, SpotifyApiClient $spotify): JsonResponse
    {
        return $this->service->newReleases($request, $spotify);
    }
}
