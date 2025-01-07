<?php

namespace App\Domain\Strava\BuildReadMe;

use App\Infrastructure\Attribute\AsCommandHandler;
use App\Infrastructure\CQRS\CommandHandler\CommandHandler;
use App\Infrastructure\CQRS\DomainCommand;
use League\Flysystem\FilesystemOperator;
use Twig\Environment;

#[AsCommandHandler]
final readonly class BuildReadMeCommandHandler implements CommandHandler
{
    public function __construct(
        private Environment $twig,
        private FilesystemOperator $filesystem,
    ) {
    }

    public function handle(DomainCommand $command): void
    {
        assert($command instanceof BuildReadMe);

        $this->filesystem->write('README.md', $this->twig->load('markdown/readme.html.twig')->render());
    }
}
