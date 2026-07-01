<?php

namespace Ubermuda\FeatureFlagsBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Ubermuda\FeatureFlagsBundle\Enum\FeatureFlagType;

final readonly class ToggleFeatureFlagHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(ToggleFeatureFlagCommand $command): void
    {
        if (FeatureFlagType::Bool !== $command->flag->type) {
            return;
        }

        $command->flag->value = !((bool) $command->flag->value);
        $this->entityManager->flush();

        $this->logger->info('feature_flag.toggled', [
            'feature_flag_id' => $command->flag->id,
            'name' => $command->flag->name,
            'enabled' => $command->flag->value,
        ]);
    }
}
