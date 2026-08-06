<?php

namespace Ubermuda\FeatureFlagsBundle\Test\Prerequisite;

use PHPUnit\Framework\TestCase;
use Ubermuda\FeatureFlagsBundle\Prerequisite\FeatureFlagPrerequisites;

final class FeatureFlagPrerequisitesTest extends TestCase
{
    public function testAFlagWithNoPrerequisiteIsAlwaysSatisfied(): void
    {
        $prerequisites = new FeatureFlagPrerequisites();

        self::assertTrue($prerequisites->isSatisfied('anything'));
        self::assertSame([], $prerequisites->missingFor('anything'));
    }

    public function testItReportsOnlyTheVariablesThatAreAbsent(): void
    {
        $prerequisites = new FeatureFlagPrerequisites([
            'push' => ['SET' => 'value', 'UNSET' => null, 'ALSO_UNSET' => null],
        ]);

        self::assertSame(['UNSET', 'ALSO_UNSET'], $prerequisites->missingFor('push'));
        self::assertFalse($prerequisites->isSatisfied('push'));
    }

    public function testABlankVariableCountsAsAbsent(): void
    {
        // An unset variable reaches most deployment tooling as an empty string
        // rather than as null. Treating it as present would let the check pass
        // while the feature stayed broken, which is the failure this exists to
        // prevent.
        $prerequisites = new FeatureFlagPrerequisites(['push' => ['BLANK' => '']]);

        self::assertSame(['BLANK'], $prerequisites->missingFor('push'));
    }

    public function testItIsSatisfiedWhenEveryVariableIsPresent(): void
    {
        $prerequisites = new FeatureFlagPrerequisites([
            'push' => ['ONE' => 'a', 'TWO' => 'b'],
        ]);

        self::assertTrue($prerequisites->isSatisfied('push'));
        self::assertSame([], $prerequisites->missingFor('push'));
    }

    public function testAPrerequisiteOnOneFlagDoesNotAffectAnother(): void
    {
        $prerequisites = new FeatureFlagPrerequisites(['push' => ['UNSET' => null]]);

        self::assertFalse($prerequisites->isSatisfied('push'));
        self::assertTrue($prerequisites->isSatisfied('billing'));
    }

    public function testItListsTheFlagsThatDeclareAPrerequisite(): void
    {
        $prerequisites = new FeatureFlagPrerequisites([
            'push' => ['UNSET' => null],
            'billing' => ['SET' => 'x'],
        ]);

        self::assertSame(['push', 'billing'], $prerequisites->flagsWithPrerequisites());
    }
}
