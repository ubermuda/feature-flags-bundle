<?php

namespace Ubermuda\FeatureFlagsBundle\Dto;

use Ubermuda\FeatureFlagsBundle\Enum\FeatureFlagType;

/**
 * Plain runtime value object for a feature flag. Carries only what the read API
 * needs; deliberately free of Doctrine mapping so a non-Doctrine reader can build
 * one without touching the ORM entity.
 */
final readonly class ResolvedFlag
{
    public function __construct(
        public string $name,
        public FeatureFlagType $type,
        public mixed $value,
    ) {
    }
}
