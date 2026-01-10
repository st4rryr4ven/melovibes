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
final  class ArtistMusicsAction extends AbstractController
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
     * @return JsonResponse
     */
    public function __invoke(int $artistId, Request $request, ArtistRepository $artistRepository, MusicRepository $musicRepository): JsonResponse
    {
        return $this->service->musics($artistId, $request, $artistRepository, $musicRepository);
    }
}
