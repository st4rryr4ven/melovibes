<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private UserPasswordHasherInterface $passwordHasher,
        private Security $security,
        private EntityManagerInterface $em,
        private MailerService $mailer
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof User) {
            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        $authenticatedUser = $this->security->getUser();

        if ($operation instanceof \ApiPlatform\Metadata\Delete) {
            try {
                error_log('Sending deletion email to '.$data->getEmail());
                $this->mailer->sendAccountDeletedEmail($data->getEmail(), $data->getLogin());
            } catch (\Throwable $e) {
                error_log('Erreur envoi mail suppression user: '.$e->getMessage());
            }


            $this->em->remove($data);
            $this->em->flush();

            return null;
        }

        if ($data->getCurrentPlainPassword()) {
            if (
                !$authenticatedUser ||
                !$this->passwordHasher->isPasswordValid($authenticatedUser, $data->getCurrentPlainPassword())
            ) {
                throw new \RuntimeException('Mot de passe incorrect.');
            }
        }

        if ($data->getPlainPassword()) {
            $hashed = $this->passwordHasher->hashPassword($data, $data->getPlainPassword());
            $data->setPassword($hashed);
        }

        $data->eraseCredentials();

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
