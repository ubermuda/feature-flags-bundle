<?php

namespace Ubermuda\FeatureFlagsBundle\Reader;

use Ubermuda\FeatureFlagsBundle\Dto\ResolvedFlag;

/**
 * The runtime read seam. Implementations resolve flag names to plain
 * {@see ResolvedFlag} value objects. The admin query/write path is deliberately
 * not part of this contract — see FeatureFlagRepository.
 */
interface FeatureFlagReaderInterface
{
    public function get(string $name): ?ResolvedFlag;

    /** @return array<string, ResolvedFlag> */
    public function all(): array;
}
