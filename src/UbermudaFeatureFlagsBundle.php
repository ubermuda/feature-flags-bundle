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

        $container->import('../config/services.php');
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        if ($builder->hasExtension('framework')) {
            // The admin's hand-rolled forms (toggle, prune-orphaned) are guarded by
            // #[CsrfToken]; register their stateless token ids so the app doesn't have to.
            $builder->prependExtensionConfig('framework', [
                'csrf_protection' => [
                    'stateless_token_ids' => [
                        'feature_flag_toggle',
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
