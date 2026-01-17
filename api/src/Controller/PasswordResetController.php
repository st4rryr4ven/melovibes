<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class PasswordResetController extends AbstractController
{
    #[Route('/forgot-password-request', name: 'api_forgot_password_request', methods: ['POST'])]
    public function request(Request $request, EntityManagerInterface $em, MailerService $mailer): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? '';

        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

        if ($user) {
            $expires = time() + 3600;
            $payload = $user->getId() . '||' . $expires;
            $signature = hash_hmac('sha256', $payload, $this->getParameter('kernel.secret'));
            $token = base64_encode($payload . '||' . $signature);

            $mailer->sendPasswordResetEmail($user->getEmail(), $user->getLogin(), $token);
        }

        return new JsonResponse(['message' => 'Consultez vos emails.']);
    }

    #[Route('/reset-password-finish', name: 'api_reset_password_finish', methods: ['POST'])]
    public function finish(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $token = base64_decode($data['token'] ?? '');
        $newPassword = $data['password'] ?? '';

        $parts = explode('||', $token);
        if (count($parts) !== 3) {
            return new JsonResponse(['error' => 'Lien invalide'], 400);
        }

        [$userId, $expires, $providedSignature] = $parts;

        if (time() > (int)$expires) {
            return new JsonResponse(['error' => 'Lien expiré'], 400);
        }

        $expectedSignature = hash_hmac('sha256', $userId . '||' . $expires, $this->getParameter('kernel.secret'));
        if (!hash_equals($expectedSignature, $providedSignature)) {
            return new JsonResponse(['error' => 'Lien corrompu'], 400);
        }

        $user = $em->getRepository(User::class)->find($userId);
        if ($user) {
            $user->setPassword($hasher->hashPassword($user, $newPassword));
            $em->flush();
            return new JsonResponse(['message' => 'Succès']);
        }

        return new JsonResponse(['error' => 'Erreur'], 400);
    }
}
