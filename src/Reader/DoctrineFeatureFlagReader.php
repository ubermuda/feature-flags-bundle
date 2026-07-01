<?php

namespace Ubermuda\FeatureFlagsBundle\Reader;

use Ubermuda\FeatureFlagsBundle\Dto\ResolvedFlag;
use Ubermuda\FeatureFlagsBundle\Entity\FeatureFlag;
use Ubermuda\FeatureFlagsBundle\Repository\FeatureFlagRepository;

/**
 * Default reader: Doctrine-backed and request-cached. Loads every flag once,
 * mapping each entity to a {@see ResolvedFlag} so callers never touch the ORM.
 */
class DoctrineFeatureFlagReader implements FeatureFlagReaderInterface
{
    /** @var array<string, ResolvedFlag>|null */
    private ?array $cache = null;

    public function __construct(
        private readonly FeatureFlagRepository $featureFlags,
    ) {
    }

    public function get(string $name): ?ResolvedFlag
    {
        return $this->all()[$name] ?? null;
    }

    public function all(): array
    {
        if (null === $this->cache) {
            $this->cache = array_map(
                static fn (FeatureFlag $flag): ResolvedFlag => new ResolvedFlag(
                    name: $flag->name,
                    type: $flag->type,
                    value: $flag->value,
                ),
                $this->featureFlags->findAllIndexed(),
            );
        }

        return $this->cache;
    }
}
