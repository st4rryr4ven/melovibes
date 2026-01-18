<?php

namespace App\Controller;

use App\Entity\Review;
use App\Repository\MusicRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Creates or updates a review for a given music.
 *
 * This controller implements an upsert semantic: a user can have at most one review per music. If a review exists for
 * the (author, music) pair, it is updated; otherwise a new review is created.
 */
#[Route('/api/reviews')]
class ReviewCreateController extends AbstractController
{
    /**
     * @param EntityManagerInterface $em Doctrine entity manager.
     * @param MusicRepository $musicRepo Repository used to resolve the target music.
     */
    public function __construct(
        private EntityManagerInterface $em,
        private MusicRepository        $musicRepo
    )
    {
    }

    /**
     * Creates or updates the authenticated user's review for a music.
     *
     * Request body:
     * - music: string IRI (e.g. "/api/music/12")
     * - rating: int (0..5)
     * - comment: string|null (optional)
     *
     * @param Request $request Incoming HTTP request.
     *
     * @return JsonResponse 200 with the review payload, or an error response.
     */
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

        $review = $this->em->getRepository(Review::class)->findOneBy([
            'author' => $user,
            'music' => $music,
        ]);

        if (!$review) {
            $review = new Review();
            $review
                ->setAuthor($user)
                ->setMusic($music)
                ->setCreatedAt(new \DateTimeImmutable());
            $this->em->persist($review);
        }

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
