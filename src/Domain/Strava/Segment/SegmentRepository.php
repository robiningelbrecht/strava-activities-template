<?php

declare(strict_types=1);

namespace App\Domain\Strava\Segment;

interface SegmentRepository
{
    public function find(int $id): Segment;

    public function findAll(): SegmentCollection;

    public function add(Segment $segment): void;
}
