<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;

class UserProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface          $persistProcessor,
        private UserPasswordHasherInterface $passwordHasher,
        private Security                    $security
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof User) {
            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        $authenticatedUser = $this->security->getUser();

        if ($data->getCurrentPlainPassword()) {
            if (!$authenticatedUser || !$this->passwordHasher->isPasswordValid(
                    $authenticatedUser,
                    $data->getCurrentPlainPassword()
                )) {
                $violations = new ConstraintViolationList();
                $violations->add(new ConstraintViolation(
                    'Mot de passe incorrect.',
                    null,
                    [],
                    $data,
                    'currentPlainPassword',
                    $data->getCurrentPlainPassword()
                ));
                throw new ValidationException($violations);
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
