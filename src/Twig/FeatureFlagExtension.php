<?php

namespace Ubermuda\FeatureFlagsBundle\Twig;

use Twig\Attribute\AsTwigFunction;
use Ubermuda\FeatureFlagsBundle\FeatureFlagService;
use Ubermuda\FeatureFlagsBundle\Prerequisite\FeatureFlagPrerequisites;

readonly class FeatureFlagExtension
{
    public function __construct(
        private FeatureFlagService $featureFlagService,
        private FeatureFlagPrerequisites $prerequisites = new FeatureFlagPrerequisites(),
    ) {
    }

    #[AsTwigFunction('is_feature_enabled')]
    public function isFeatureEnabled(string $name): bool
    {
        return $this->featureFlagService->isEnabled($name);
    }

    /**
     * Environment variables this flag needs that are absent, so the admin can
     * say why a switch is not offered instead of rendering one that does
     * nothing. Empty when the flag is available — including when it declares no
     * prerequisite at all.
     *
     * @return list<string>
     */
    #[AsTwigFunction('feature_flag_missing_env')]
    public function featureFlagMissingEnv(string $name): array
    {
        return $this->prerequisites->missingFor($name);
    }

    #[AsTwigFunction('feature_flag_value')]
    public function featureFlagValue(string $name): mixed
    {
        return $this->featureFlagService->getValue($name);
    }
}
