<?php

namespace Cobweb\ExternalImport\Command;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Cobweb\ExternalImport\Domain\Repository\ConfigurationRepository;
use Cobweb\ExternalImport\Importer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Runs the External Import process from the command line.
 *
 * @package Cobweb\ExternalImport\Command
 */
class ImportCommand extends Command
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var ConfigurationRepository
     */
    protected $configurationRepository;

    /**
     * Configures the command by setting its name, description and options.
     *
     * @return void
     */
    public function configure()
    {
        $this->setDescription('Runs an External Import synchronization, for one configuration or all of them.')
                ->setHelp('Choose a specific configuration using the --table and --index options. Use --all to synchronize all configurations.')
                ->addOption(
                        'table',
                        't',
                        InputOption::VALUE_REQUIRED,
                        'Choose a table name among those available for synchronization.'
                )
                ->addOption(
                        'index',
                        'i',
                        InputOption::VALUE_REQUIRED,
                        'Choose an index that matches the given table name.'
                )
                ->addOption(
                        'all',
                        'a',
                        InputOption::VALUE_NONE,
                        'Use this option to synchronize all existing configurations, in order of priority (other options are ignored).'
                )
                ->addOption(
                        'list',
                        'l',
                        InputOption::VALUE_NONE,
                        'Print a list of all existing External Import configurations available for synchronization.'
                );
    }

    /**
     * Executes the command that runs the selected import.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Make sure the _cli_ user is loaded
        Bootstrap::getInstance()->initializeBackendAuthentication();

        $io = new SymfonyStyle($input, $output);
        $io->title($this->getDescription());

        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->configurationRepository = $this->objectManager->get(ConfigurationRepository::class);

        $list = $input->getOption('list');
        // Call up the list and print it out
        if ($list) {
            $this->printConfigurationList($io);
        } else {
            $importer = $this->objectManager->get(Importer::class);
            $all = $input->getOption('all');
            $table = $input->getOption('table');
            $index = $input->getOption('index');
            // Launch full synchronization
            if ($all) {
                $configurations = $this->configurationRepository->findOrderedConfigurations();
                foreach ($configurations as $tableList) {
                    foreach ($tableList as $configuration) {
                        $io->section('Importing: ' . $configuration['table'] . ' / ' . $configuration['index']);
                        $messages = $importer->synchronize(
                                $configuration['table'],
                                $configuration['index']
                        );
                        $this->reportResults($io, $messages);
                    }
                }
            // Launch selected synchronization
            } elseif ($table !== null && $index !== null) {
                $messages = $importer->synchronize(
                        $table,
                        $index
                );
                $this->reportResults($io, $messages);
            } else {
                // Report erroneous arguments
                $io->warning('The command was called with invalid arguments. Please use "typo3 help externalimport:sync" for help.');
            }
        }
    }

    /**
     * Outputs messages returned by the import process.
     *
     * @param SymfonyStyle $io
     * @param array $messages
     */
    protected function reportResults(SymfonyStyle $io, $messages)
    {
        foreach ($messages as $severity => $messageList) {
            foreach ($messageList as $message) {
                switch ($severity) {
                    case AbstractMessage::ERROR:
                        $io->error($message);
                        break;
                    case AbstractMessage::WARNING:
                        $io->warning($message);
                        break;
                    case AbstractMessage::OK:
                        $io->success($message);
                }
            }
        }
    }

    /**
     * Prints the list of synchronizable configurations as a table.
     *
     * @param SymfonyStyle $io
     * @return void
     */
    protected function printConfigurationList(SymfonyStyle $io)
    {
        $configurations = $this->configurationRepository->findOrderedConfigurations();
        $outputTable = array();
        foreach ($configurations as $priority => $tableList) {
            foreach ($tableList as $configuration) {
                $outputTable[] = array($priority, $configuration['table'], $configuration['index']);
            }
        }
        $io->table(
                array('Priority', 'Table', 'Index'),
                $outputTable
        );
    }
}