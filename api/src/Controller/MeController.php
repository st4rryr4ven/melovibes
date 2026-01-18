<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * Returns information about the currently authenticated user.
 *
 * This endpoint is used by the frontend to bootstrap authentication state and display profile information.
 */
final class MeController extends AbstractController
{
    /**
     * Returns the current user as a lightweight JSON object.
     *
     * @param User|null $user The authenticated user injected by Symfony, or null when unauthenticated.
     *
     * @return JsonResponse 401 when unauthenticated, otherwise user data.
     */
    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function __invoke(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(['message' => 'Unauthenticated'], 401);
        }

        return $this->json([
            'id' => $user->getId(),
            'login' => $user->getLogin(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'favoriteMusic' => $user->getFavoriteMusic(),
            'spotifyLinked' => $user->getSpotifyId() !== null,
            'spotifyDisplayName' => $user->getSpotifyDisplayName(),
        ]);
    }
}
