<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use App\Entity\Music;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;

class UserProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private UserPasswordHasherInterface $passwordHasher,
        private Security $security,
        private EntityManagerInterface $em
    ) {}

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
                throw new \RuntimeException('Mot de passe incorrect.');
            }
        }

        if ($data->getPlainPassword()) {
            $hashed = $this->passwordHasher->hashPassword($data, $data->getPlainPassword());
            $data->setPassword($hashed);
        }

        if ($data instanceof User && $context['input'] instanceof FavoriteMusicInput) {
            $musicId = $context['input']->musicId;

            if ($musicId) {
                $music = $this->em->getRepository(Music::class)->find($musicId);
                if ($music) {
                    if ($data->getFavoriteMusic()->contains($music)) {
                        $data->removeFavoriteMusic($music);
                    } else {
                        $data->addFavoriteMusic($music);
                    }
                }
            }
        }

        $data->eraseCredentials();

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
