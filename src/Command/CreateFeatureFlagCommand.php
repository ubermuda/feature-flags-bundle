<?php

namespace Ubermuda\FeatureFlagsBundle\Command;

use Ubermuda\FeatureFlagsBundle\Enum\FeatureFlagType;

final readonly class CreateFeatureFlagCommand
{
    /**
     * @param list<string>      $tags
     * @param list<string>|null $options
     */
    public function __construct(
        public string $name,
        public FeatureFlagType $type,
        public bool|int|string|null $value,
        public array $tags,
        public ?array $options,
    ) {
    }
}
