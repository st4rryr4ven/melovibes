<?php

namespace App\State;


use ApiPlatform\Doctrine\Common\State\PersistProcessor;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Music;
use Symfony\Bundle\SecurityBundle\Security;

class MusicProcessor implements ProcessorInterface
{
    public function __construct(
        private PersistProcessor $persistProcessor,
        private Security         $security
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($data instanceof Music && $operation->getName() === 'post') {
            if (!$this->security->isGranted('ROLE_ADMIN')) {
                $data->setIsValidated(false);
            } else {
                $data->setIsValidated(true);
            }
            if ($data->getPopularity() === null) {
                $data->setPopularity(100);
            }
        }
        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}

