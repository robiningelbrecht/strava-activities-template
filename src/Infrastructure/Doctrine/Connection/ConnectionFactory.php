<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Connection;

use App\Infrastructure\Environment\Settings;
use App\Infrastructure\ValueObject\Time\Year;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

class ConnectionFactory
{
    public function __construct(
        private readonly Settings $settings
    ) {
    }

    public function getForYear(Year $year): Connection
    {
        $connection = $this->settings->get('doctrine.connections.year_based');

        $connection['path'] = str_replace('%YEAR%', (string) $year, $connection['path']);

        return DriverManager::getConnection($connection);
    }

    public function getDefault(): Connection
    {
        $connection = $this->settings->get('doctrine.connections.default');

        return DriverManager::getConnection($connection);
    }

    public function getReadOnly(): Connection
    {
        $connection = $this->settings->get('doctrine.connections.read');

        return DriverManager::getConnection($connection);
    }
}
