<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Log\Logger;
use Symfony\Component\Routing\Annotation\Route;


class UserController extends AbstractController
{

    #[Route('/api/users/{id}', methods: ['DELETE'])]
    public function delete(
        int $id,
        UserRepository $userRepository,
        EntityManagerInterface $em,
        MailerService $mailerService,
        Logger $logger
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $userRepository->find($id);

        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur introuvable'], 404);
        }

        // Sauvegarder AVANT suppression
        $email = $user->getEmail();
        $login = $user->getLogin();

        $em->remove($user);
        $em->flush();

        try {
            $mailerService->sendAccountDeletedEmail($email, $login);
        } catch (\Throwable $e) {
            $logger->error('Email suppression user impossible', [
                'exception' => $e,
                'userId' => $id
            ]);
        }


        return new Response(null, Response::HTTP_NO_CONTENT);
    }

}
