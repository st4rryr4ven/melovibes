<?php

namespace App\Controller;

use App\Entity\Review;
use App\Repository\MusicRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/reviews')]
class ReviewCreateController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private MusicRepository        $musicRepo
    )
    {
    }

    #[Route('', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Vous devez être connecté'], 401);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['music'], $data['rating'])) {
            return $this->json(['error' => 'Données manquantes'], 400);
        }

        // Extract music ID from IRI
        preg_match('/\/api\/music\/(\d+)/', $data['music'], $matches);
        $musicId = $matches[1] ?? null;

        if (!$musicId) {
            return $this->json(['error' => 'Musique invalide'], 400);
        }

        $music = $this->musicRepo->find($musicId);
        if (!$music) {
            return $this->json(['error' => 'Musique introuvable'], 404);
        }

        if (!is_numeric($data['rating']) || $data['rating'] < 0 || $data['rating'] > 5) {
            return $this->json(['error' => 'Note invalide'], 400);
        }

        // 🔑 FIND EXISTING REVIEW
        $review = $this->em->getRepository(Review::class)->findOneBy([
            'author' => $user,
            'music' => $music,
        ]);

        // 🆕 CREATE IF NOT EXISTS
        if (!$review) {
            $review = new Review();
            $review
                ->setAuthor($user)
                ->setMusic($music)
                ->setCreatedAt(new \DateTimeImmutable());
            $this->em->persist($review);
        }

        // ✏️ UPDATE FIELDS (CREATE OR EDIT)
        $review
            ->setRating((int)$data['rating'])
            ->setComment($data['comment'] ?? null);

        $this->em->flush();

        return $this->json([
            'id' => $review->getId(),
            'music' => $music->getId(),
            'author' => $user->getId(),
            'rating' => $review->getRating(),
            'comment' => $review->getComment(),
            'createdAt' => $review->getCreatedAt()->format(DATE_ATOM),
        ], 200);
    }
}
