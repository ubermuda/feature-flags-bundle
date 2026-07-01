<?php

namespace Ubermuda\FeatureFlagsBundle\Test\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\RouterInterface;
use Ubermuda\FeatureFlagsBundle\Controller\Admin\ListFeatureFlagsController;
use Ubermuda\FeatureFlagsBundle\FeatureFlagService;
use Ubermuda\FeatureFlagsBundle\Reader\FeatureFlagReaderInterface;
use Ubermuda\FeatureFlagsBundle\Scanner\FeatureFlagScanner;

final class BundleWiringTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return FeatureFlagsTestKernel::class;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // FrameworkBundle::boot() registers an ErrorHandler exception handler that
        // kernel shutdown does not pop; restore it so PHPUnit does not flag the test risky.
        restore_exception_handler();
    }

    public function testCoreServicesAreWired(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        self::assertInstanceOf(FeatureFlagService::class, $container->get(FeatureFlagService::class));
        self::assertInstanceOf(FeatureFlagScanner::class, $container->get(FeatureFlagScanner::class));
        self::assertTrue($container->has(FeatureFlagReaderInterface::class));
    }

    public function testAdminRoutesAreMountedUnderConfiguredPrefix(): void
    {
        self::bootKernel();
        $router = self::getContainer()->get(RouterInterface::class);

        $match = $router->match('/admin/feature-flags');

        self::assertSame(ListFeatureFlagsController::class, $match['_controller']);
    }
}
