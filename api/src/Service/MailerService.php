<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Validator\Constraints\Email;
use Twig\Environment;

class MailerService
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig
    ) {}

    public function sendAccountDeletedEmail(string $toEmail, string $login): void
    {
        $html = $this->twig->render('emails/account_deleted.html.twig', [
            'login' => $login,
        ]);

        $email = (new Email())
            ->from('no-reply@melovibes.fr')
            ->to($toEmail)
            ->subject('Suppression de votre compte')
            ->html($html);

        $this->mailer->send($email);
    }
}

