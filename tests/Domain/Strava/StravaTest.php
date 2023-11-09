<?php

namespace App\Tests\Domain\Strava;

use App\Domain\Strava\Challenge\ImportChallenges\ImportChallengesCommandHandler;
use App\Domain\Strava\Strava;
use App\Domain\Strava\StravaClientId;
use App\Domain\Strava\StravaClientSecret;
use App\Domain\Strava\StravaRefreshToken;
use App\Infrastructure\Serialization\Json;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

class StravaTest extends TestCase
{
    use MatchesSnapshots;

    private Strava $strava;

    private MockObject $client;
    private StravaClientId $stravaClientId;
    private StravaClientSecret $stravaClientSecret;
    private StravaRefreshToken $stravaRefreshToken;

    public function testGetAthlete(): void
    {
        $matcher = $this->exactly(2);
        $this->client
            ->expects($matcher)
            ->method('request')
            ->willReturnCallback(function (string $method, string $path, array $options) use ($matcher) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertEquals('POST', $method);
                    $this->assertEquals('oauth/token', $path);
                    $this->assertMatchesJsonSnapshot($options);

                    return new Response(200, [], Json::encode(['access_token' => 'theAccessToken']));
                }

                $this->assertEquals('GET', $method);
                $this->assertEquals('api/v3/athlete', $path);
                $this->assertMatchesJsonSnapshot($options);

                return new Response(200, [], Json::encode(['weight' => 68, 'id' => 10]));
            });

        $this->strava->getAthlete();
        // Test static cache.
        $this->strava->getAthlete();
    }

    public function testGetActivities(): void
    {
        $matcher = $this->exactly(2);
        $this->client
            ->expects($matcher)
            ->method('request')
            ->willReturnCallback(function (string $method, string $path, array $options) use ($matcher) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertEquals('POST', $method);
                    $this->assertEquals('oauth/token', $path);
                    $this->assertMatchesJsonSnapshot($options);

                    return new Response(200, [], Json::encode(['access_token' => 'theAccessToken']));
                }

                $this->assertEquals('GET', $method);
                $this->assertEquals('api/v3/athlete/activities', $path);
                $this->assertMatchesJsonSnapshot($options);

                return new Response(200, [], Json::encode([]));
            });

        $this->strava->getActivities();
    }

    public function testGetActivity(): void
    {
        $matcher = $this->exactly(2);
        $this->client
            ->expects($matcher)
            ->method('request')
            ->willReturnCallback(function (string $method, string $path, array $options) use ($matcher) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertEquals('POST', $method);
                    $this->assertEquals('oauth/token', $path);
                    $this->assertMatchesJsonSnapshot($options);

                    return new Response(200, [], Json::encode(['access_token' => 'theAccessToken']));
                }

                $this->assertEquals('GET', $method);
                $this->assertEquals('api/v3/activities/3', $path);
                $this->assertMatchesJsonSnapshot($options);

                return new Response(200, [], Json::encode([]));
            });

        $this->strava->getActivity(3);
    }

    public function testGetActivityZones(): void
    {
        $matcher = $this->exactly(2);
        $this->client
            ->expects($matcher)
            ->method('request')
            ->willReturnCallback(function (string $method, string $path, array $options) use ($matcher) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertEquals('POST', $method);
                    $this->assertEquals('oauth/token', $path);
                    $this->assertMatchesJsonSnapshot($options);

                    return new Response(200, [], Json::encode(['access_token' => 'theAccessToken']));
                }

                $this->assertEquals('GET', $method);
                $this->assertEquals('api/v3/activities/3/zones', $path);
                $this->assertMatchesJsonSnapshot($options);

                return new Response(200, [], Json::encode([]));
            });

        $this->strava->getActivityZones(3);
    }

    public function testGetAllActivityStreams(): void
    {
        $matcher = $this->exactly(2);
        $this->client
            ->expects($matcher)
            ->method('request')
            ->willReturnCallback(function (string $method, string $path, array $options) use ($matcher) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertEquals('POST', $method);
                    $this->assertEquals('oauth/token', $path);
                    $this->assertMatchesJsonSnapshot($options);

                    return new Response(200, [], Json::encode(['access_token' => 'theAccessToken']));
                }

                $this->assertEquals('GET', $method);
                $this->assertEquals('api/v3/activities/3/streams', $path);
                $this->assertMatchesJsonSnapshot($options);

                return new Response(200, [], Json::encode([]));
            });

        $this->strava->getAllActivityStreams(3);
    }

    public function testGetAllActivityPhotos(): void
    {
        $matcher = $this->exactly(2);
        $this->client
            ->expects($matcher)
            ->method('request')
            ->willReturnCallback(function (string $method, string $path, array $options) use ($matcher) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertEquals('POST', $method);
                    $this->assertEquals('oauth/token', $path);
                    $this->assertMatchesJsonSnapshot($options);

                    return new Response(200, [], Json::encode(['access_token' => 'theAccessToken']));
                }

                $this->assertEquals('GET', $method);
                $this->assertEquals('api/v3/activities/3/photos', $path);
                $this->assertMatchesJsonSnapshot($options);

                return new Response(200, [], Json::encode([]));
            });

        $this->strava->getActivityPhotos(3);
    }

    public function testGetGear(): void
    {
        $matcher = $this->exactly(2);
        $this->client
            ->expects($matcher)
            ->method('request')
            ->willReturnCallback(function (string $method, string $path, array $options) use ($matcher) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertEquals('POST', $method);
                    $this->assertEquals('oauth/token', $path);
                    $this->assertMatchesJsonSnapshot($options);

                    return new Response(200, [], Json::encode(['access_token' => 'theAccessToken']));
                }

                $this->assertEquals('GET', $method);
                $this->assertEquals('api/v3/gear/3', $path);
                $this->assertMatchesJsonSnapshot($options);

                return new Response(200, [], Json::encode([]));
            });

        $this->strava->getGear(3);
    }

    public function testGetChallengesOnPublicProfile(): void
    {
        $this->client
            ->expects($this->once())
            ->method('request')
            ->willReturnCallback(function (string $method, string $path, array $options) {
                $this->assertEquals('GET', $method);
                $this->assertEquals('athletes/10', $path);

                return new Response(200, [], file_get_contents(__DIR__.'/public-profile.html'));
            });

        $challenges = $this->strava->getChallengesOnPublicProfile();
        $this->assertMatchesJsonSnapshot($challenges);
    }

    public function testGetChallengesOnPublicProfileWhenInvalidProfile(): void
    {
        $this->client
            ->expects($this->once())
            ->method('request')
            ->willReturnCallback(function (string $method, string $path, array $options) {
                $this->assertEquals('GET', $method);
                $this->assertEquals('athletes/10', $path);

                return new Response(200, [], '');
            });

        $this->expectExceptionObject(new \RuntimeException('Could not fetch Strava challenges on public profile'));

        $this->strava->getChallengesOnPublicProfile();
    }

    public function testGetChallengesOnPublicProfileWhenNameNotFound(): void
    {
        $this->client
            ->expects($this->once())
            ->method('request')
            ->willReturnCallback(function (string $method, string $path, array $options) {
                $this->assertEquals('GET', $method);
                $this->assertEquals('athletes/10', $path);

                return new Response(200, [], file_get_contents(__DIR__.'/public-profile-without-name.html'));
            });

        $this->expectExceptionObject(new \RuntimeException('Could not fetch Strava challenge name'));

        $this->strava->getChallengesOnPublicProfile();
    }

    public function testGetChallengesOnPublicProfileWhenTeaserNotFound(): void
    {
        $this->client
            ->expects($this->once())
            ->method('request')
            ->willReturnCallback(function (string $method, string $path, array $options) {
                $this->assertEquals('GET', $method);
                $this->assertEquals('athletes/10', $path);

                return new Response(200, [], file_get_contents(__DIR__.'/public-profile-without-teaser.html'));
            });

        $this->expectExceptionObject(new \RuntimeException('Could not fetch Strava challenge teaser'));

        $this->strava->getChallengesOnPublicProfile();
    }

    public function testGetChallengesOnPublicProfileWhenLogoNotFound(): void
    {
        $this->client
            ->expects($this->once())
            ->method('request')
            ->willReturnCallback(function (string $method, string $path, array $options) {
                $this->assertEquals('GET', $method);
                $this->assertEquals('athletes/10', $path);

                return new Response(200, [], file_get_contents(__DIR__.'/public-profile-without-logo.html'));
            });

        $this->expectExceptionObject(new \RuntimeException('Could not fetch Strava challenge logoUrl'));

        $this->strava->getChallengesOnPublicProfile();
    }

    public function testGetChallengesOnPublicProfileWhenUrlNotFound(): void
    {
        $this->client
            ->expects($this->once())
            ->method('request')
            ->willReturnCallback(function (string $method, string $path, array $options) {
                $this->assertEquals('GET', $method);
                $this->assertEquals('athletes/10', $path);

                return new Response(200, [], file_get_contents(__DIR__.'/public-profile-without-url.html'));
            });

        $this->expectExceptionObject(new \RuntimeException('Could not fetch Strava challenge url'));

        $this->strava->getChallengesOnPublicProfile();
    }

    public function testGetChallengesOnPublicProfileWhenIdNotFound(): void
    {
        $this->client
            ->expects($this->once())
            ->method('request')
            ->willReturnCallback(function (string $method, string $path, array $options) {
                $this->assertEquals('GET', $method);
                $this->assertEquals('athletes/10', $path);

                return new Response(200, [], file_get_contents(__DIR__.'/public-profile-without-id.html'));
            });

        $this->expectExceptionObject(new \RuntimeException('Could not fetch Strava challenge challengeId'));

        $this->strava->getChallengesOnPublicProfile();
    }

    public function testGetChallengesOnTrophyCase(): void
    {
        $this->client
            ->expects($this->once())
            ->method('request')
            ->willReturnCallback(function (string $method, string $path, array $options) {
                $this->assertEquals('GET', $method);
                $this->assertEquals('https://raw.githubusercontent.com/robiningelbrecht/strava-activities/master/files/strava-challenge-history.html', $path);

                return new Response(200, [], file_get_contents(__DIR__.'/trophy-case.html'));
            });

        $challenges = $this->strava->getChallengesOnTrophyCase();
        $this->assertMatchesJsonSnapshot($challenges);
    }

    public function testGetChallengesOnTrophyCaseWithDefaultHtml(): void
    {
        $this->client
            ->expects($this->once())
            ->method('request')
            ->willReturnCallback(function (string $method, string $path, array $options) {
                $this->assertEquals('GET', $method);
                $this->assertEquals('https://raw.githubusercontent.com/robiningelbrecht/strava-activities/master/files/strava-challenge-history.html', $path);

                return new Response(200, [], ImportChallengesCommandHandler::DEFAULT_STRAVA_CHALLENGE_HISTORY);
            });

        $challenges = $this->strava->getChallengesOnTrophyCase();
        $this->assertEmpty($challenges);
    }

    public function testGetChallengesOnTrophyCaseWhenInvalidHtml(): void
    {
        $this->client
            ->expects($this->once())
            ->method('request')
            ->willReturnCallback(function (string $method, string $path, array $options) {
                $this->assertEquals('GET', $method);
                $this->assertEquals('https://raw.githubusercontent.com/robiningelbrecht/strava-activities/master/files/strava-challenge-history.html', $path);

                return new Response(200, [], '');
            });

        $this->expectExceptionObject(new \RuntimeException('Could not fetch Strava challenges from trophy case'));

        $this->strava->getChallengesOnTrophyCase();
    }

    public function testGetChallengesOnTrophyCaseWhenInvalidHtmlCaseTwo(): void
    {
        $this->client
            ->expects($this->once())
            ->method('request')
            ->willReturnCallback(function (string $method, string $path, array $options) {
                $this->assertEquals('GET', $method);
                $this->assertEquals('https://raw.githubusercontent.com/robiningelbrecht/strava-activities/master/files/strava-challenge-history.html', $path);

                return new Response(200, [], "<ul class='list-block-grid list-trophies'>YEAHBABY</ul>");
            });

        $this->expectExceptionObject(new \RuntimeException('Could not fetch Strava challenges from trophy case'));

        $this->strava->getChallengesOnTrophyCase();
    }

    public function testGetChallengesOnTrophyCaseWhenNameNotFound(): void
    {
        $this->client
            ->expects($this->once())
            ->method('request')
            ->willReturnCallback(function (string $method, string $path, array $options) {
                $this->assertEquals('GET', $method);
                $this->assertEquals('https://raw.githubusercontent.com/robiningelbrecht/strava-activities/master/files/strava-challenge-history.html', $path);

                return new Response(200, [], file_get_contents(__DIR__.'/trophy-case-without-name.html'));
            });

        $this->expectExceptionObject(new \RuntimeException('Could not fetch Strava challenge name'));

        $this->strava->getChallengesOnTrophyCase();
    }

    public function testGetChallengesOnTrophyCaseWhenTeaserNotFound(): void
    {
        $this->client
            ->expects($this->once())
            ->method('request')
            ->willReturnCallback(function (string $method, string $path, array $options) {
                $this->assertEquals('GET', $method);
                $this->assertEquals('https://raw.githubusercontent.com/robiningelbrecht/strava-activities/master/files/strava-challenge-history.html', $path);

                return new Response(200, [], file_get_contents(__DIR__.'/trophy-case-without-teaser.html'));
            });

        $this->expectExceptionObject(new \RuntimeException('Could not fetch Strava challenge teaser'));

        $this->strava->getChallengesOnTrophyCase();
    }

    public function testGetChallengesOnTrophyCaseWhenLogoNotFound(): void
    {
        $this->client
            ->expects($this->once())
            ->method('request')
            ->willReturnCallback(function (string $method, string $path, array $options) {
                $this->assertEquals('GET', $method);
                $this->assertEquals('https://raw.githubusercontent.com/robiningelbrecht/strava-activities/master/files/strava-challenge-history.html', $path);

                return new Response(200, [], file_get_contents(__DIR__.'/trophy-case-without-logo.html'));
            });

        $this->expectExceptionObject(new \RuntimeException('Could not fetch Strava challenge logoUrl'));

        $this->strava->getChallengesOnTrophyCase();
    }

    public function testGetChallengesOnTrophyCaseWhenUrlNotFound(): void
    {
        $this->client
            ->expects($this->once())
            ->method('request')
            ->willReturnCallback(function (string $method, string $path, array $options) {
                $this->assertEquals('GET', $method);
                $this->assertEquals('https://raw.githubusercontent.com/robiningelbrecht/strava-activities/master/files/strava-challenge-history.html', $path);

                return new Response(200, [], file_get_contents(__DIR__.'/trophy-case-without-url.html'));
            });

        $this->expectExceptionObject(new \RuntimeException('Could not fetch Strava challenge url'));

        $this->strava->getChallengesOnTrophyCase();
    }

    public function testGetChallengesOnTrophyCaseWhenIdNotFound(): void
    {
        $this->client
            ->expects($this->once())
            ->method('request')
            ->willReturnCallback(function (string $method, string $path, array $options) {
                $this->assertEquals('GET', $method);
                $this->assertEquals('https://raw.githubusercontent.com/robiningelbrecht/strava-activities/master/files/strava-challenge-history.html', $path);

                return new Response(200, [], file_get_contents(__DIR__.'/trophy-case-without-id.html'));
            });

        $this->expectExceptionObject(new \RuntimeException('Could not fetch Strava challenge challengeId'));

        $this->strava->getChallengesOnTrophyCase();
    }

    public function testGetChallengesOnTrophyCaseWhenTimestampNotFound(): void
    {
        $this->client
            ->expects($this->once())
            ->method('request')
            ->willReturnCallback(function (string $method, string $path, array $options) {
                $this->assertEquals('GET', $method);
                $this->assertEquals('https://raw.githubusercontent.com/robiningelbrecht/strava-activities/master/files/strava-challenge-history.html', $path);

                return new Response(200, [], file_get_contents(__DIR__.'/trophy-case-without-timestamp.html'));
            });

        $this->expectExceptionObject(new \RuntimeException('Could not fetch Strava challenge timestamp'));

        $this->strava->getChallengesOnTrophyCase();
    }

    public function testGetChallengesOnTrophyCaseWithEmptyTimestamp(): void
    {
        $this->client
            ->expects($this->once())
            ->method('request')
            ->willReturnCallback(function (string $method, string $path, array $options) {
                $this->assertEquals('GET', $method);
                $this->assertEquals('https://raw.githubusercontent.com/robiningelbrecht/strava-activities/master/files/strava-challenge-history.html', $path);

                return new Response(200, [], file_get_contents(__DIR__.'/trophy-case-with-empty-timestamp.html'));
            });

        $this->expectExceptionObject(new \RuntimeException('Could not fetch Strava challenge timestamp'));

        $this->strava->getChallengesOnTrophyCase();
    }

    public function testDownloadImage(): void
    {
        $this->client
            ->expects($this->once())
            ->method('request')
            ->willReturnCallback(function (string $method, string $path, array $options) {
                $this->assertEquals('GET', $method);
                $this->assertEquals('uri', $path);

                return new Response(200, [], '');
            });

        $this->strava->downloadImage('uri');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = $this->createMock(Client::class);
        $this->stravaClientId = StravaClientId::fromString('clientId');
        $this->stravaClientSecret = StravaClientSecret::fromString('clientSecret');
        $this->stravaRefreshToken = StravaRefreshToken::fromString('refreshToken');

        $this->strava = new Strava(
            client: $this->client,
            stravaClientId: $this->stravaClientId,
            stravaClientSecret: $this->stravaClientSecret,
            stravaRefreshToken: $this->stravaRefreshToken
        );
    }
}
