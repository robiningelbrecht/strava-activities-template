<?php

namespace App\Tests\Domain\Strava\Segment\SegmentEffort;

use App\Domain\Strava\Segment\SegmentEffort\SegmentEffortCollection;
use PHPUnit\Framework\TestCase;

class SegmentEffortCollectionTest extends TestCase
{
    public function testGetBestEffort(): void
    {
        $bestEffort = SegmentEffortBuilder::fromDefaults()
            ->withData([
                'elapsed_time' => 5.3,
                'average_watts' => 200,
                'distance' => 100,
            ])
            ->build();
        $collection = SegmentEffortCollection::fromArray([
            SegmentEffortBuilder::fromDefaults()
                ->withData([
                    'elapsed_time' => 9.3,
                    'average_watts' => 200,
                    'distance' => 100,
                ])
                ->build(),
            $bestEffort,
        ]);

        $this->assertEquals(
            $bestEffort,
            $collection->getBestEffort()
        );
    }
}
