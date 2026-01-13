<?php

namespace App\Controller;

use App\Entity\Review;
use App\Entity\User;
use App\Repository\MusicRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

#[Route('/api/reviews')]
class ReviewCreateController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private MusicRepository $musicRepo
    ) {}

    #[Route('', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Vous devez être connecté'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $musicIri = $data['music'] ?? null;
        $rating = $data['rating'] ?? null;
        $comment = $data['comment'] ?? null;

        if (!$musicIri) {
            return $this->json(['error' => 'La musique est obligatoire'], 400);
        }

        preg_match('/\/api\/music\/(\d+)/', $musicIri, $matches);
        $musicId = $matches[1] ?? null;

        $music = $this->musicRepo->find($musicId);
        if (!$music) {
            return $this->json(['error' => 'Musique introuvable'], 404);
        }

        if ($rating === null || $rating < 0 || $rating > 5) {
            return $this->json(['error' => 'Note invalide'], 400);
        }

        $review = new Review();
        $review->setAuthor($user);
        $review->setMusic($music);
        $review->setRating($rating);
        $review->setComment($comment);
        $review->setCreatedAt(new \DateTimeImmutable());

        $existing = $this->em->getRepository(Review::class)
            ->findOneBy(['author' => $user, 'music' => $review->getMusic()]);

        if ($existing) {
            return $this->json([
                'error' => 'Vous avez deja poste un avis pour cette musique.'
            ], 409);
        }

        $this->em->persist($review);
        $this->em->flush();

        return $this->json([
            'id' => $review->getId(),
            'music' => $music->getId(),
            'author' => $user->getId(),
            'rating' => $rating,
            'comment' => $comment,
            'createdAt' => $review->getCreatedAt()->format(DATE_ATOM),
        ], 201);
    }
}



