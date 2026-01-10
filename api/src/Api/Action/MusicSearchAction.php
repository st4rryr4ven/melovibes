<?php

namespace App\Api\Action;

use App\Repository\MusicRepository;
use App\Service\Api\MusicActionsService;
use App\Service\Spotify\SpotifyApiClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API Platform custom operation controller for /api/music/search.
 */
final  class MusicSearchAction extends AbstractController
{
    /**
     * @param MusicActionsService $service
     */
    public function __construct(private readonly MusicActionsService $service)
    {
    }

    /**
     * @param Request $request
     * @param SpotifyApiClient $spotify
     * @param MusicRepository $musicRepository
     * @return JsonResponse
     */
    public function __invoke(Request $request, SpotifyApiClient $spotify, MusicRepository $musicRepository): JsonResponse
    {
        return $this->service->search($request, $spotify, $musicRepository);
    }
}
