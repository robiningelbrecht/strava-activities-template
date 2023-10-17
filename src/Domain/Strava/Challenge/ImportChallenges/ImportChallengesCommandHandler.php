<?php

namespace App\Domain\Strava\Challenge\ImportChallenges;

use App\Domain\Strava\Challenge\Challenge;
use App\Domain\Strava\Challenge\StravaChallengeRepository;
use App\Domain\Strava\Strava;
use App\Infrastructure\Attribute\AsCommandHandler;
use App\Infrastructure\CQRS\CommandHandler\CommandHandler;
use App\Infrastructure\CQRS\DomainCommand;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Lcobucci\Clock\Clock;
use League\Flysystem\FilesystemOperator;
use Ramsey\Uuid\Rfc4122\UuidV5;

#[AsCommandHandler]
final readonly class ImportChallengesCommandHandler implements CommandHandler
{
    public function __construct(
        private Strava $strava,
        private StravaChallengeRepository $stravaChallengeRepository,
        private FilesystemOperator $filesystem,
        private Clock $clock
    ) {
    }

    public function handle(DomainCommand $command): void
    {
        assert($command instanceof ImportChallenges);
        $command->getOutput()->writeln('Importing challenges...');

        try {
            $challenges = $this->strava->getChallenges();
        } catch (\Throwable) {
            $command->getOutput()->writeln('Could not import challenges...');

            return;
        }

        foreach ($challenges as $challengeData) {
            try {
                $this->stravaChallengeRepository->find($challengeData['challenge_id']);
            } catch (EntityNotFound) {
                $challenge = Challenge::create(
                    challengeId: $challengeData['challenge_id'],
                    createdOn: SerializableDateTime::fromDateTimeImmutable($this->clock->now()),
                    data: $challengeData,
                );
                if ($url = $challenge->getLogoUrl()) {
                    $imagePath = sprintf('files/challenges/%s.png', UuidV5::uuid1());
                    $this->filesystem->write(
                        $imagePath,
                        $this->strava->downloadImage($url)
                    );

                    $challenge->updateLocalLogo($imagePath);
                }
                $this->stravaChallengeRepository->add($challenge);
                $command->getOutput()->writeln(sprintf('  => Imported challenge "%s"', $challenge->getName()));
                sleep(1); // Make sure timestamp is increased by at least one.
            }
        }
    }
}
