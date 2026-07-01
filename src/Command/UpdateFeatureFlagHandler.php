<?php

namespace Ubermuda\FeatureFlagsBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final readonly class UpdateFeatureFlagHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(UpdateFeatureFlagCommand $command): void
    {
        $flag = $command->flag;
        $flag->name = $command->name;
        $flag->type = $command->type;
        $flag->value = $command->value;
        $flag->tags = $command->tags;
        $flag->options = $command->options;

        $this->entityManager->flush();

        $this->logger->info('feature_flag.updated', [
            'feature_flag_id' => $flag->id,
            'name' => $flag->name,
            'type' => $flag->type->value,
        ]);
    }
}
