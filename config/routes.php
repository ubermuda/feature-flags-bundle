<?php

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Ubermuda\FeatureFlagsBundle\Controller\Admin\CreateFeatureFlagController;
use Ubermuda\FeatureFlagsBundle\Controller\Admin\DeleteFeatureFlagController;
use Ubermuda\FeatureFlagsBundle\Controller\Admin\DeleteOrphanedFeatureFlagsController;
use Ubermuda\FeatureFlagsBundle\Controller\Admin\EditFeatureFlagController;
use Ubermuda\FeatureFlagsBundle\Controller\Admin\ListFeatureFlagsController;
use Ubermuda\FeatureFlagsBundle\Controller\Admin\ScanFeatureFlagsController;
use Ubermuda\FeatureFlagsBundle\Controller\Admin\ToggleFeatureFlagController;

return static function (RoutingConfigurator $routes): void {
    $prefix = '%ubermuda_feature_flags.route_prefix%';

    $routes->add('ubermuda_feature_flags_list', $prefix)
        ->controller(ListFeatureFlagsController::class)
        ->methods(['GET']);

    $routes->add('ubermuda_feature_flags_create', $prefix.'/new')
        ->controller(CreateFeatureFlagController::class)
        ->methods(['GET', 'POST']);

    $routes->add('ubermuda_feature_flags_scan', $prefix.'/scan')
        ->controller(ScanFeatureFlagsController::class)
        ->methods(['GET']);

    $routes->add('ubermuda_feature_flags_delete_orphaned', $prefix.'/delete-orphaned')
        ->controller(DeleteOrphanedFeatureFlagsController::class)
        ->methods(['POST']);

    $routes->add('ubermuda_feature_flags_edit', $prefix.'/{id}/edit')
        ->controller(EditFeatureFlagController::class)
        ->methods(['GET', 'POST'])
        ->requirements(['id' => '\d+']);

    $routes->add('ubermuda_feature_flags_delete', $prefix.'/{id}/delete')
        ->controller(DeleteFeatureFlagController::class)
        ->methods(['GET', 'POST'])
        ->requirements(['id' => '\d+']);

    $routes->add('ubermuda_feature_flags_toggle', $prefix.'/{id}/toggle')
        ->controller(ToggleFeatureFlagController::class)
        ->methods(['POST'])
        ->requirements(['id' => '\d+']);
};
