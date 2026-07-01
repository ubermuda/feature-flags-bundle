<?php

namespace Ubermuda\FeatureFlagsBundle\Form;

use Symfony\Component\Validator\Constraints\NotBlank;
use Ubermuda\FeatureFlagsBundle\Entity\FeatureFlag;
use Ubermuda\FeatureFlagsBundle\Enum\FeatureFlagType;

class FeatureFlagRequest
{
    public function __construct(
        #[NotBlank]
        public ?string $name = null,
        public FeatureFlagType $type = FeatureFlagType::Bool,
        public ?bool $boolValue = null,
        public ?int $intValue = null,
        public ?string $selectValue = null,
        /** @var list<string> */
        public array $options = [],
        /** @var list<string> */
        public array $tags = [],
    ) {
    }

    public static function fromFlag(FeatureFlag $flag): self
    {
        return new self(
            name: $flag->name,
            type: $flag->type,
            boolValue: FeatureFlagType::Bool === $flag->type ? (bool) $flag->value : null,
            intValue: FeatureFlagType::Int === $flag->type ? (int) $flag->value : null,
            selectValue: FeatureFlagType::Select === $flag->type && is_string($flag->value) ? $flag->value : null,
            options: $flag->options ?? [],
            tags: $flag->tags,
        );
    }
}
