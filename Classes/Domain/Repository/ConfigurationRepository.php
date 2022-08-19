<?php

declare(strict_types=1);

namespace Cobweb\ExternalImport\Domain\Repository;

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

use Cobweb\ExternalImport\Domain\Model\Configuration;
use Cobweb\ExternalImport\Domain\Model\ConfigurationKey;
use Cobweb\ExternalImport\Importer;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Pseudo-repository for fetching external import configurations from the TCA
 *
 * This is not a true repository in the Extbase sense of the term, as it relies on reading its information
 * from the TCA and not a database. It also does not provide any persistence.
 *
 * @package Cobweb\ExternalImport\Domain\Repository
 */
class ConfigurationRepository
{
    /**
     * @var array Extension configuration
     */
    protected $extensionConfiguration = [];

    public function __toString()
    {
        return self::class;
    }

    /**
     * ConfigurationRepository constructor.
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException
     */
    public function __construct()
    {
        $this->extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get(
            'external_import'
        );
    }

    /**
     * Returns the various sections of the external import configuration for the given table and index.
     *
     * @param string $table Name of the table
     * @param string|int $index Key of the configuration
     * @return array The relevant TCA configuration
     */
    public function findByTableAndIndex(string $table, $index): array
    {
        $configuration = [
            'general' => [],
            'additionalFields' => [],
            'columns' => []
        ];

        // General configuration
        if (isset($GLOBALS['TCA'][$table]['external']['general'][$index])) {
            $configuration['general'] = $this->processGeneralConfiguration(
                $GLOBALS['TCA'][$table]['external']['general'][$index]
            );
            // Check for legacy general configuration
        } elseif (isset($GLOBALS['TCA'][$table]['ctrl']['external'][$index])) {
            $configuration['general'] = $this->processGeneralConfiguration(
                $GLOBALS['TCA'][$table]['ctrl']['external'][$index]
            );
        }

        // Load additional fields configuration
        if (isset($GLOBALS['TCA'][$table]['external']['additionalFields'][$index])) {
            $configuration['additionalFields'] = $GLOBALS['TCA'][$table]['external']['additionalFields'][$index];
        }

        // Load columns configuration
        // Override the configuration index for columns, if so defined
        $alternateIndex = $configuration['general']['useColumnIndex'] ?? '';
        if (isset($GLOBALS['TCA'][$table]['columns'])) {
            $columnsConfiguration = $GLOBALS['TCA'][$table]['columns'];
            ksort($columnsConfiguration);
            foreach ($columnsConfiguration as $columnName => $columnData) {
                // If a configuration for the given column and index exists, it always takes precedence,
                // otherwise the alternate index is considered, if defined
                if (isset($columnData['external'][$index])) {
                    $configuration['columns'][$columnName] = $columnData['external'][$index];
                } elseif ($alternateIndex !== '' && isset($columnData['external'][$alternateIndex])) {
                    $configuration['columns'][$columnName] = $columnData['external'][$alternateIndex];
                }
            }
        }

        return $configuration;
    }

    /**
     * Finds all synchronizable configurations and returns them ordered by priority.
     *
     * @return array
     */
    public function findOrderedConfigurations(): array
    {
        $externalTables = [];
        foreach ($GLOBALS['TCA'] as $tableName => $sections) {
            if (isset($sections['external']['general']) || isset($sections['ctrl']['external'])) {
                $generalConfiguration = $sections['external']['general'] ?? $sections['ctrl']['external'];
                foreach ($generalConfiguration as $index => $externalConfig) {
                    if (!empty($externalConfig['connector'])) {
                        // Default priority if not defined, set to very low
                        $priority = $externalConfig['priority'] ?? Importer::DEFAULT_PRIORITY;
                        if (!isset($externalTables[$priority])) {
                            $externalTables[$priority] = [];
                        }
                        $externalTables[$priority][] = [
                            'table' => $tableName,
                            'index' => $index,
                            'group' => $externalConfig['group'] ?? '-'
                        ];
                    }
                }
            }
        }
        // Sort tables by priority (lower number is highest priority)
        ksort($externalTables);

        return $externalTables;
    }

    /**
     * Finds all synchronizable configurations belonging to the given group and returns them ordered by priority.
     *
     * @param string $group Name of the group to look up
     * @return array
     */
    public function findByGroup(string $group): array
    {
        $externalTables = [];
        foreach ($GLOBALS['TCA'] as $tableName => $sections) {
            if (isset($sections['external']['general']) || isset($sections['ctrl']['external'])) {
                $generalConfiguration = $sections['external']['general'] ?? $sections['ctrl']['external'];
                foreach ($generalConfiguration as $index => $externalConfig) {
                    if (!empty($externalConfig['connector']) && array_key_exists('group', $externalConfig) && $externalConfig['group'] === $group) {
                        // Default priority if not defined, set to very low
                        $priority = $externalConfig['priority'] ?? Importer::DEFAULT_PRIORITY;
                        if (!isset($externalTables[$priority])) {
                            $externalTables[$priority] = [];
                        }
                        $externalTables[$priority][] = [
                            'table' => $tableName,
                            'index' => $index
                        ];
                    }
                }
            }
        }
        // Sort tables by priority (lower number is highest priority)
        ksort($externalTables);

        return $externalTables;
    }

    /**
     * Returns all configuration groups.
     *
     * @return array
     */
    public function findAllGroups(): array
    {
        $groups = [];
        foreach ($GLOBALS['TCA'] as $tableName => $sections) {
            if (isset($sections['external']['general']) || isset($sections['ctrl']['external'])) {
                $generalConfiguration = $sections['external']['general'] ?? $sections['ctrl']['external'];
                foreach ($generalConfiguration as $index => $externalConfig) {
                    if (!empty($externalConfig['connector']) && !empty($externalConfig['group'])) {
                        $groups[] = $externalConfig['group'];
                    }
                }
            }
        }
        $groups = array_unique($groups);
        sort($groups);
        return $groups;
    }

    /**
     * Returns the full External Import configuration as an object for the given table and index.
     *
     * @param string $table Name of the table
     * @param string|int $index Key of the configuration
     * @param array|null $defaultSteps List of default steps (if null will be guessed by the Configuration object)
     * @return Configuration
     */
    public function findConfigurationObject(string $table, $index, $defaultSteps = null): Configuration
    {
        $configuration = GeneralUtility::makeInstance(Configuration::class);
        $externalConfiguration = $this->findByTableAndIndex($table, $index);

        // Set the values in the Configuration object
        $configuration->setTable($table);
        $configuration->setIndex($index);
        $configuration->setGeneralConfiguration($externalConfiguration['general'], $defaultSteps);
        if (array_key_exists('additionalFields', $externalConfiguration)) {
            $configuration->setAdditionalFields($externalConfiguration['additionalFields']);
        }
        $configuration->setColumnConfiguration($externalConfiguration['columns']);
        return $configuration;
    }

    /**
     * Returns external import configurations based on their sync type.
     *
     * The return structure of this method is very specific and used only by the DataModuleController
     * to display a list of all configurations, including Scheduler information, if any.
     *
     * @param bool $isSynchronizable True for tables with synchronization configuration, false for others
     * @return array List of external import TCA configurations
     */
    public function findBySync(bool $isSynchronizable): array
    {
        $isSynchronizable = (bool)$isSynchronizable;
        $configurations = [];

        // Get a list of all external import Scheduler tasks
        $tasks = [];
        $schedulerRepository = GeneralUtility::makeInstance(SchedulerRepository::class);
        $tasks = $schedulerRepository->fetchAllTasks();

        // Loop on all tables and extract external_import-related information from them
        $backendUser = $this->getBackendUser();
        foreach ($GLOBALS['TCA'] as $tableName => $sections) {
            // Check if table has external info and user has at least read-access to it
            if ((isset($sections['external']['general']) || isset($sections['ctrl']['external'])) &&
                $backendUser->check('tables_select', $tableName)) {
                $generalConfiguration = $sections['external']['general'] ?? $sections['ctrl']['external'];
                $hasWriteAccess = $backendUser->check('tables_modify', $tableName);
                foreach ($generalConfiguration as $index => $externalConfiguration) {
                    // Synchronizable tables have a connector configuration
                    // Non-synchronizable tables don't
                    if (
                        ($isSynchronizable && !empty($externalConfiguration['connector'])) ||
                        (!$isSynchronizable && empty($externalConfiguration['connector']))
                    ) {
                        // If priority is not defined, set to very low
                        // NOTE: the priority doesn't matter for non-synchronizable tables
                        $priority = Importer::DEFAULT_PRIORITY;
                        if (isset($externalConfiguration['priority'])) {
                            $priority = (int)$externalConfiguration['priority'];
                        }
                        $description = '';
                        if (isset($externalConfiguration['description'])) {
                            if (strpos($externalConfiguration['description'], 'LLL:') === 0) {
                                $description = LocalizationUtility::translate($externalConfiguration['description']);
                            } else {
                                $description = $externalConfiguration['description'];
                            }
                        }
                        if (strpos($sections['ctrl']['title'], 'LLL:') === 0) {
                            $tableTitle = LocalizationUtility::translate($sections['ctrl']['title']);
                        } else {
                            $tableTitle = $sections['ctrl']['title'];
                        }
                        // Store the base configuration
                        $configurationKey = GeneralUtility::makeInstance(ConfigurationKey::class);
                        $configurationKey->setTableAndIndex($tableName, (string)$index);
                        $taskId = $configurationKey->getConfigurationKey();
                        $groupKey = ($externalConfiguration['group'] ?? false) ? 'group:' . $externalConfiguration['group'] : '';
                        $tableConfiguration = [
                            'id' => $taskId,
                            'table' => $tableName,
                            'tableName' => $tableTitle,
                            'index' => $index,
                            'priority' => $priority,
                            'group' => $externalConfiguration['group'] ?? '',
                            'description' => htmlspecialchars($description),
                            'writeAccess' => $hasWriteAccess
                        ];
                        // Add Scheduler task information, if any
                        // If the configuration is part of a group and that group is automated, return task information too,
                        // but if the configuration is specifically automated, that will take precedence
                        if (array_key_exists($taskId, $tasks)) {
                            $tableConfiguration['automated'] = 1;
                            $tableConfiguration['task'] = $tasks[$taskId];
                            $tableConfiguration['groupTask'] = 0;
                        } elseif (array_key_exists($groupKey, $tasks)) {
                            $tableConfiguration['automated'] = 1;
                            $tableConfiguration['task'] = $tasks[$groupKey];
                            $tableConfiguration['groupTask'] = 1;
                        } else {
                            $tableConfiguration['automated'] = 0;
                            $tableConfiguration['task'] = null;
                            $tableConfiguration['groupTask'] = 0;
                        }
                        $configurations[] = $tableConfiguration;
                    }
                }
            }
        }

        // Return the results
        return $configurations;
    }

    /**
     * Checks if user has write access to some, all or none of the tables having an external configuration.
     *
     * @return string Global access (none, partial or all)
     */
    public function findGlobalWriteAccess(): string
    {
        // An admin user has full access
        $backendUser = $this->getBackendUser();
        if ($backendUser->isAdmin()) {
            $hasGlobalWriteAccess = 'all';
        } else {
            // Loop on all tables and extract external_import-related information from them
            $noAccessCount = 0;
            $numberOfTables = 0;
            foreach ($GLOBALS['TCA'] as $tableName => $sections) {
                // Check if table has external info
                if (isset($sections['ctrl']['external'])) {
                    $numberOfTables++;
                    // Check if user has write rights on it
                    if (!$backendUser->check('tables_modify', $tableName)) {
                        $noAccessCount++;
                    }
                }
            }
            // If the user has no restriction, then access is full
            if ($noAccessCount === 0) {
                $hasGlobalWriteAccess = 'all';
                // Assess if user has rights to no table at all or at least to some
            } elseif ($noAccessCount === $numberOfTables) {
                $hasGlobalWriteAccess = 'none';
            } else {
                $hasGlobalWriteAccess = 'partial';
            }
        }
        return $hasGlobalWriteAccess;
    }

    /**
     * Performs some processing on the general configuration, like taking global values into account.
     *
     * @param array $configuration The external import configuration to process
     * @return array The processed configuration
     */
    protected function processGeneralConfiguration($configuration): array
    {
        // If the pid is not set in the current configuration, use global storage pid
        $pid = 0;
        if (array_key_exists('pid', $configuration)) {
            $pid = (int)$configuration['pid'];
        } elseif (array_key_exists('storagePID', $this->extensionConfiguration)) {
            $pid = (int)$this->extensionConfiguration['storagePID'];
        }
        $configuration['pid'] = $pid;

        return $configuration;
    }

    /**
     * Returns the BE user object.
     *
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
