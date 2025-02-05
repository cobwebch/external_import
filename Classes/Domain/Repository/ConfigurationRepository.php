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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Pseudo-repository for fetching external import configurations from the TCA
 *
 * This is not a true repository in the Extbase sense of the term, as it relies on reading its information
 * from the TCA and not a database. It also does not provide any persistence.
 */
class ConfigurationRepository
{
    /**
     * @var array Extension configuration
     */
    protected array $extensionConfiguration = [];

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
     * @throws \Cobweb\ExternalImport\Exception\NoConfigurationException
     */
    public function findByTableAndIndex(string $table, $index): array
    {
        $configuration = [
            'general' => [],
            'additionalFields' => [],
            'columns' => [],
        ];

        // General configuration
        if (isset($GLOBALS['TCA'][$table]['external']['general'][$index])) {
            $configuration['general'] = $this->processGeneralConfiguration(
                $GLOBALS['TCA'][$table]['external']['general'][$index]
            );
            // Check for legacy general configuration
            // TODO: remove in version 8.0
        } elseif (isset($GLOBALS['TCA'][$table]['ctrl']['external'][$index])) {
            $configuration['general'] = $this->processGeneralConfiguration(
                $GLOBALS['TCA'][$table]['ctrl']['external'][$index]
            );
        } else {
            throw new \Cobweb\ExternalImport\Exception\NoConfigurationException(
                sprintf(
                    'No configuration found for table %s and index %s',
                    $table,
                    $index
                ),
                1662459744
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
                        if (is_array($externalConfig['groups'] ?? [])) {
                            $groups = $externalConfig['groups'] ?? [];
                            // TODO: drop support for the "group" property in the next major version
                        } elseif (array_key_exists('group', $externalConfig)) {
                            $groups = [
                                $externalConfig['group'],
                            ];
                        } else {
                            $groups = [];
                        }
                        $externalTables[$priority][] = [
                            'table' => $tableName,
                            'index' => $index,
                            'groups' => $groups,
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
     * Finds all configurations belonging to the given group and returns them ordered by priority,
     * possibly filtered by synchronization status.
     *
     * @param string $group Name of the group to look up
     * @param bool $synchronizable TRUE to get only groups from synchronizable configurations
     * @param bool $nonSynchronizable TRUE to get only groups from non-synchronizable configurations
     * @return array
     */
    public function findByGroup(string $group, bool $synchronizable, bool $nonSynchronizable = false): array
    {
        $externalTables = [];
        foreach ($GLOBALS['TCA'] as $tableName => $sections) {
            if (isset($sections['external']['general']) || isset($sections['ctrl']['external'])) {
                $generalConfiguration = $sections['external']['general'] ?? $sections['ctrl']['external'];
                foreach ($generalConfiguration as $index => $externalConfig) {
                    // Skip the configurations we don't want, if either flag has been set
                    if (
                        ($synchronizable && empty($externalConfig['connector'])) ||
                        ($nonSynchronizable && !empty($externalConfig['connector']))
                    ) {
                        continue;
                    }
                    // TODO: drop support for "group" property in the next major version
                    $configuredGroups = $externalConfig['groups'] ?? [];
                    if (
                        (array_key_exists('group', $externalConfig) && $externalConfig['group'] === $group) ||
                        (is_array($configuredGroups) && in_array($group, $configuredGroups, true))
                    ) {
                        // Default priority if not defined, set to very low
                        $priority = $externalConfig['priority'] ?? Importer::DEFAULT_PRIORITY;
                        if (!isset($externalTables[$priority])) {
                            $externalTables[$priority] = [];
                        }
                        $externalTables[$priority][] = [
                            'table' => $tableName,
                            'index' => $index,
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
     * Returns all configuration groups, possibly filtered by synchronization status.
     *
     * @param bool $synchronizable TRUE to get only groups from synchronizable configurations
     * @param bool $nonSynchronizable TRUE to get only groups from non-synchronizable configurations
     * @return array
     */
    public function findAllGroups(bool $synchronizable = false, bool $nonSynchronizable = false): array
    {
        $groups = [];
        foreach ($GLOBALS['TCA'] as $tableName => $sections) {
            if (isset($sections['external']['general']) || isset($sections['ctrl']['external'])) {
                $generalConfiguration = $sections['external']['general'] ?? $sections['ctrl']['external'];
                foreach ($generalConfiguration as $index => $externalConfig) {
                    // Skip the configurations we don't want, if either flag has been set
                    if (
                        ($synchronizable && empty($externalConfig['connector'])) ||
                        ($nonSynchronizable && !empty($externalConfig['connector']))
                    ) {
                        continue;
                    }
                    // TODO: remove support for "group" property in the next major version
                    if (!empty($externalConfig['group'])) {
                        $groups[] = $externalConfig['group'];
                    }
                    $configuredGroups = $externalConfig['groups'] ?? [];
                    if (is_array($configuredGroups) && count($configuredGroups) > 0) {
                        foreach ($configuredGroups as $group) {
                            $groups[] = $group;
                        }
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
     * @throws \Cobweb\ExternalImport\Exception\NoConfigurationException
     */
    public function findConfigurationObject(string $table, $index, ?array $defaultSteps = null): Configuration
    {
        /** @var Configuration $configuration */
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

        // Once all values are set, restructure configuration for later use
        $configuration->processConfiguration();

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
                            if (str_starts_with($externalConfiguration['description'], 'LLL:')) {
                                $description = $GLOBALS['LANG']->sL($externalConfiguration['description']);
                            } else {
                                $description = $externalConfiguration['description'];
                            }
                        }
                        if (str_starts_with($sections['ctrl']['title'] ?? '', 'LLL:')) {
                            $tableTitle = $GLOBALS['LANG']->sL($sections['ctrl']['title']);
                        } else {
                            $tableTitle = $sections['ctrl']['title'] ?? 'untitled';
                        }
                        // TODO: drop support for old "group" property in the next major version; for now automatically convert it
                        if (array_key_exists('group', $externalConfiguration)) {
                            $externalConfiguration['groups'] = [
                                $externalConfiguration['group'],
                            ];
                        } else {
                            $externalConfiguration['groups'] = $externalConfiguration['groups'] ?? [];
                        }
                        // Store the base configuration
                        $configurationKey = GeneralUtility::makeInstance(ConfigurationKey::class);
                        $configurationKey->setTableAndIndex($tableName, (string)$index);
                        $taskId = $configurationKey->getConfigurationKey();
                        $groupKeys = [];
                        if (count($externalConfiguration['groups']) > 0) {
                            foreach ($externalConfiguration['groups'] as $group) {
                                $groupKeys[] = 'group:' . $group;
                            }
                        }
                        $tableConfiguration = [
                            'id' => $taskId,
                            'table' => $tableName,
                            'tableName' => $tableTitle,
                            'index' => $index,
                            'priority' => $priority,
                            'groups' => $externalConfiguration['groups'],
                            'description' => htmlspecialchars($description),
                            'writeAccess' => $hasWriteAccess,
                        ];
                        // Add Scheduler task information, if any
                        // If the configuration is part of a group and that group is automated, return task information too,
                        // but if the configuration is specifically automated, that will take precedence
                        if (array_key_exists($taskId, $tasks)) {
                            $tableConfiguration['automated'] = 1;
                            $tableConfiguration['task'] = $tasks[$taskId];
                            $tableConfiguration['groupTask'] = 0;
                        } elseif (count(array_intersect($groupKeys, array_keys($tasks))) > 0) {
                            // There could be multiple tasks. We consider only the first one.
                            // This is not fully satisfying, but the same could actually happen in the previous case too,
                            // as a configuration could be synchronized several times (probably mistakenly, but you never know...)
                            $foundTasks = array_intersect($groupKeys, array_keys($tasks));
                            $tableConfiguration['automated'] = 1;
                            $tableConfiguration['task'] = $tasks[array_shift($foundTasks)];
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
    protected function processGeneralConfiguration(array $configuration): array
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
