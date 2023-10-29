<?php

namespace App\Console;

use App\Domain\Strava\Activity\BuildActivityHeatmapChart\BuildActivityHeatmapChart;
use App\Domain\Strava\Activity\BuildDaytimeStatsChart\BuildDaytimeStatsChart;
use App\Domain\Strava\Activity\BuildEddingtonChart\BuildEddingtonChart;
use App\Domain\Strava\Activity\BuildLatestStravaActivities\BuildLatestStravaActivities;
use App\Domain\Strava\Activity\BuildWeekdayStatsChart\BuildWeekdayStatsChart;
use App\Domain\Strava\Activity\BuildWeeklyDistanceChart\BuildWeeklyDistanceChart;
use App\Domain\Strava\BuildHtmlVersion\BuildHtmlVersion;
use App\Domain\Strava\BuildReadMe\BuildReadMe;
use App\Domain\Strava\ReachedStravaApiRateLimits;
use App\Infrastructure\CQRS\CommandBus;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:strava:build-files', description: 'Build Strava files')]
final class BuildStravaActivityFilesConsoleCommand extends Command
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly ReachedStravaApiRateLimits $reachedStravaApiRateLimits
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->reachedStravaApiRateLimits->hasReached()) {
            $output->writeln('Reached Strava API rate limits, cannot build stats yet...');

            return Command::SUCCESS;
        }

        $output->writeln('Building latest activities...');
        $this->commandBus->dispatch(new BuildLatestStravaActivities());
        $output->writeln('Building weekly distance chart...');
        $this->commandBus->dispatch(new BuildWeeklyDistanceChart());
        $output->writeln('Building weekday stats chart...');
        $this->commandBus->dispatch(new BuildWeekdayStatsChart());
        $output->writeln('Building daytime stats chart...');
        $this->commandBus->dispatch(new BuildDaytimeStatsChart());
        $output->writeln('Building activity heatmap chart...');
        $this->commandBus->dispatch(new BuildActivityHeatmapChart());
        $output->writeln('Building Eddington chart...');
        $this->commandBus->dispatch(new BuildEddingtonChart());
        $output->writeln('Building README...');
        $this->commandBus->dispatch(new BuildReadMe());
        $output->writeln('Building HTML...');
        $this->commandBus->dispatch(new BuildHtmlVersion());

        return Command::SUCCESS;
    }
}
