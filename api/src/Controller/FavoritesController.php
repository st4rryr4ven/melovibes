<?php

namespace App\Controller;

use App\Repository\MusicRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/users/{id}/favorites')]
class FavoritesController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private MusicRepository        $musicRepo
    )
    {
    }

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
            'status' => 'success',
            'action' => $action,
            'musicId' => $musicId,
        ]);
    }

    #[Route('', name: 'list_favorites', methods: ['GET'])]
    public function listFavorites(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || $user->getId() !== $id) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $favorites = $user->getFavoriteMusics()->map(fn($music) => [
            'id' => $music->getId(),
            'title' => $music->getTitle(),
            'link' => $music->getLink(),
        ])->toArray();

        return $this->json($favorites);
    }
}
