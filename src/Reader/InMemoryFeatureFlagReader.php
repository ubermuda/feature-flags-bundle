<?php

namespace Ubermuda\FeatureFlagsBundle\Reader;

use Ubermuda\FeatureFlagsBundle\Dto\ResolvedFlag;

/**
 * Array-backed reader for tests and config-driven / non-Doctrine runtimes.
 */
final class InMemoryFeatureFlagReader implements FeatureFlagReaderInterface
{
    /** @var array<string, ResolvedFlag> */
    private array $flags = [];

    /**
     * @param iterable<ResolvedFlag> $flags
     */
    public function __construct(iterable $flags = [])
    {
        foreach ($flags as $flag) {
            $this->flags[$flag->name] = $flag;
        }
    }

    public function get(string $name): ?ResolvedFlag
    {
        return $this->flags[$name] ?? null;
    }

    public function all(): array
    {
        return $this->flags;
    }
}
