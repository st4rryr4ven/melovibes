<?php

namespace App\Service;

use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Thin wrapper around Symfony Mailer and Twig used to render and send transactional emails.
 */
readonly class MailerService
{
    /**
     * @param MailerInterface $mailer Symfony mailer implementation.
     * @param Environment $twig Twig environment used to render email templates.
     */
    public function __construct(
        private MailerInterface $mailer,
        private Environment     $twig
    )
    {
    }

    /**
     * Sends a notification email after the user's account has been deleted.
     *
     * @param string $toEmail Recipient email address.
     * @param string $login Login of the deleted account.
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws TransportExceptionInterface
     */
    public function sendAccountDeletedEmail(string $toEmail, string $login): void
    {
        $html = $this->twig->render('emails/accounts_deleted.html.twig', [
            'login' => $login,
        ]);

        $email = (new Email())
            ->from('dainiute.daniele@gmail.com')
            ->to($toEmail)
            ->subject('Suppression de votre compte')
            ->html($html);

        $this->mailer->send($email);
    }

    /**
     * Sends a password reset email containing a signed reset token.
     *
     * The token is expected to be embedded in a frontend URL. The frontend is responsible for collecting
     * the new password and calling the API to finish the reset.
     *
     * @param string $toEmail Recipient email address.
     * @param string $login User login (used for personalization).
     * @param string $token Signed reset token.
     * @throws TransportExceptionInterface
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function sendPasswordResetEmail(string $toEmail, string $login, string $token): void
    {
        $baseUrl = $_ENV['FRONTEND_URL'] ?? 'http://localhost:5173';
        $resetUrl = $baseUrl . "/reset-password?token=" . urlencode($token);
        $html = $this->twig->render('emails/password_reset.html.twig', [
            'login' => $login,
            'resetUrl' => $resetUrl,
        ]);

        $email = (new Email())
            ->from('dainiute.daniele@gmail.com')
            ->to($toEmail)
            ->subject('Réinitialisation de votre mot de passe')
            ->html($html);

        $this->mailer->send($email);
    }
}
