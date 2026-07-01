<?php

namespace Ubermuda\FeatureFlagsBundle\Twig;

use Twig\Attribute\AsTwigFunction;
use Ubermuda\FeatureFlagsBundle\FeatureFlagService;

readonly class FeatureFlagExtension
{
    public function __construct(
        private FeatureFlagService $featureFlagService,
    ) {
    }

    #[AsTwigFunction('is_feature_enabled')]
    public function isFeatureEnabled(string $name): bool
    {
        return $this->featureFlagService->isEnabled($name);
    }

    #[AsTwigFunction('feature_flag_value')]
    public function featureFlagValue(string $name): mixed
    {
        return $this->featureFlagService->getValue($name);
    }
}
