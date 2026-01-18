<?php

namespace App\State;

use ApiPlatform\Doctrine\Common\State\PersistProcessor;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Music;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * API Platform processor for {@see Music} persistence.
 *
 * This processor enforces creation-time defaults for new musics:
 * - Non-admin users create musics as unvalidated.
 * - Admin users create musics as validated.
 * - When popularity is missing, a default value is assigned.
 *
 * The actual database persistence is delegated to API Platform's Doctrine persist processor.
 */
readonly class MusicProcessor implements ProcessorInterface
{
    /**
     * @param PersistProcessor $persistProcessor Doctrine persist processor used by API Platform.
     * @param Security $security Security helper used to check roles.
     */
    public function __construct(
        private PersistProcessor $persistProcessor,
        private Security         $security
    ) {
    }

    /**
     * Applies creation rules for Music resources and delegates persistence to the underlying processor.
     *
     * @param mixed $data The object to persist (expected to be {@see Music} for this processor to apply rules).
     * @param Operation $operation The API Platform operation metadata.
     * @param array<string, mixed> $uriVariables URI variables extracted from the request.
     * @param array<string, mixed> $context Execution context.
     *
     * @return mixed The persisted object (or processor-specific return value).
     */
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
