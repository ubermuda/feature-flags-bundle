<?php

namespace Ubermuda\FeatureFlagsBundle\Test\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
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

    public function testFeatureFlagsMenuItemAutoTagsAndRendersInTheAdminSidebar(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $container->get('request_stack')->push($request);

        // Rendering the admin base exercises admin_menu_items(), which returns
        // every service tagged app.admin_menu_item. FeatureFlagsMenuItem auto-tags
        // via the admin bundle's instanceof autoconfiguration (services.php loads
        // src/ with autoconfigure and does not exclude Menu/).
        $html = $container->get('twig')->render('@Test/renders_admin_base.html.twig');

        self::assertStringContainsString('Feature Flags', $html);
        // The nav link points at the FF list route, proving the item's routeName.
        self::assertStringContainsString('/admin/feature-flags', $html);
        self::assertStringContainsString('admin-nav-link', $html);
    }
}
