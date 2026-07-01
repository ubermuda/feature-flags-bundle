<?php

namespace Ubermuda\FeatureFlagsBundle\Test;

use PHPUnit\Framework\TestCase;
use Ubermuda\FeatureFlagsBundle\Dto\ResolvedFlag;
use Ubermuda\FeatureFlagsBundle\Enum\FeatureFlagType;
use Ubermuda\FeatureFlagsBundle\FeatureFlagService;
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

    public function testGetValueReturnsRawValueOrNull(): void
    {
        [$service] = $this->service(new ResolvedFlag('choice', FeatureFlagType::Select, 'b'));

        self::assertSame('b', $service->getValue('choice'));
        self::assertNull($service->getValue('missing'));
    }
}
