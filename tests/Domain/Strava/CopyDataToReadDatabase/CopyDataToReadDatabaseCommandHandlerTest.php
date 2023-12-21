<?php

namespace App\Tests\Domain\Strava\CopyDataToReadDatabase;

use App\Domain\Strava\CopyDataToReadDatabase\CopyDataToReadDatabase;
use App\Domain\Strava\CopyDataToReadDatabase\CopyDataToReadDatabaseCommandHandler;
use App\Domain\Strava\StravaYears;
use App\Infrastructure\CQRS\CommandHandler\CommandHandler;
use App\Infrastructure\Doctrine\Connection\ConnectionFactory;
use App\Infrastructure\Environment\Settings;
use App\Infrastructure\ValueObject\Time\Year;
use App\Infrastructure\ValueObject\Time\YearCollection;
use App\Tests\CommandHandlerTestCase;
use App\Tests\SpyOutput;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\MockObject\MockObject;
use Spatie\Snapshots\MatchesSnapshots;

class CopyDataToReadDatabaseCommandHandlerTest extends CommandHandlerTestCase
{
    use MatchesSnapshots;

    private CopyDataToReadDatabaseCommandHandler $copyDataToReadDatabaseCommandHandler;
    private MockObject $readOnlyConnection;
    private MockObject $stravaYears;

    public function testHandle(): void
    {
        $output = new SpyOutput();

        static $snapshots = [];
        $this->readOnlyConnection
            ->expects($this->exactly(26))
            ->method('executeStatement')
            ->willReturnCallback(function (string $query) use (&$snapshots) {
                $snapshots[] = $query;
            });

        $this->stravaYears
            ->expects($this->once())
            ->method('getYears')
            ->willReturn(YearCollection::fromArray([Year::fromInt(2023), Year::fromInt(2024)]));

        $this->copyDataToReadDatabaseCommandHandler->handle(new CopyDataToReadDatabase($output));
        $this->assertMatchesJsonSnapshot($snapshots);
        $this->assertMatchesTextSnapshot($output);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $connectionFactory = $this->createMock(ConnectionFactory::class);
        $settings = $this->getContainer()->get(Settings::class);
        $this->stravaYears = $this->createMock(StravaYears::class);
        $this->readOnlyConnection = $this->createMock(Connection::class);

        $connectionFactory
            ->expects($this->once())
            ->method('getReadOnly')
            ->willReturn($this->readOnlyConnection);

        $connectionFactory
            ->expects($this->once())
            ->method('getDefault')
            ->willReturn(DriverManager::getConnection($settings->get('doctrine.connections.default')));

        $this->copyDataToReadDatabaseCommandHandler = new CopyDataToReadDatabaseCommandHandler(
            $connectionFactory,
            $settings,
            $this->stravaYears,
        );
    }

    protected function getCommandHandler(): CommandHandler
    {
        return $this->copyDataToReadDatabaseCommandHandler;
    }
}
