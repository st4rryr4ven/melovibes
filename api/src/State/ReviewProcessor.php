<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Review;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use ApiPlatform\Doctrine\Common\State\PersistProcessor;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ReviewProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private PersistProcessor $persistProcessor,
        private Security $security,
        private EntityManagerInterface $em
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($data instanceof Review && $operation->getName() === 'post') {
            $userFromSecurity = $this->security->getUser();

            if (!$userFromSecurity) {
                throw new \RuntimeException('Vous devez être connecté pour créer une review.');
            }

            $user = $this->em->getRepository(\App\Entity\User::class)
                ->find($userFromSecurity->getId());

            if (!$user) {
                throw new \RuntimeException('Impossible de récupérer l’utilisateur depuis la base.');
            }

            $data->setAuthor($user);
            $data->setCreatedAt(new \DateTimeImmutable());
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
