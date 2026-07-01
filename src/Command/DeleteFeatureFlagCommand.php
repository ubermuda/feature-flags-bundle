<?php

namespace Ubermuda\FeatureFlagsBundle\Command;

use Ubermuda\FeatureFlagsBundle\Entity\FeatureFlag;

final readonly class DeleteFeatureFlagCommand
{
    public function __construct(
        public FeatureFlag $flag,
    ) {
    }
}
