<?php

namespace Ubermuda\FeatureFlagsBundle\Command;

use Ubermuda\FeatureFlagsBundle\Entity\FeatureFlag;
use Ubermuda\FeatureFlagsBundle\Enum\FeatureFlagType;

final readonly class UpdateFeatureFlagCommand
{
    /**
     * @param list<string>      $tags
     * @param list<string>|null $options
     */
    public function __construct(
        public FeatureFlag $flag,
        public string $name,
        public FeatureFlagType $type,
        public bool|int|string|null $value,
        public array $tags,
        public ?array $options,
    ) {
    }
}
