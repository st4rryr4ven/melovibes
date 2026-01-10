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
     * @param SpotifyApiClient $spotify
     * @param MusicRepository $musicRepository
     */
    public function __construct(private readonly MusicActionsService $service, private readonly SpotifyApiClient $spotify, private readonly MusicRepository $musicRepository)
    {
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        return $this->service->search($request, $this->spotify, $this->musicRepository);
    }
}
