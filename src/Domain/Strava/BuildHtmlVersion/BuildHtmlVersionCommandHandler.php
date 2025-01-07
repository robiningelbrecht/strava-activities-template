<?php

namespace App\Domain\Strava\BuildHtmlVersion;

use App\Infrastructure\Attribute\AsCommandHandler;
use App\Infrastructure\CQRS\CommandHandler\CommandHandler;
use App\Infrastructure\CQRS\DomainCommand;
use League\Flysystem\FilesystemOperator;
use Twig\Environment;

#[AsCommandHandler]
final readonly class BuildHtmlVersionCommandHandler implements CommandHandler
{
    public function __construct(
        private Environment $twig,
        private FilesystemOperator $filesystem,
    ) {
    }

    public function handle(DomainCommand $command): void
    {
        assert($command instanceof BuildHtmlVersion);

        $command->getOutput()->writeln('  => Building index.html');
        $this->filesystem->write(
            'build/html/index.html',
            $this->twig->load('html/index.html.twig')->render(),
        );
    }
}
