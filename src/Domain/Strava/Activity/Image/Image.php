<?php

namespace App\Domain\Strava\Activity\Image;

use App\Domain\Strava\Activity\Activity;

final readonly class Image
{
    private function __construct(
        private string $gitHubImageLocation,
        private Activity $activity,
    ) {
    }

    public static function create(
        string $gitHubImageLocation,
        Activity $activity
    ): self {
        return new self(
            gitHubImageLocation: $gitHubImageLocation,
            activity: $activity
        );
    }

    public function getGitHubImageUrl(): string
    {
        return $this->gitHubImageLocation;
    }

    public function getActivity(): Activity
    {
        return $this->activity;
    }
}
