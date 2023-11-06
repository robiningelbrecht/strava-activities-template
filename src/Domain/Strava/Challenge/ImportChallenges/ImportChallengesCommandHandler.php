<?php

namespace App\Domain\Strava\Challenge\ImportChallenges;

use App\Domain\Strava\Challenge\Challenge;
use App\Domain\Strava\Challenge\ChallengeRepository;
use App\Domain\Strava\Strava;
use App\Infrastructure\Attribute\AsCommandHandler;
use App\Infrastructure\CQRS\CommandHandler\CommandHandler;
use App\Infrastructure\CQRS\DomainCommand;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Time\Sleep;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Infrastructure\ValueObject\UuidFactory;
use Lcobucci\Clock\Clock;
use League\Flysystem\FilesystemOperator;

#[AsCommandHandler]
final readonly class ImportChallengesCommandHandler implements CommandHandler
{
    private const DEFAULT_STRAVA_CHALLENGE_HISTORY = '<!-- OVERRIDE ME WITH HTML COPY/PASTED FROM https://www.strava.com/athletes/[YOUR_ATHLETE_ID]/trophy-case -->';

    public function __construct(
        private Strava $strava,
        private ChallengeRepository $challengeRepository,
        private FilesystemOperator $filesystem,
        private Clock $clock,
        private UuidFactory $uuidFactory,
        private Sleep $sleep
    ) {
    }

    public function handle(DomainCommand $command): void
    {
        assert($command instanceof ImportChallenges);
        $command->getOutput()->writeln('Importing challenges...');

        if (!$this->filesystem->fileExists('files/strava-challenge-history.html')) {
            $this->filesystem->write(
                location: 'files/strava-challenge-history.html',
                contents: self::DEFAULT_STRAVA_CHALLENGE_HISTORY
            );
        }

        $challenges = [];
        try {
            $challenges = $this->strava->getChallengesOnPublicProfile();
        } catch (\Throwable) {
            $command->getOutput()->writeln('Could not import challenges from public profile...');
        }
        try {
            $challenges = [...$challenges, ...$this->strava->getChallengesOnTrophyCase()];
        } catch (\Throwable) {
            $command->getOutput()->writeln('Could not import challenges from trophy case page...');
        }

        if (empty($challenges)) {
            $command->getOutput()->writeln('No Challenges to import...');

            return;
        }

        foreach ($challenges as $stravaChallenge) {
            try {
                $this->challengeRepository->find($stravaChallenge['challenge_id']);
            } catch (EntityNotFound) {
                $challenge = Challenge::create(
                    challengeId: $stravaChallenge['challenge_id'],
                    createdOn: $stravaChallenge['completedOn'] ?? SerializableDateTime::fromDateTimeImmutable($this->clock->now()),
                    data: $stravaChallenge,
                );
                if ($url = $challenge->getLogoUrl()) {
                    $imagePath = sprintf('files/challenges/%s.png', $this->uuidFactory->random());
                    $this->filesystem->write(
                        $imagePath,
                        $this->strava->downloadImage($url)
                    );

                    $challenge->updateLocalLogo($imagePath);
                }
                $this->challengeRepository->add($challenge);
                $command->getOutput()->writeln(sprintf('  => Imported challenge "%s"', $challenge->getName()));
                $this->sleep->sweetDreams(1); // Make sure timestamp is increased by at least one.
            }
        }
    }
}
