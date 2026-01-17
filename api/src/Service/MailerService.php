<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class MailerService
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment     $twig
    )
    {
    }

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

