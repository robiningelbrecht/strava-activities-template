<?php

namespace App\Domain\Strava\Activity\Image;

use App\Domain\Strava\Activity\StravaActivityRepository;

final readonly class ActivityBasedImageRepository
{
    public function __construct(
        private StravaActivityRepository $stravaActivityRepository
    ) {
    }

    /**
     * @return \App\Domain\Strava\Activity\Image\Image[]
     */
    public function findAll(): array
    {
        $images = [];
        $activities = $this->stravaActivityRepository->findAll();
        foreach ($activities as $activity) {
            if (0 === $activity->getTotalImageCount()) {
                continue;
            }
            $images = [
                ...$images,
                ...array_map(
                    fn (string $path) => Image::create(
                        gitHubImageLocation: $path,
                        activity: $activity
                    ),
                    $activity->getRemoteImagePaths()
                ),
            ];
        }

        return $images;
    }
}
