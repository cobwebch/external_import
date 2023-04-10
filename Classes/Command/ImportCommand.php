<?php

declare(strict_types=1);

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

use Cobweb\ExternalImport\Context\AbstractCallContext;
use Cobweb\ExternalImport\Context\CommandLineCallContext;
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
    protected SymfonyStyle $io;

    /**
     * @var ConfigurationRepository
     */
    protected ConfigurationRepository $configurationRepository;

    /**
     * @var Importer
     */
    protected Importer $importer;

    /**
     * Configures the command by setting its name, description and options.
     *
     * @return void
     */
    public function configure()
    {
        $this->setDescription('Runs an External Import synchronization, for one configuration or all of them.')
            ->setHelp(
                'Choose a specific configuration using the --table and --index options. Use --all to synchronize all configurations.'
            )
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
                'group',
                'g',
                InputOption::VALUE_REQUIRED,
                'Use this option to synchronize all configurations from a given group, in order of priority ("all" comes first, other options are ignored).'
            )
            ->addOption(
                'list',
                'l',
                InputOption::VALUE_NONE,
                'Print a list of all existing External Import configurations available for synchronization.'
            )
            ->addOption(
                'storage',
                's',
                InputOption::VALUE_REQUIRED,
                'Define a specific page (using its uid) in TYPO3 where to store the imported data. Overrides TCA or extension settings.'
            )
            ->addOption(
                'debug',
                'd',
                InputOption::VALUE_NONE,
                'Turns on debugging. Debug output goes to the devlog unless verbose mode is also turned on.'
            )
            ->addOption(
                'preview',
                'p',
                InputOption::VALUE_REQUIRED,
                'Turns on preview mode. Process will stop at the given step (class name) and will output relevant data. No data is saved to the database.'
            );
    }

    /**
     * Executes the command that runs the selected import.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Make sure the _cli_ user is loaded
        Bootstrap::initializeBackendAuthentication();

        $this->io = new SymfonyStyle($input, $output);
        $this->io->title($this->getDescription());

        try {
            $this->configurationRepository = GeneralUtility::makeInstance(ConfigurationRepository::class);

            $list = $input->getOption('list');
            // Call up the list and print it out
            if ($list) {
                $this->printConfigurationList();
            } else {
                $this->importer = GeneralUtility::makeInstance(Importer::class);
                $this->importer->setContext('cli');
                /** @var AbstractCallContext $callContext */
                $callContext = GeneralUtility::makeInstance(
                    CommandLineCallContext::class,
                    $this->importer
                );
                $callContext->setInputOutput($this->io);
                $this->importer->setCallContext($callContext);

                $preview = $input->getOption('preview');
                if ($preview) {
                    $this->importer->setPreviewStep($preview);
                }
                $all = $input->getOption('all');
                $group = $input->getOption('group');
                $table = $input->getOption('table');
                $index = $input->getOption('index');

                // Override the storage page, if defined
                $storage = (int)$input->getOption('storage');
                if ($storage > 0) {
                    $this->importer->setForcedStoragePid($storage);
                }

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
                    $this->runSynchronization($configurations);
                    // Launch group synchronization
                } elseif ($group) {
                    $configurations = $this->configurationRepository->findByGroup($group);
                    $this->runSynchronization($configurations);
                    // Launch selected synchronization
                } elseif ($table !== null && $index !== null) {
                    // Assemble fake configuration array, for calling the same method as above
                    $configurations = [
                        Importer::DEFAULT_PRIORITY => [
                            [
                                'table' => $table,
                                'index' => $index
                            ]
                        ]
                    ];
                    $this->runSynchronization($configurations);
                } else {
                    // Report erroneous arguments
                    $this->io->warning(
                        'The command was called with invalid arguments. Please use "typo3 help externalimport:sync" for help.'
                    );
                }
            }
            return 0;
        } catch (\Exception $e) {
            $this->io->error(
                sprintf(
                    'An exception occurred: %s (%d)',
                    $e->getMessage(),
                    $e->getCode()
                )
            );
            return 1;
        }
    }

    /**
     * Prints the list of synchronizable configurations as a table.
     *
     * @return void
     */
    protected function printConfigurationList(): void
    {
        $configurations = $this->configurationRepository->findOrderedConfigurations();
        $outputTable = [];
        foreach ($configurations as $priority => $tableList) {
            foreach ($tableList as $configuration) {
                $outputTable[] = [
                    $priority,
                    $configuration['table'],
                    $configuration['index'],
                    $configuration['group']
                ];
            }
        }
        $this->io->table(
            ['Priority', 'Table', 'Index', 'Group'],
            $outputTable
        );
    }

    /**
     * Runs the synchronization of the given list of configurations.
     *
     * @param array $configurations List of External Import configurations
     * @return void
     */
    protected function runSynchronization(array $configurations): void
    {
        if (count($configurations) === 0) {
            $this->io->warning('No configuration to synchronize.');
        } else {
            foreach ($configurations as $tableList) {
                foreach ($tableList as $configuration) {
                    $this->io->section('Importing: ' . $configuration['table'] . ' / ' . $configuration['index']);
                    $messages = $this->importer->synchronize(
                        $configuration['table'],
                        $configuration['index']
                    );
                    if ($this->importer->isPreview()) {
                        $this->io->section('Preview data');
                        $this->io->writeln(
                            var_export(
                                $this->importer->getPreviewData(),
                                true
                            )
                        );
                    }
                    $this->reportResults($messages);
                }
            }
        }
    }

    /**
     * Outputs messages returned by the import process.
     *
     * @param array $messages
     */
    protected function reportResults(array $messages): void
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
}