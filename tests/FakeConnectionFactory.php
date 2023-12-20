<?php

declare(strict_types=1);

namespace App\Tests;

use App\Infrastructure\Doctrine\Connection\ConnectionFactory;
use App\Infrastructure\Environment\Settings;
use App\Infrastructure\ValueObject\Time\Year;
use Doctrine\DBAL\Connection;

class FakeConnectionFactory extends ConnectionFactory
{
    public function __construct()
    {
        parent::__construct(Settings::load());
    }

    public function getForYear(Year $year): Connection
    {
        return parent::getDefault();
    }

    public function getDefault(): Connection
    {
        return parent::getDefault();
    }

    public function getReadOnly(): Connection
    {
        return parent::getDefault();
    }
}
