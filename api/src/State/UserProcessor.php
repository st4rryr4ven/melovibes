<?php

namespace App\State;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Throwable;

/**
 * API Platform processor for {@see User} persistence.
 *
 * Responsibilities:
 * - On DELETE, removes the user entity and attempts to send an account deletion email.
 * - On password change, validates the provided current password against the authenticated user.
 * - Hashes the new password (plainPassword) and clears transient credentials.
 *
 * Persistence for non-delete operations is delegated to API Platform's Doctrine persist processor.
 */
readonly class UserProcessor implements ProcessorInterface
{
    /**
     * @param ProcessorInterface $persistProcessor API Platform Doctrine persist processor.
     * @param UserPasswordHasherInterface $passwordHasher Password hasher used for validation and hashing.
     * @param Security $security Security helper used to resolve the authenticated user.
     * @param EntityManagerInterface $em Doctrine entity manager (used for delete flow).
     * @param MailerService $mailer Transactional mailer used for account deletion notifications.
     */
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface          $persistProcessor,
        private UserPasswordHasherInterface $passwordHasher,
        private Security                    $security,
        private EntityManagerInterface      $em,
        private MailerService               $mailer
    ) {
    }

    /**
     * Applies User-specific rules and delegates to the underlying persistence processor.
     *
     * @param mixed $data The object to persist (expected to be {@see User} for this processor to apply rules).
     * @param Operation $operation The API Platform operation metadata.
     * @param array<string, mixed> $uriVariables URI variables extracted from the request.
     * @param array<string, mixed> $context Execution context.
     *
     * @return mixed The persisted object, or null for delete operations.
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof User) {
            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        $authenticatedUser = $this->security->getUser();

        if ($operation instanceof Delete) {
            try {
                $this->mailer->sendAccountDeletedEmail($data->getEmail(), $data->getLogin());
            } catch (Throwable) {
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
