<?php

namespace App\Controller;

use App\Repository\MusicRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Favorites endpoints for the currently authenticated user.
 *
 * Routes are nested under /api/users/{id}/favorites and enforce that the authenticated user id
 * matches the route {id}.
 */
#[Route('/api/users/{id}/favorites')]
class FavoritesController extends AbstractController
{
    /**
     * @param EntityManagerInterface $em Doctrine entity manager.
     * @param MusicRepository $musicRepo Music repository used to resolve a music by id.
     */
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MusicRepository        $musicRepo
    ) {
    }

    /**
     * Lists the authenticated user's favorite musics.
     *
     * @param int $id User id from the route.
     * @param SerializerInterface $serializer Serializer used to return the collection with serialization groups.
     *
     * @return JsonResponse A JSON array of musics (music:read group), or 401 when unauthorized.
     * @throws ExceptionInterface
     */
    #[Route('', methods: ['GET'])]
    public function listFavorites(int $id, SerializerInterface $serializer): JsonResponse
    {
        $user = $this->getUser();

        if (!$user || $user->getId() !== $id) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $favorites = $user->getFavoriteMusic()->toArray();

        $json = $serializer->serialize($favorites, 'json', ['groups' => ['music:read']]);

        return JsonResponse::fromJsonString($json);
    }

    /**
     * Toggles a music in the authenticated user's favorites.
     *
     * Request body:
     * - musicId: int
     *
     * @param int $id User id from the route.
     * @param Request $request HTTP request.
     *
     * @return JsonResponse Action result payload, or an error response.
     */
    #[Route('', methods: ['POST'])]
    public function toggleFavorite(int $id, Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user || $user->getId() !== $id) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $musicId = $data['musicId'] ?? null;

        if (!$musicId) {
            return $this->json(['error' => 'musicId missing'], 400);
        }

        $music = $this->musicRepo->find($musicId);
        if (!$music) {
            return $this->json(['error' => 'Music not found'], 404);
        }

        if ($user->getFavoriteMusic()->contains($music)) {
            $user->removeFavoriteMusic($music);
            $action = 'removed';
        } else {
            $user->addFavoriteMusic($music);
            $action = 'added';
        }

        $this->em->flush();

        return $this->json([
            'action' => $action,
            'musicId' => $musicId,
        ]);
    }
}
