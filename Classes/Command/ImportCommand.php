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
     * @var SymfonyStyle
     */
    protected $io;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var ConfigurationRepository
     */
    protected $configurationRepository;

    /**
     * @var Importer
     */
    protected $importer;

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
                )
                ->addOption(
                        'debug',
                        'd',
                        InputOption::VALUE_NONE,
                        'Turns on debugging. Debug output goes to the devlog unless verbose mode is also turned on.'
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

        $this->io = new SymfonyStyle($input, $output);
        $this->io->title($this->getDescription());

        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->configurationRepository = $this->objectManager->get(ConfigurationRepository::class);

        $list = $input->getOption('list');
        // Call up the list and print it out
        if ($list) {
            $this->printConfigurationList();
        } else {
            $this->importer = $this->objectManager->get(Importer::class);
            $this->importer->setContext('cli');
            $callContext = $this->objectManager->get(
                    \Cobweb\ExternalImport\Context\CommandLineCallContext::class,
                    $this->importer
            );
            $this->importer->setCallContext($callContext);

            $all = $input->getOption('all');
            $table = $input->getOption('table');
            $index = $input->getOption('index');

            // Check output options
            $debug = $input->getOption('debug');
            // Set the debug flag only if true
            if ($debug) {
                $this->importer->setDebug((bool)$debug);
            }
            $this->importer->setVerbose($output->isVerbose());

            // Launch full synchronization
            if ($all) {
                $configurations = $this->configurationRepository->findOrderedConfigurations();
                foreach ($configurations as $tableList) {
                    foreach ($tableList as $configuration) {
                        $this->io->section('Importing: ' . $configuration['table'] . ' / ' . $configuration['index']);
                        $messages = $this->importer->synchronize(
                                $configuration['table'],
                                $configuration['index']
                        );
                        $this->reportResults($messages);
                    }
                }
            // Launch selected synchronization
            } elseif ($table !== null && $index !== null) {
                $messages = $this->importer->synchronize(
                        $table,
                        $index
                );
                $this->reportResults($messages);
            } else {
                // Report erroneous arguments
                $this->io->warning('The command was called with invalid arguments. Please use "typo3 help externalimport:sync" for help.');
            }
        }
    }

    /**
     * Outputs messages returned by the import process.
     *
     * @param array $messages
     */
    protected function reportResults($messages)
    {
        foreach ($messages as $severity => $messageList) {
            foreach ($messageList as $message) {
                switch ($severity) {
                    case AbstractMessage::ERROR:
                        $this->io->error($message);
                        break;
                    case AbstractMessage::WARNING:
                        $this->io->warning($message);
                        break;
                    case AbstractMessage::OK:
                        $this->io->success($message);
                }
            }
        }
    }

    /**
     * Prints the list of synchronizable configurations as a table.
     *
     * @return void
     */
    protected function printConfigurationList()
    {
        $configurations = $this->configurationRepository->findOrderedConfigurations();
        $outputTable = array();
        foreach ($configurations as $priority => $tableList) {
            foreach ($tableList as $configuration) {
                $outputTable[] = array($priority, $configuration['table'], $configuration['index']);
            }
        }
        $this->io->table(
                array('Priority', 'Table', 'Index'),
                $outputTable
        );
    }
}