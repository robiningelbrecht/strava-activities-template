<?php

namespace App\Domain\Strava;

use App\Domain\Strava\Activity\Stream\StreamType;
use App\Infrastructure\Serialization\Json;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class Strava
{
    /** @var array<mixed> */
    private static array $cachedAthlete = [];

    public function __construct(
        private readonly Client $client,
        private readonly StravaClientId $stravaClientId,
        private readonly StravaClientSecret $stravaClientSecret,
        private readonly StravaRefreshToken $stravaRefreshToken,
    ) {
    }

    /**
     * @param array<mixed> $options
     */
    private function request(
        string $path,
        string $method = 'GET',
        array $options = []): string
    {
        $options = array_merge([
            'base_uri' => 'https://www.strava.com/',
        ], $options);
        $response = $this->client->request($method, $path, $options);

        return $response->getBody()->getContents();
    }

    private function getAccessToken(): string
    {
        $response = $this->request('oauth/token', 'POST', [
            RequestOptions::FORM_PARAMS => [
                'client_id' => (string) $this->stravaClientId,
                'client_secret' => (string) $this->stravaClientSecret,
                'grant_type' => 'refresh_token',
                'refresh_token' => (string) $this->stravaRefreshToken,
            ],
        ]);

        return Json::decode($response)['access_token'] ?? throw new \RuntimeException('Could not fetch Strava accessToken');
    }

    /**
     * @return array<mixed>
     */
    public function getAthlete(): array
    {
        if (!empty(Strava::$cachedAthlete)) {
            return Strava::$cachedAthlete;
        }

        Strava::$cachedAthlete = Json::decode($this->request('api/v3/athlete', 'GET', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$this->getAccessToken(),
            ],
        ]));

        return Strava::$cachedAthlete;
    }

    /**
     * @return array<mixed>
     */
    public function getActivities(): array
    {
        $allActivities = [];

        $page = 1;
        do {
            $activities = Json::decode($this->request('api/v3/athlete/activities', 'GET', [
                RequestOptions::HEADERS => [
                    'Authorization' => 'Bearer '.$this->getAccessToken(),
                ],
                RequestOptions::QUERY => [
                    'page' => $page,
                    'per_page' => 200,
                ],
            ]));
            $allActivities = array_merge($allActivities, $activities);
            ++$page;
        } while (count($activities) > 0);

        return $allActivities;
    }

    /**
     * @return array<mixed>
     */
    public function getActivity(int $id): array
    {
        return Json::decode($this->request('api/v3/activities/'.$id, 'GET', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$this->getAccessToken(),
            ],
        ]));
    }

    /**
     * @return array<mixed>
     */
    public function getActivityZones(int $id): array
    {
        return Json::decode($this->request('api/v3/activities/'.$id.'/zones', 'GET', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$this->getAccessToken(),
            ],
        ]));
    }

    /**
     * @return array<mixed>
     */
    public function getAllActivityStreams(int $id): array
    {
        return Json::decode($this->request('api/v3/activities/'.$id.'/streams', 'GET', [
            RequestOptions::QUERY => [
                'keys' => implode(',', array_map(fn (StreamType $streamType) => $streamType->value, StreamType::cases())),
            ],
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$this->getAccessToken(),
            ],
        ]));
    }

    /**
     * @return array<mixed>
     */
    public function getActivityPhotos(int $activityId): array
    {
        return Json::decode($this->request('api/v3/activities/'.$activityId.'/photos', 'GET', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$this->getAccessToken(),
            ],
            RequestOptions::QUERY => [
                'size' => 5000,
            ],
        ]));
    }

    /**
     * @return array<mixed>
     */
    public function getGear(string $id): array
    {
        return Json::decode($this->request('api/v3/gear/'.$id, 'GET', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$this->getAccessToken(),
            ],
        ]));
    }

    /**
     * @return array<mixed>
     */
    public function getChallenges(): array
    {
        $athleteId = $this->getAthlete()['id'];
        $contents = $this->request('athletes/'.$athleteId);
        if (!preg_match_all('/<li class="Trophies_listItem[\S]*">(?<matches>[[\s\S]*)<\/li>/U', $contents, $matches)) {
            throw new \RuntimeException('Could not fetch Strava challenges');
        }

        $challenges = [];
        foreach ($matches['matches'] as $match) {
            if (!preg_match('/<h4>(?<match>.*?)<\/h4>/U', $match, $challengeName)) {
                throw new \RuntimeException('Could not fetch Strava challenge name');
            }
            if (!preg_match('/<a href="[\S]*" title="(?<match>.*?)" class="[\S]*"[\s\S]*\/>/U', $match, $teaser)) {
                throw new \RuntimeException('Could not fetch Strava challenge teaser');
            }
            if (!preg_match('/<img src="(?<match>.*?)" alt="[\s\S]*"[\s\S]*\/>/U', $match, $logoUrl)) {
                throw new \RuntimeException('Could not fetch Strava challenge logoUrl');
            }
            if (!preg_match('/<a href="\/challenges\/(?<match>.*?)" title="[\s\S]*"[\s\S]*>/U', $match, $url)) {
                throw new \RuntimeException('Could not fetch Strava challenge url');
            }
            if (!preg_match('/<img src="https[\S]+\/challenges\/(?<match>.*?)\/[\S]+.png" alt="[\s\S]*"[\s\S]*\/>/U', $match, $challengeId)) {
                throw new \RuntimeException('Could not fetch Strava challenge challengeId');
            }

            $challenges[] = [
                'name' => $challengeName['match'],
                'teaser' => $teaser['match'],
                'logo_url' => $logoUrl['match'],
                'url' => $url['match'],
                'challenge_id' => $challengeId['match'],
            ];
        }

        return $challenges;
    }

    public function downloadImage(string $uri): string
    {
        $response = $this->client->request('GET', $uri);

        return $response->getBody()->getContents();
    }
}
