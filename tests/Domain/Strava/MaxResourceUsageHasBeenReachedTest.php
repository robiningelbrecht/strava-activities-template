<?php

namespace App\Tests\Domain\Strava;

use App\Domain\Strava\MaxResourceUsageHasBeenReached;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MaxResourceUsageHasBeenReachedTest extends TestCase
{
    private MaxResourceUsageHasBeenReached $maxResourceUsageHasBeenReached;
    private MockObject $filesystem;

    public function testClear(): void
    {
        $this->filesystem
            ->expects($this->once())
            ->method('delete')
            ->with('MAX_RESOURCE_USAGE_REACHED');

        $this->maxResourceUsageHasBeenReached->clear();
    }

    public function testMarkAsReached(): void
    {
        $this->filesystem
            ->expects($this->once())
            ->method('write')
            ->with('MAX_RESOURCE_USAGE_REACHED', '');

        $this->maxResourceUsageHasBeenReached->markAsReached();
    }

    public function testHasReached(): void
    {
        $this->filesystem
            ->expects($this->once())
            ->method('has')
            ->willReturn(true);

        $this->maxResourceUsageHasBeenReached->hasReached();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = $this->createMock(FilesystemOperator::class);
        $this->maxResourceUsageHasBeenReached = new MaxResourceUsageHasBeenReached(
            $this->filesystem
        );
    }
}
