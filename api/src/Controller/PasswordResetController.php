<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Password reset endpoints.
 *
 * The reset flow is split in two steps:
 * - request: generates a signed one-hour token and sends it by email;
 * - finish: verifies the token integrity and expiration and sets the new password.
 */
#[Route('/api')]
class PasswordResetController extends AbstractController
{
    #[Route('/forgot-password-request', name: 'api_forgot_password_request', methods: ['POST'])]
    /**
     * Sends a password reset email if the account exists.
     *
     * For privacy reasons, this endpoint always returns a generic success message even when the email is unknown.
     *
     * Request body:
     * - email: string
     *
     * @param Request $request Current HTTP request.
     * @param EntityManagerInterface $em Doctrine entity manager.
     * @param MailerService $mailer Mailer used to send the reset email.
     *
     * @return JsonResponse Generic response indicating that the user should check their inbox.
     * @throws TransportExceptionInterface
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
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
    /**
     * Finishes a password reset using a signed token.
     *
     * Request body:
     * - token: string (base64 encoded)
     * - password: string (new password)
     *
     * @param Request $request Current HTTP request.
     * @param EntityManagerInterface $em Doctrine entity manager.
     * @param UserPasswordHasherInterface $hasher Password hasher.
     *
     * @return JsonResponse Success response or a 400 error if the token is invalid/expired.
     */
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
