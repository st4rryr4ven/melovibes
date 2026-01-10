<?php

namespace App\Api\Action;

use App\Repository\ArtistRepository;
use App\Repository\MusicRepository;
use App\Service\Api\ArtistActionsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API Platform custom operation controller for /api/artist/{artistId}/musics.
 */
final  class ArtistMusicAction extends AbstractController
{
    /**
     * @param ArtistActionsService $service
     * @param ArtistRepository $artistRepository
     * @param MusicRepository $musicRepository
     */
    public function __construct(private readonly ArtistActionsService $service,
                                private readonly ArtistRepository $artistRepository,
                                private readonly MusicRepository $musicRepository)
    {
    }

    /**
     * @param int $artistId
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(int $artistId, Request $request): JsonResponse
    {
        return $this->service->musics($artistId, $request, $this->artistRepository, $this->musicRepository);
        return $this->service->music($artistId, $request, $artistRepository, $musicRepository);
    }
}
