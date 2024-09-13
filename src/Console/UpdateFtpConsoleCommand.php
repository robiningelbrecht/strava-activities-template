<?php

declare(strict_types=1);

namespace App\Console;

use App\Domain\Strava\Ftp\Ftp;
use App\Domain\Strava\Ftp\FtpValue;
use App\Domain\Strava\Ftp\WriteModel\FtpRepository;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:strava:update-ftp', description: 'Update FTP')]
final class UpdateFtpConsoleCommand extends Command
{
    public function __construct(
        private readonly FtpRepository $ftpRepository,
    ) {
        parent::__construct();
    }

    public function configure(): void
    {
        parent::configure();

        $this->addArgument('setOn', InputArgument::REQUIRED);
        $this->addArgument('ftp', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $setOn = SerializableDateTime::fromString($input->getArgument('setOn'));
        $ftp = FtpValue::fromInt((int) $input->getArgument('ftp'));

        $this->ftpRepository->save(Ftp::fromState(
            setOn: $setOn,
            ftp: $ftp
        ));

        $output->writeln(sprintf('FTP "%s" set on "%s"', $ftp->getValue(), $setOn->format('d-m-Y')));

        return Command::SUCCESS;
    }
}
