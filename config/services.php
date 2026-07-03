<?php

use Ubermuda\FeatureFlagsBundle\Reader\DoctrineFeatureFlagReader;
use Ubermuda\FeatureFlagsBundle\Reader\FeatureFlagReaderInterface;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;

return static function (Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator $container): void {
    $services = $container->services();

    $services->defaults()
        ->autowire()
        ->autoconfigure()
        ->bind('$scanPaths', param('ubermuda_feature_flags.scan.paths'));

    $services->load('Ubermuda\\FeatureFlagsBundle\\', __DIR__.'/../src/')
        ->exclude([
            __DIR__.'/../src/Attribute/',
            __DIR__.'/../src/Entity/',
            __DIR__.'/../src/Dto/',
            __DIR__.'/../src/Enum/',
            __DIR__.'/../src/Command/*Command.php',
            __DIR__.'/../src/Form/FeatureFlagRequest.php',
            __DIR__.'/../src/Reader/InMemoryFeatureFlagReader.php',
            __DIR__.'/../src/UbermudaFeatureFlagsBundle.php',
        ]);

    $services->alias(FeatureFlagReaderInterface::class, DoctrineFeatureFlagReader::class);
};
