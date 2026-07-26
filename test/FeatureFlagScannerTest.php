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
        // isEnabled('gamma', true) — both two-argument calls — plus 'zeta' from
        // getStringValue('zeta', ''), 'epsilon', referenced only through a
        // class constant (self::EPSILON_FLAG / Sample::EPSILON_FLAG), and 'eta',
        // whose constant (Flags::ETA_FLAG) is defined in a different file than the
        // call site. Sample.php also references self::UNRESOLVED_FLAG, defined
        // nowhere — unresolvable references are skipped, so nothing else appears.
        self::assertSame(['alpha', 'beta', 'delta', 'epsilon', 'eta', 'gamma', 'zeta'], $scanner->findReferencedFlags());
    }

    public function testReturnsEmptyWhenNoPathsExist(): void
    {
        $scanner = new FeatureFlagScanner([__DIR__.'/Fixtures/does-not-exist']);

        self::assertSame([], $scanner->findReferencedFlags());
    }
}
