<?php

use App\Domain\Strava\StravaClientId;
use App\Domain\Strava\StravaClientSecret;
use App\Domain\Strava\StravaRefreshToken;
use App\Infrastructure\Console\ConsoleCommandContainer;
use App\Infrastructure\Environment\Environment;
use App\Infrastructure\Environment\Settings;
use App\Infrastructure\Twig\TwigBuilder;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Dotenv\Dotenv;
use Lcobucci\Clock\Clock;
use Lcobucci\Clock\SystemClock;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Symfony\Component\Console\Application;
use Twig\Environment as TwigEnvironment;
use Twig\Loader\FilesystemLoader;

$appRoot = Settings::getAppRoot();

$dotenv = Dotenv::createImmutable($appRoot);
$dotenv->load();

return [
    // Clock.
    Clock::class => DI\factory([SystemClock::class, 'fromSystemTimezone']),
    // Twig Environment.
    FilesystemLoader::class => DI\create(FilesystemLoader::class)->constructor($appRoot.'/templates'),
    TwigEnvironment::class => DI\factory([TwigBuilder::class, 'build']),
    // Doctrine Dbal.
    Connection::class => function (Settings $settings): Connection {
        return DriverManager::getConnection($settings->get('doctrine.connection'));
    },
    // Doctrine EntityManager.
    EntityManager::class => function (Settings $settings): EntityManager {
        $config = ORMSetup::createAttributeMetadataConfiguration(
            $settings->get('doctrine.metadata_dirs'),
            $settings->get('doctrine.dev_mode'),
        );

        $connection = DriverManager::getConnection($settings->get('doctrine.connection'), $config);

        return new EntityManager($connection, $config);
    },
    EntityManagerInterface::class => DI\get(EntityManager::class),
    // Console command application.
    Application::class => function (ConsoleCommandContainer $consoleCommandContainer) {
        $application = new Application();
        foreach ($consoleCommandContainer->getCommands() as $command) {
            $application->add($command);
        }

        return $application;
    },
    // Environment.
    Environment::class => fn () => Environment::from($_ENV['ENVIRONMENT']),
    // Settings.
    Settings::class => DI\factory([Settings::class, 'load']),
    // Strava stuff.
    StravaClientId::class => StravaClientId::fromString($_ENV['STRAVA_CLIENT_ID']),
    StravaClientSecret::class => StravaClientSecret::fromString($_ENV['STRAVA_CLIENT_SECRET']),
    StravaRefreshToken::class => StravaRefreshToken::fromString($_ENV['STRAVA_REFRESH_TOKEN']),
    // File system.
    FilesystemOperator::class => DI\create(Filesystem::class)->constructor(new LocalFilesystemAdapter(
        Settings::getAppRoot()
    )),
];
