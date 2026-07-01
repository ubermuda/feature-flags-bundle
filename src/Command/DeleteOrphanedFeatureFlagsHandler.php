<?php

namespace Ubermuda\FeatureFlagsBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Ubermuda\FeatureFlagsBundle\Repository\FeatureFlagRepository;
use Ubermuda\FeatureFlagsBundle\Scanner\FeatureFlagScanner;

final readonly class DeleteOrphanedFeatureFlagsHandler
{
    public function __construct(
        private FeatureFlagRepository $featureFlags,
        private FeatureFlagScanner $scanner,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(DeleteOrphanedFeatureFlagsCommand $command): void
    {
        $referencedNames = $this->scanner->findReferencedFlags();

        $removedNames = [];
        foreach ($this->featureFlags->findAll() as $flag) {
            if (!in_array($flag->name, $referencedNames, true)) {
                if ([] === $command->selectedNames || in_array($flag->name, $command->selectedNames, true)) {
                    $this->entityManager->remove($flag);
                    $removedNames[] = $flag->name;
                }
            }
        }

        $this->entityManager->flush();

        $this->logger->info('feature_flag.orphaned_pruned', [
            'count' => count($removedNames),
            'names' => $removedNames,
        ]);
    }
}
