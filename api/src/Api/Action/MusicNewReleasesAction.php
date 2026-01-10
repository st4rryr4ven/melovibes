<?php

namespace App\Api\Action;

use App\Repository\MusicRepository;
use App\Service\Api\MusicActionsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API Platform custom operation controller for /api/music/new-releases.
 */
final  class MusicNewReleasesAction extends AbstractController
{
    /**
     * @param MusicActionsService $service
     */
    public function __construct(private readonly MusicActionsService $service)
    {
    }

    /**
     * @param Request $request
     * @param MusicRepository $musicRepository
     * @return JsonResponse
     */
    public function __invoke(Request $request, MusicRepository $musicRepository): JsonResponse
    {
        return $this->service->newReleases($request, $musicRepository);
    }
}
