<?php

namespace Ubermuda\FeatureFlagsBundle\Prerequisite;

/**
 * Environment a feature needs before it can work at all.
 *
 * A flag answers "should this be on?", which is the operator's choice. A
 * prerequisite answers "can this work here?", which is not — a feature whose
 * configuration is absent cannot be switched on by anybody, and pretending
 * otherwise gives an operator a switch that appears to work and does nothing.
 *
 * Prerequisites therefore only ever force a flag **off**. There is deliberately
 * no way to express the other direction: a rule that forced a flag on would
 * override a deliberate decision to disable something, with no way to override
 * it back from the admin UI.
 *
 * They apply to boolean flags, through FeatureFlagService::isEnabled(). Other
 * flag types read their value unchanged — "unavailable" has no meaning for a
 * string or an integer.
 */
final readonly class FeatureFlagPrerequisites
{
    /**
     * Keyed by flag name, then by environment variable name. Values are
     * resolved by the container, so `%env()%` semantics apply: whatever the
     * application's own configuration would see — real environment, .env chain
     * or a secrets vault — is what is checked here.
     *
     * @param array<string, array<string, string|null>> $requiredEnv
     */
    public function __construct(
        private array $requiredEnv = [],
    ) {
    }

    public function isSatisfied(string $flag): bool
    {
        return [] === $this->missingFor($flag);
    }

    /**
     * Names of the environment variables this flag needs that are absent.
     *
     * An empty string counts as absent. A variable that exists but is blank is
     * the shape an unset value takes in most deployment tooling, and treating
     * it as present would make the check pass while the feature stayed broken.
     *
     * @return list<string>
     */
    public function missingFor(string $flag): array
    {
        $missing = [];

        foreach ($this->requiredEnv[$flag] ?? [] as $name => $value) {
            if (null === $value || '' === $value) {
                $missing[] = $name;
            }
        }

        return $missing;
    }

    /**
     * Flags that declare a prerequisite, whether or not it is satisfied.
     *
     * @return list<string>
     */
    public function flagsWithPrerequisites(): array
    {
        return array_keys($this->requiredEnv);
    }
}
