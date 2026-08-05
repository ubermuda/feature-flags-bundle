<?php

namespace Ubermuda\FeatureFlagsBundle\Test\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Ubermuda\FeatureFlagsBundle\FeatureFlagService;
use Ubermuda\FeatureFlagsBundle\Prerequisite\FeatureFlagPrerequisites;

/**
 * The configured variable names have to survive as env *placeholders* rather
 * than being read while the container is built, or a container compiled on a
 * machine without the variable would keep answering "unavailable" after the
 * variable was supplied. These tests boot the same compiled container twice
 * with the variable set differently, which only passes if resolution is
 * happening at runtime.
 */
final class PrerequisiteWiringTest extends KernelTestCase
{
    public const string VARIABLE = 'FF_PREREQUISITE_TEST_VAR';

    protected static function getKernelClass(): string
    {
        return PrerequisiteTestKernel::class;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($_ENV[self::VARIABLE], $_SERVER[self::VARIABLE]);

        // FrameworkBundle::boot() registers an exception handler that kernel
        // shutdown does not pop; restore it so PHPUnit does not flag the test risky.
        restore_exception_handler();
    }

    public function testAnUnsetVariableMakesTheFlagUnavailable(): void
    {
        unset($_ENV[self::VARIABLE], $_SERVER[self::VARIABLE]);
        self::bootKernel();
        $container = self::getContainer();

        $prerequisites = $container->get(FeatureFlagPrerequisites::class);
        self::assertInstanceOf(FeatureFlagPrerequisites::class, $prerequisites);
        self::assertSame([self::VARIABLE], $prerequisites->missingFor('gated'));

        $flags = $container->get(FeatureFlagService::class);
        self::assertInstanceOf(FeatureFlagService::class, $flags);
        // Default true, so this can only be false because the prerequisite won.
        self::assertFalse($flags->isEnabled('gated', true));
    }

    public function testSupplyingTheVariableMakesItAvailableWithoutRebuilding(): void
    {
        $_ENV[self::VARIABLE] = 'configured';
        $_SERVER[self::VARIABLE] = 'configured';
        self::bootKernel();
        $container = self::getContainer();

        $prerequisites = $container->get(FeatureFlagPrerequisites::class);
        self::assertInstanceOf(FeatureFlagPrerequisites::class, $prerequisites);
        self::assertSame([], $prerequisites->missingFor('gated'));

        // Stops here deliberately. Once the prerequisite is satisfied the service
        // falls through to the Doctrine reader, and this kernel has no schema —
        // what happens after that point is unit-tested against an in-memory
        // reader instead. The assertion above is the wiring claim.
    }

    public function testAFlagWithoutAPrerequisiteIsUntouched(): void
    {
        unset($_ENV[self::VARIABLE], $_SERVER[self::VARIABLE]);
        self::bootKernel();
        $container = self::getContainer();

        $prerequisites = $container->get(FeatureFlagPrerequisites::class);
        self::assertInstanceOf(FeatureFlagPrerequisites::class, $prerequisites);
        self::assertSame([], $prerequisites->missingFor('ungated'));
        self::assertSame(['gated'], $prerequisites->flagsWithPrerequisites());
    }
}

final class PrerequisiteTestKernel extends FeatureFlagsTestKernel
{
    #[\Override]
    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/ubermuda-feature-flags/prerequisite-cache/'.$this->environment;
    }

    #[\Override]
    protected function configureContainer(ContainerConfigurator $container, LoaderInterface $loader): void
    {
        parent::configureContainer($container, $loader);

        $container->extension('ubermuda_feature_flags', [
            'prerequisites' => [
                'gated' => [PrerequisiteWiringTest::VARIABLE],
            ],
        ]);
    }
}
