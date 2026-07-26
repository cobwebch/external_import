<?php

declare(strict_types=1);

namespace Cobweb\ExternalImport\Task;

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

use Cobweb\ExternalImport\Domain\Model\ConfigurationKey;
use Cobweb\ExternalImport\Domain\Repository\ConfigurationRepository;
use Cobweb\ExternalImport\Enum\CallType;
use Cobweb\ExternalImport\Exception\NoConfigurationException;
use Cobweb\ExternalImport\Importer;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * This class executes Scheduler events for automatic synchronisations of external data
 */
class SynchronizationTask extends AbstractTask
{
    protected string $sync_item = '';
    protected string $sync_storage = '';
    protected bool $synchronizeAll = false;
    protected string $group = '';
    protected string $table = '';
    protected mixed $index = '';
    public int $storage = 0;

    /**
     * Executes the job registered in the Scheduler task
     *
     * @return bool
     * @throws \Exception
     */
    public function execute(): bool
    {
        $reportContent = '';

        // Instantiate the import object and call appropriate method depending on command
        $importer = GeneralUtility::makeInstance(Importer::class);
        $importer->setCallType(CallType::Scheduler);
        // Override the storage page, if defined
        if ($this->storage > 0) {
            $importer->setForcedStoragePid($this->storage);
        }
        // Get the extension's configuration from the importer object
        $extensionConfiguration = $importer->getExtensionConfiguration();
        // Synchronize all tables
        $globalStatus = 'OK';
        $errorCount = 0;
        if ($this->table === 'all' || str_starts_with($this->table, 'group:')) {
            if ($this->table === 'all') {
                $configurations = $importer->getConfigurationRepository()->findOrderedConfigurations();
            } else {
                $group = substr($this->table, 6);
                $configurations = $importer->getConfigurationRepository()->findByGroup($group, true);
            }
            // Exit early if no configuration was found
            if (count($configurations) === 0) {
                throw new NoConfigurationException(
                    'No configuration was found for synchronization. Please check your task settings or your configuration via the BE module.',
                    1530390188
                );
            }
            // Loop on all found configurations
            foreach ($configurations as $tableList) {
                foreach ($tableList as $configuration) {
                    $messages = $importer->synchronize(
                        $configuration['table'],
                        $configuration['index']
                    );
                    if (!empty($extensionConfiguration['reportEmail'])) {
                        $reportContent .= $importer->getReportingUtility()->reportForTable(
                            $configuration['table'],
                            $configuration['index'],
                            $messages
                        );
                        $errorCount += count($messages[ContextualFeedbackSeverity::ERROR->value]);
                    }
                }
            }
            if ($errorCount > 0) {
                $globalStatus = 'ERROR';
            }
            // If necessary, prepare a report with all messages
            if (!empty($extensionConfiguration['reportEmail'])) {
                // Assemble the subject and send the mail
                $subject = empty($extensionConfiguration['reportSubject']) ? '' : $extensionConfiguration['reportSubject'];
                $subject .= ' [' . $globalStatus . '] ' . 'Full synchronization';
                $importer->getReportingUtility()->sendMail($subject, $reportContent);
            }
        } else {
            $messages = $importer->synchronize($this->table, $this->index);
            if (count($messages[ContextualFeedbackSeverity::ERROR->value]) > 0) {
                $globalStatus = 'ERROR';
            }
            // If necessary, prepare a report with all messages
            if (!empty($extensionConfiguration['reportEmail'])) {
                $reportContent .= $importer->getReportingUtility()->reportForTable(
                    $this->table,
                    $this->index,
                    $messages
                );
                // Assemble the subject and send the mail
                $subject = empty($extensionConfiguration['reportSubject']) ? '' : $extensionConfiguration['reportSubject'];
                $subject .= ' [' . $globalStatus . '] ' . 'Synchronization of table ' . $this->table . ', index ' . $this->index;
                $importer->getReportingUtility()->sendMail($subject, $reportContent);
            }
        }
        // If any error happened, throw an exception, otherwise return true
        if ($globalStatus !== 'OK') {
            throw new \Exception(
                'One or more errors happened. Please consult the log.',
                1258116760
            );
        }
        return true;
    }

    /**
     * Post-process values coming from DB record
     *
     * @param array $parameters Values from TCA fields
     */
    public function setTaskParameters(array $parameters): void
    {
        parent::setTaskParameters($parameters);
        if ($this->sync_item === 'all') {
            $this->synchronizeAll = true;
        } elseif (str_starts_with($this->sync_item, 'group:')) {
            $this->group = substr($this->table, 6);
        } else {
            $configurationKey = GeneralUtility::makeInstance(ConfigurationKey::class);
            $configurationKey->setConfigurationKey($this->sync_item);
            $this->table = $configurationKey->getTable();
            $this->index = $configurationKey->getIndex();
        }
        $this->storage = (int)$this->sync_storage;
    }

    /**
     * Returns additional information for display in the Scheduler BE module.
     *
     * @return string Information to display
     */
    public function getAdditionalInformation(): string
    {
        if ($this->synchronizeAll) {
            $info = $this->getLanguageService()->sL(
                'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:allTables'
            );
        } elseif (!empty($this->group)) {
            $info = sprintf(
                $this->getLanguageService()->sL(
                    'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:selectedGroup'
                ),
                $this->group
            );
        } else {
            try {
                $configurationRepository = GeneralUtility::makeInstance(ConfigurationRepository::class);
                $configuration = $configurationRepository->findConfigurationObject($this->table, $this->index);
                $info = sprintf(
                    $this->getLanguageService()->sL(
                        'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:tableIndexAndPriority'
                    ),
                    $this->table,
                    $this->index,
                    $configuration->getGeneralConfigurationProperty('priority')
                );
            } catch (\Exception $e) {
                $info = '';
            }
        }
        if ($this->storage > 0) {
            if (!empty($info)) {
                $info .= ' / ';
            }
            $info .= sprintf(
                $this->getLanguageService()->sL(
                    'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:overrideStoragePid'
                ),
                $this->storage
            );
        }
        return $info;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getIndex(): mixed
    {
        return $this->index;
    }

    public function isSynchronizeAll(): bool
    {
        return $this->synchronizeAll;
    }
}
