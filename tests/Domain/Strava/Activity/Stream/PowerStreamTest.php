<?php

namespace App\Tests\Domain\Strava\Activity\Stream;

use App\Domain\Strava\Activity\Stream\PowerStream;
use PHPUnit\Framework\TestCase;

class PowerStreamTest extends TestCase
{
    public function testGetters(): void
    {
        $defaultStream = DefaultStreamBuilder::fromDefaults()->build();
        $powerStream = PowerStream::fromStream($defaultStream);

        $this->assertEquals(
            $defaultStream->getName(),
            $powerStream->getName(),
        );

        $this->assertEquals(
            $defaultStream->getCreatedOn(),
            $powerStream->getCreatedOn(),
        );

        $this->assertEquals(
            $defaultStream->getStreamType(),
            $powerStream->getStreamType(),
        );

        $this->assertEquals(
            $defaultStream->getData(),
            $powerStream->getData(),
        );
    }
}
