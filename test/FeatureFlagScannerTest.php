<?php

namespace Ubermuda\FeatureFlagsBundle\Test;

use PHPUnit\Framework\TestCase;
use Ubermuda\FeatureFlagsBundle\Scanner\FeatureFlagScanner;

final class FeatureFlagScannerTest extends TestCase
{
    public function testFindsReferencedFlagsSortedAndDeduped(): void
    {
        $base = __DIR__.'/Fixtures/scan';
        $scanner = new FeatureFlagScanner([$base.'/templates', $base.'/src']);

        // Includes 'delta' from getIntValue('delta', 0) and 'gamma' from
        // isEnabled('gamma', true) — both two-argument calls.
        self::assertSame(['alpha', 'beta', 'delta', 'gamma'], $scanner->findReferencedFlags());
    }

    public function testReturnsEmptyWhenNoPathsExist(): void
    {
        $scanner = new FeatureFlagScanner([__DIR__.'/Fixtures/does-not-exist']);

        self::assertSame([], $scanner->findReferencedFlags());
    }
}
