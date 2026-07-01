<?php

namespace Ubermuda\FeatureFlagsBundle\Test\Reader;

use PHPUnit\Framework\TestCase;
use Ubermuda\FeatureFlagsBundle\Dto\ResolvedFlag;
use Ubermuda\FeatureFlagsBundle\Entity\FeatureFlag;
use Ubermuda\FeatureFlagsBundle\Enum\FeatureFlagType;
use Ubermuda\FeatureFlagsBundle\Reader\DoctrineFeatureFlagReader;
use Ubermuda\FeatureFlagsBundle\Repository\FeatureFlagRepository;

final class DoctrineFeatureFlagReaderTest extends TestCase
{
    public function testMapsEntitiesToResolvedFlags(): void
    {
        $flag = new FeatureFlag('feature', FeatureFlagType::Int, 3);

        $repository = $this->createMock(FeatureFlagRepository::class);
        $repository->method('findAllIndexed')->willReturn(['feature' => $flag]);

        $reader = new DoctrineFeatureFlagReader($repository);

        $resolved = $reader->get('feature');
        self::assertInstanceOf(ResolvedFlag::class, $resolved);
        self::assertSame('feature', $resolved->name);
        self::assertSame(FeatureFlagType::Int, $resolved->type);
        self::assertSame(3, $resolved->value);
        self::assertNull($reader->get('missing'));
    }

    public function testCachesAcrossCalls(): void
    {
        $repository = $this->createMock(FeatureFlagRepository::class);
        $repository->expects(self::once())
            ->method('findAllIndexed')
            ->willReturn(['a' => new FeatureFlag('a', FeatureFlagType::Bool, true)]);

        $reader = new DoctrineFeatureFlagReader($repository);

        $reader->get('a');
        $reader->all();
        $reader->get('a');

        self::assertArrayHasKey('a', $reader->all());
    }
}
