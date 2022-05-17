<?php

namespace Vancado\VncCancelForm\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CleanFolderCommand extends Command
{
    /**
     * Configure the command by defining the name, options and arguments
     */
    protected function configure()
    {
        $this->setDescription('Delete all files in folder older then offset set in seconds')
            ->addArgument(
                'folder',
                InputArgument::OPTIONAL,
                'folder name',
            )
            ->addArgument(
                'offset',
                InputArgument::OPTIONAL,
                'Offset in seconds',
                3600
            );
    }


    public function execute(InputInterface $input, OutputInterface $output)
    {
        $targetFolder = $input->getArgument('folder');
        $offset = $input->getArgument('offset');

        $timestamp = $GLOBALS['EXEC_TIME'] - $offset;

        $recyclerFolders = [];
        $storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
        // takes only _recycler_ folder on the first level into account
        foreach ($storageRepository->findAll() as $storage) {
            $rootLevelFolder = $storage->getRootLevelFolder(false);
            foreach ($rootLevelFolder->getSubfolders() as $subFolder) {
                if ($subFolder->getName() === $targetFolder) {
                    $this->cleanupFiles($subFolder, $timestamp);
                }
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Gets a list of all files in a directory recursively and removes
     * old ones.
     *
     * @param Folder $folder the folder
     * @param int $timestamp Timestamp of the last file modification
     */
    protected function cleanupFiles(Folder $folder, $timestamp)
    {
        foreach ($folder->getFiles() as $file) {
            if ($timestamp > $file->getModificationTime()) {
                $file->delete();
            }
        }
        foreach ($folder->getSubfolders() as $subFolder) {
            $this->cleanupFiles($subFolder, $timestamp);
            // if no more files and subdirectories are in the folder, remove the folder as well
            if ($subFolder->getFileCount() === 0 && count($subFolder->getSubfolders()) === 0) {
                $subFolder->delete(true);
            }
        }
    }
}
