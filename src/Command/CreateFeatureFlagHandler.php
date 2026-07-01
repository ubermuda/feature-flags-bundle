<?php

namespace Ubermuda\FeatureFlagsBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Ubermuda\FeatureFlagsBundle\Entity\FeatureFlag;

final readonly class CreateFeatureFlagHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(CreateFeatureFlagCommand $command): FeatureFlag
    {
        $flag = new FeatureFlag(
            name: $command->name,
            type: $command->type,
            value: $command->value,
        );
        $flag->tags = $command->tags;
        $flag->options = $command->options;

        $this->entityManager->persist($flag);
        $this->entityManager->flush();

        $this->logger->info('feature_flag.created', [
            'feature_flag_id' => $flag->id,
            'name' => $flag->name,
            'type' => $flag->type->value,
        ]);

        return $flag;
    }
}
