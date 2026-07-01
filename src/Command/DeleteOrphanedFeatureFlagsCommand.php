<?php

namespace Ubermuda\FeatureFlagsBundle\Command;

final readonly class DeleteOrphanedFeatureFlagsCommand
{
    /**
     * @param list<string> $selectedNames
     */
    public function __construct(
        public array $selectedNames,
    ) {
    }
}
