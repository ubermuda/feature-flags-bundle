<?php

namespace Ubermuda\FeatureFlagsBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class UbermudaFeatureFlagsBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->scalarNode('route_prefix')
                    ->defaultValue('/admin/feature-flags')
                    ->info('URL prefix the admin routes are mounted under.')
                ->end()
                ->arrayNode('prerequisites')
                    ->info('Environment variables a flag needs before it can be enabled at all. A flag whose variables are unset or blank reads as off, whoever set it, and the admin shows it as unavailable rather than offering a switch that does nothing. Forces off only — there is no way to force a flag on.')
                    ->useAttributeAsKey('flag')
                    ->arrayPrototype()
                        ->scalarPrototype()->end()
                    ->end()
                ->end()
                ->arrayNode('scan')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('paths')
                            ->info('Directories scanned for referenced flag names.')
                            ->scalarPrototype()->end()
                            ->defaultValue([
                                '%kernel.project_dir%/templates',
                                '%kernel.project_dir%/src',
                            ])
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $builder->setParameter('ubermuda_feature_flags.route_prefix', $config['route_prefix']);
        $builder->setParameter('ubermuda_feature_flags.scan.paths', $config['scan']['paths']);

        // Each required variable becomes an env placeholder rather than being
        // read here, so the check resolves the way the rest of the application
        // resolves configuration — .env chain, real environment or a secrets
        // vault — and is evaluated per request rather than frozen into the
        // compiled container. `default::` yields null for an unset variable
        // instead of failing the build.
        $requiredEnv = [];
        foreach ($config['prerequisites'] as $flag => $names) {
            foreach ($names as $name) {
                $requiredEnv[$flag][$name] = '%env(default::'.$name.')%';
            }
        }

        $builder->setParameter('ubermuda_feature_flags.prerequisites', $requiredEnv);

        $container->import('../config/services.php');
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        if ($builder->hasExtension('framework')) {
            // The admin's hand-rolled forms (toggle, delete, prune-orphaned) are guarded
            // by #[CsrfToken]; register their stateless token ids so the app doesn't have to.
            $builder->prependExtensionConfig('framework', [
                'csrf_protection' => [
                    'stateless_token_ids' => [
                        'feature_flag_toggle',
                        'feature_flag_delete',
                        'feature_flag_delete_orphaned',
                    ],
                ],
            ]);
        }

        if (!$builder->hasExtension('doctrine')) {
            return;
        }

        $builder->prependExtensionConfig('doctrine', [
            'orm' => [
                'mappings' => [
                    'UbermudaFeatureFlagsBundle' => [
                        'type' => 'attribute',
                        'dir' => __DIR__.'/Entity',
                        'prefix' => 'Ubermuda\\FeatureFlagsBundle\\Entity',
                        'alias' => 'UbermudaFeatureFlags',
                        'is_bundle' => false,
                    ],
                ],
            ],
        ]);
    }
}
