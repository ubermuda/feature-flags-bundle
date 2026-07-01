<?php

namespace Ubermuda\FeatureFlagsBundle;

use Psr\Log\LoggerInterface;
use Ubermuda\FeatureFlagsBundle\Enum\FeatureFlagType;
use Ubermuda\FeatureFlagsBundle\Reader\FeatureFlagReaderInterface;

readonly class FeatureFlagService
{
    public function __construct(
        private FeatureFlagReaderInterface $reader,
        private LoggerInterface $logger,
    ) {
    }

    public function isEnabled(string $name, bool $default = false): bool
    {
        $flag = $this->reader->get($name);

        if (null === $flag) {
            return $default;
        }

        if (FeatureFlagType::Bool !== $flag->type) {
            $this->logger->error(sprintf(
                'Feature flag "%s" is of type "%s", not "bool". Returning default value.',
                $name,
                $flag->type->value,
            ));

            return $default;
        }

        return (bool) $flag->value;
    }

    public function getIntValue(string $name, int $default): int
    {
        $flag = $this->reader->get($name);

        if (null === $flag) {
            return $default;
        }

        if (FeatureFlagType::Int !== $flag->type) {
            $this->logger->error(sprintf(
                'Feature flag "%s" is of type "%s", not "int". Returning default value.',
                $name,
                $flag->type->value,
            ));

            return $default;
        }

        return is_int($flag->value) ? $flag->value : $default;
    }

    public function getValue(string $name): mixed
    {
        return $this->reader->get($name)?->value;
    }
}
