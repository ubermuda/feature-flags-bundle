<?php

namespace Ubermuda\FeatureFlagsBundle\Test;

use PHPUnit\Framework\TestCase;
use Ubermuda\FeatureFlagsBundle\Dto\ResolvedFlag;
use Ubermuda\FeatureFlagsBundle\Enum\FeatureFlagType;
use Ubermuda\FeatureFlagsBundle\FeatureFlagService;
use Ubermuda\FeatureFlagsBundle\Prerequisite\FeatureFlagPrerequisites;
use Ubermuda\FeatureFlagsBundle\Reader\InMemoryFeatureFlagReader;

final class FeatureFlagServiceTest extends TestCase
{
    private function service(ResolvedFlag ...$flags): array
    {
        $logger = new RecordingLogger();
        $reader = new InMemoryFeatureFlagReader($flags);

        return [new FeatureFlagService($reader, $logger), $logger];
    }

    public function testIsEnabledReturnsDefaultWhenMissing(): void
    {
        [$service] = $this->service();

        self::assertFalse($service->isEnabled('missing'));
        self::assertTrue($service->isEnabled('missing', true));
    }

    public function testIsEnabledReadsBoolValue(): void
    {
        [$service] = $this->service(
            new ResolvedFlag('on', FeatureFlagType::Bool, true),
            new ResolvedFlag('off', FeatureFlagType::Bool, false),
        );

        self::assertTrue($service->isEnabled('on'));
        self::assertFalse($service->isEnabled('off'));
    }

    public function testIsEnabledLogsAndFallsBackOnTypeMismatch(): void
    {
        [$service, $logger] = $this->service(new ResolvedFlag('n', FeatureFlagType::Int, 5));

        self::assertTrue($service->isEnabled('n', true));
        self::assertTrue($logger->hasErrorRecords());
    }

    public function testGetIntValueReadsAndFallsBack(): void
    {
        [$service, $logger] = $this->service(
            new ResolvedFlag('count', FeatureFlagType::Int, 42),
            new ResolvedFlag('flagged', FeatureFlagType::Bool, true),
        );

        self::assertSame(42, $service->getIntValue('count', 0));
        self::assertSame(7, $service->getIntValue('missing', 7));
        self::assertSame(7, $service->getIntValue('flagged', 7));
        self::assertTrue($logger->hasErrorRecords());
    }

    public function testGetStringValueReadsAndFallsBack(): void
    {
        [$service, $logger] = $this->service(
            new ResolvedFlag('label', FeatureFlagType::String, 'hello'),
            new ResolvedFlag('flagged', FeatureFlagType::Bool, true),
        );

        self::assertSame('hello', $service->getStringValue('label', 'fallback'));
        self::assertSame('fallback', $service->getStringValue('missing', 'fallback'));
        self::assertSame('', $service->getStringValue('missing'));
        self::assertSame('fallback', $service->getStringValue('flagged', 'fallback'));
        self::assertTrue($logger->hasErrorRecords());
    }

    public function testGetValueReturnsRawValueOrNull(): void
    {
        [$service] = $this->service(new ResolvedFlag('choice', FeatureFlagType::Select, 'b'));

        self::assertSame('b', $service->getValue('choice'));
        self::assertNull($service->getValue('missing'));
    }

    /**
     * An unsatisfied prerequisite beats everything else the read could say:
     * a stored true, and a caller whose default is true. Both are exercised
     * because they fail through different branches — one reads the flag, the
     * other never finds one.
     */
    public function testAnUnsatisfiedPrerequisiteForcesTheFlagOff(): void
    {
        $prerequisites = new FeatureFlagPrerequisites(['push' => ['MISSING' => null]]);
        $reader = new InMemoryFeatureFlagReader([new ResolvedFlag('push', FeatureFlagType::Bool, true)]);
        $service = new FeatureFlagService($reader, new RecordingLogger(), $prerequisites);

        self::assertFalse($service->isEnabled('push'));
        self::assertFalse($service->isEnabled('push', true));
    }

    public function testAnUnsatisfiedPrerequisiteForcesOffEvenWhenNoFlagExists(): void
    {
        $prerequisites = new FeatureFlagPrerequisites(['push' => ['MISSING' => null]]);
        $service = new FeatureFlagService(new InMemoryFeatureFlagReader(), new RecordingLogger(), $prerequisites);

        self::assertFalse($service->isEnabled('push', true));
    }

    public function testASatisfiedPrerequisiteLeavesTheStoredValueAlone(): void
    {
        $prerequisites = new FeatureFlagPrerequisites(['push' => ['PRESENT' => 'value']]);
        $reader = new InMemoryFeatureFlagReader([
            new ResolvedFlag('push', FeatureFlagType::Bool, false),
            new ResolvedFlag('other', FeatureFlagType::Bool, true),
        ]);
        $service = new FeatureFlagService($reader, new RecordingLogger(), $prerequisites);

        self::assertFalse($service->isEnabled('push'));
        self::assertTrue($service->isEnabled('other'));
    }

    /**
     * Prerequisites are a boolean-availability concept, so they must not reach
     * the typed readers — "unavailable" has no meaning for a string.
     */
    public function testPrerequisitesDoNotAffectNonBooleanReads(): void
    {
        $prerequisites = new FeatureFlagPrerequisites(['label' => ['MISSING' => null]]);
        $reader = new InMemoryFeatureFlagReader([new ResolvedFlag('label', FeatureFlagType::String, 'hello')]);
        $service = new FeatureFlagService($reader, new RecordingLogger(), $prerequisites);

        self::assertSame('hello', $service->getStringValue('label'));
        self::assertSame('hello', $service->getValue('label'));
    }
}
