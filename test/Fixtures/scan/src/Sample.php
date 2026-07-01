<?php

namespace Ubermuda\FeatureFlagsBundle\Test\Fixtures\Scan;

final class Sample
{
    public function run($featureFlags): void
    {
        $featureFlags->isEnabled('gamma', true);
        $featureFlags->getValue('beta');
        $featureFlags->getIntValue('delta', 0);
    }
}
