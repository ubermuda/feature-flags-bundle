<?php

namespace Ubermuda\FeatureFlagsBundle\Test\Functional;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\UX\Icons\UXIconsBundle;
use Symfony\UX\TwigComponent\TwigComponentBundle;
use Ubermuda\AdminBundle\UbermudaAdminBundle;
use Ubermuda\FeatureFlagsBundle\UbermudaFeatureFlagsBundle;

final class FeatureFlagsTestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new DoctrineBundle(),
            new TwigBundle(),
            new SecurityBundle(),
            new UXIconsBundle(),
            new TwigComponentBundle(),
            new UbermudaAdminBundle(),
            new UbermudaFeatureFlagsBundle(),
        ];
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/ubermuda-feature-flags/cache/'.$this->environment;
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir().'/ubermuda-feature-flags/log';
    }

    protected function configureContainer(ContainerConfigurator $container, LoaderInterface $loader): void
    {
        $container->extension('framework', [
            'secret' => 'test',
            'test' => true,
            'csrf_protection' => true,
            'http_method_override' => false,
            'handle_all_throwables' => true,
            'php_errors' => ['log' => true],
            'session' => [
                'storage_factory_id' => 'session.storage.factory.mock_file',
            ],
        ]);

        $container->extension('doctrine', [
            'dbal' => [
                'driver' => 'pdo_sqlite',
                'url' => 'sqlite:///:memory:',
            ],
            'orm' => [
                'auto_mapping' => false,
            ],
        ]);

        $container->extension('twig', [
            'strict_variables' => true,
            // Register the fixture templates so the render tests can override the
            // admin base's importmap block (not wired in this kernel) before rendering.
            'paths' => [__DIR__.'/Fixtures/templates' => 'Test'],
        ]);

        $container->extension('twig_component', [
            'anonymous_template_directory' => 'components/',
            'defaults' => [],
        ]);

        // ux-icons resolves `lucide:*` from the Iconify API by default; ignore
        // missing icons so the admin sidebar renders without a network round-trip.
        $container->extension('ux_icons', [
            'ignore_not_found' => true,
        ]);

        $container->extension('security', [
            'providers' => ['in_memory' => ['memory' => null]],
            'firewalls' => ['main' => ['lazy' => true]],
        ]);
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import(__DIR__.'/../../config/routes.php');

        // Stub route so path('app_dashboard') resolves in the admin base layout
        // (the admin bundle's "Back to app" link + brand link).
        $routes->add('app_dashboard', '/')->methods(['GET']);
    }
}
