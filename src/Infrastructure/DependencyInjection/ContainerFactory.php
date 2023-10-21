<?php

namespace App\Infrastructure\DependencyInjection;

use App\Infrastructure\Environment\Environment;
use App\Infrastructure\Environment\Settings;
use Dotenv\Dotenv;
use Psr\Container\ContainerInterface;

class ContainerFactory
{
    public static function create(): ContainerInterface
    {
        $builder = self::createBuilder('.env');

        return $builder->build();
    }

    public static function createForTestSuite(): ContainerInterface
    {
        $builder = self::createBuilder('.env.test');
        $appRoot = Settings::getAppRoot();

        if (file_exists($appRoot.'/config/container_test.php')) {
            $builder->addDefinitions($appRoot.'/config/container_test.php');
        }

        return $builder->build();
    }

    private static function createBuilder(string $dotEnv): ContainerBuilder
    {
        $appRoot = Settings::getAppRoot();

        $dotenv = Dotenv::createImmutable($appRoot, $dotEnv);
        $dotenv->load();

        // At this point the container has not been built. We need to load the settings manually.
        $settings = Settings::load();
        $containerBuilder = ContainerBuilder::create();

        if (Environment::PRODUCTION === Environment::from($_ENV['ENVIRONMENT'])) {
            // Compile and cache container.
            $containerBuilder->enableCompilation($settings->get('slim.cache_dir').'/container');
            $containerBuilder->enableClassAttributeCache($settings->get('slim.cache_dir').'/class-attributes');
        }
        $containerBuilder->addDefinitions($appRoot.'/config/container.php');
        $containerBuilder->addCompilerPasses(...require $appRoot.'/config/compiler-passes.php');

        return $containerBuilder;
    }
}
