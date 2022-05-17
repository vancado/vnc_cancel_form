<?php


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ClearFolderCommand extends Command
{
    /**
     * Configure the command by defining the name, options and arguments
     */
    protected function configure()
    {
        $this
            ->setDescription('Delete all files in folder older then offset set in seconds')
            ->addArgument(
                'folder',
                InputArgument::REQUIRED,
                'folder name',
            )
            ->addArgument(
                'offset',
                InputArgument::REQUIRED,
                'Offset in seconds',
                3600
            );
    }

    // use Symfony\Component\Console\Input\InputInterface;
    // use Symfony\Component\Console\Output\OutputInterface;

    protected function execute(InputInterface $input, OutputInterface $output)
    {

    }
}
