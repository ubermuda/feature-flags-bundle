<?php

namespace Ubermuda\FeatureFlagsBundle\Test\Fixtures\Scan;

final class Sample
{
    private const string EPSILON_FLAG = 'epsilon';

    public function run($featureFlags): void
    {
        $featureFlags->isEnabled('gamma', true);
        $featureFlags->getValue('beta');
        $featureFlags->getIntValue('delta', 0);
        $featureFlags->getStringValue('zeta', '');
        $featureFlags->isEnabled(self::EPSILON_FLAG);
        $featureFlags->getIntValue(Sample::EPSILON_FLAG, 0);
    }
}
