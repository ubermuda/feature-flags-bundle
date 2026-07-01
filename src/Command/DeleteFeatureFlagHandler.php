<?php

namespace Ubermuda\FeatureFlagsBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final readonly class DeleteFeatureFlagHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(DeleteFeatureFlagCommand $command): void
    {
        $flagId = $command->flag->id;
        $name = $command->flag->name;

        $this->entityManager->remove($command->flag);
        $this->entityManager->flush();

        $this->logger->info('feature_flag.deleted', [
            'feature_flag_id' => $flagId,
            'name' => $name,
        ]);
    }
}
