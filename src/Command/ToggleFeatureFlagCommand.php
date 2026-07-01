<?php

namespace Ubermuda\FeatureFlagsBundle\Command;

use Ubermuda\FeatureFlagsBundle\Entity\FeatureFlag;

final readonly class ToggleFeatureFlagCommand
{
    public function __construct(
        public FeatureFlag $flag,
    ) {
    }
}
