<?php

namespace App\Api\Action;

use App\Repository\ArtistRepository;
use App\Repository\MusicRepository;
use App\Service\Api\ArtistActionsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API Platform custom operation controller for listing musics linked to a local artist.
 *
 * This action is wired to a custom API Platform operation (typically /api/artist/{artistId}/musics). It delegates
 * data access and payload shaping to {@see ArtistActionsService::music()}.
 */
final class ArtistMusicAction extends AbstractController
{
    /**
     * @param ArtistActionsService $service Application service that implements the operation.
     * @param ArtistRepository $artistRepository Repository used to load the artist entity.
     * @param MusicRepository $musicRepository Repository used to query musics for the artist.
     */
    public function __construct(
        private readonly ArtistActionsService $service,
        private readonly ArtistRepository $artistRepository,
        private readonly MusicRepository $musicRepository
    ) {
    }

    /**
     * Invokes the operation.
     *
     * @param int $artistId Local artist identifier.
     * @param Request $request Current HTTP request (pagination query parameters).
     *
     * @return JsonResponse Operation response.
     */
    public function __invoke(int $artistId, Request $request): JsonResponse
    {
        return $this->service->music($artistId, $request, $this->artistRepository, $this->musicRepository);
    }
}
