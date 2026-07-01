<?php

namespace Ubermuda\FeatureFlagsBundle\Test\Command;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Ubermuda\FeatureFlagsBundle\Command\CreateFeatureFlagCommand;
use Ubermuda\FeatureFlagsBundle\Command\CreateFeatureFlagHandler;
use Ubermuda\FeatureFlagsBundle\Command\DeleteFeatureFlagCommand;
use Ubermuda\FeatureFlagsBundle\Command\DeleteFeatureFlagHandler;
use Ubermuda\FeatureFlagsBundle\Command\DeleteOrphanedFeatureFlagsCommand;
use Ubermuda\FeatureFlagsBundle\Command\DeleteOrphanedFeatureFlagsHandler;
use Ubermuda\FeatureFlagsBundle\Command\ToggleFeatureFlagCommand;
use Ubermuda\FeatureFlagsBundle\Command\ToggleFeatureFlagHandler;
use Ubermuda\FeatureFlagsBundle\Command\UpdateFeatureFlagCommand;
use Ubermuda\FeatureFlagsBundle\Command\UpdateFeatureFlagHandler;
use Ubermuda\FeatureFlagsBundle\Entity\FeatureFlag;
use Ubermuda\FeatureFlagsBundle\Enum\FeatureFlagType;
use Ubermuda\FeatureFlagsBundle\Repository\FeatureFlagRepository;
use Ubermuda\FeatureFlagsBundle\Scanner\FeatureFlagScanner;
use Ubermuda\FeatureFlagsBundle\Test\RecordingLogger;

final class CommandHandlersTest extends TestCase
{
    public function testCreateLogsObservabilityEvent(): void
    {
        $logger = new RecordingLogger();

        (new CreateFeatureFlagHandler($this->createMock(EntityManagerInterface::class), $logger))(
            new CreateFeatureFlagCommand('feature', FeatureFlagType::Bool, true, [], null),
        );

        self::assertSame('feature_flag.created', $logger->records[0]['message']);
    }

    public function testCreatePersistsAndReturnsFlag(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist');
        $em->expects(self::once())->method('flush');

        $flag = (new CreateFeatureFlagHandler($em, new NullLogger()))(new CreateFeatureFlagCommand(
            name: 'feature',
            type: FeatureFlagType::Select,
            value: 'a',
            tags: ['team'],
            options: ['a', 'b'],
        ));

        self::assertSame('feature', $flag->name);
        self::assertSame(FeatureFlagType::Select, $flag->type);
        self::assertSame('a', $flag->value);
        self::assertSame(['team'], $flag->tags);
        self::assertSame(['a', 'b'], $flag->options);
    }

    public function testUpdateMutatesFlag(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');

        $flag = new FeatureFlag('old', FeatureFlagType::Bool, false);

        (new UpdateFeatureFlagHandler($em, new NullLogger()))(new UpdateFeatureFlagCommand(
            flag: $flag,
            name: 'new',
            type: FeatureFlagType::Int,
            value: 9,
            tags: ['x'],
            options: null,
        ));

        self::assertSame('new', $flag->name);
        self::assertSame(FeatureFlagType::Int, $flag->type);
        self::assertSame(9, $flag->value);
        self::assertSame(['x'], $flag->tags);
    }

    public function testDeleteRemovesFlag(): void
    {
        $flag = new FeatureFlag('d', FeatureFlagType::Bool, true);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('remove')->with($flag);
        $em->expects(self::once())->method('flush');

        (new DeleteFeatureFlagHandler($em, new NullLogger()))(new DeleteFeatureFlagCommand($flag));
    }

    public function testToggleFlipsBoolValue(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');

        $flag = new FeatureFlag('b', FeatureFlagType::Bool, false);

        (new ToggleFeatureFlagHandler($em, new NullLogger()))(new ToggleFeatureFlagCommand($flag));

        self::assertTrue($flag->value);
    }

    public function testToggleLeavesNonBoolUnchangedAndDoesNotFlush(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('flush');

        $flag = new FeatureFlag('n', FeatureFlagType::Int, 5);

        (new ToggleFeatureFlagHandler($em, new NullLogger()))(new ToggleFeatureFlagCommand($flag));

        self::assertSame(5, $flag->value);
    }

    public function testDeleteOrphanedRemovesUnreferencedFlags(): void
    {
        $base = __DIR__.'/../Fixtures/scan';
        // The fixtures reference: alpha, beta, delta, gamma.
        $scanner = new FeatureFlagScanner([$base.'/templates', $base.'/src']);

        $alpha = new FeatureFlag('alpha', FeatureFlagType::Bool, true);
        $orphan = new FeatureFlag('orphan', FeatureFlagType::Bool, true);

        $repository = $this->createMock(FeatureFlagRepository::class);
        $repository->method('findAll')->willReturn([$alpha, $orphan]);

        $removed = [];
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('remove')->willReturnCallback(static function (object $entity) use (&$removed): void {
            $removed[] = $entity;
        });
        $em->expects(self::once())->method('flush');

        (new DeleteOrphanedFeatureFlagsHandler($repository, $scanner, $em, new NullLogger()))(
            new DeleteOrphanedFeatureFlagsCommand([]),
        );

        self::assertSame([$orphan], $removed);
    }
}
