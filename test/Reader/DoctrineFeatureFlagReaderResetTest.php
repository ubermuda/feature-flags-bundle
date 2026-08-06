<?php

namespace Ubermuda\FeatureFlagsBundle\Test\Reader;

use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Service\ResetInterface;
use Ubermuda\FeatureFlagsBundle\Entity\FeatureFlag;
use Ubermuda\FeatureFlagsBundle\Enum\FeatureFlagType;
use Ubermuda\FeatureFlagsBundle\Reader\DoctrineFeatureFlagReader;
use Ubermuda\FeatureFlagsBundle\Repository\FeatureFlagRepository;

final class DoctrineFeatureFlagReaderResetTest extends TestCase
{
    public function testItIsResettableSoALongLivedWorkerSeesAToggle(): void
    {
        $repository = $this->createMock(FeatureFlagRepository::class);
        $repository->expects($this->exactly(2))
            ->method('findAllIndexed')
            ->willReturnOnConsecutiveCalls(
                ['push' => new FeatureFlag(name: 'push', type: FeatureFlagType::Bool, value: true)],
                ['push' => new FeatureFlag(name: 'push', type: FeatureFlagType::Bool, value: false)],
            );

        $reader = new DoctrineFeatureFlagReader($repository);

        self::assertInstanceOf(ResetInterface::class, $reader);
        self::assertTrue($reader->get('push')?->value);

        // Without the reset this second read returns the first value forever,
        // which is what a messenger worker would do for its whole lifetime.
        $reader->reset();

        self::assertFalse($reader->get('push')?->value);
    }

    public function testItStillCachesWithinOneRequest(): void
    {
        $repository = $this->createMock(FeatureFlagRepository::class);
        $repository->expects($this->once())
            ->method('findAllIndexed')
            ->willReturn(['push' => new FeatureFlag(name: 'push', type: FeatureFlagType::Bool, value: true)]);

        $reader = new DoctrineFeatureFlagReader($repository);

        $reader->get('push');
        $reader->get('push');
        $reader->all();
    }
}
