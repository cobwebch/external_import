<?php
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
use Cobweb\ExternalImport\Exception\ConfigurationNotFoundException;
use Cobweb\ExternalImport\Importer;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Pseudo-repository for fetching external import configurations from the TCA
 *
 * This is not a true repository in the Extbase sense of the term, as it relies on reading its information
 * from the TCA and not a database. It also does not provide any persistence.
 *
 * @author Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package TYPO3
 * @subpackage tx_externalimport
 */
class ConfigurationRepository
{
    /**
     * @var array Extension configuration
     */
    protected $extensionConfiguration = array();

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    protected $objectManager;

    public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @var array List of renamed properties and their new name, for the "ctrl" part of the configuration
     */
    static public $renamedControlProperties = array(
            'additional_fields' => 'additionalFields',
            'reference_uid' => 'referenceUid',
            'where_clause' => 'whereClause'
    );

    public function __construct()
    {
        $this->extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['external_import']);
    }

    /**
     * Returns the "ctrl" part of the external import configuration for the given table and index.
     *
     * @param string $table Name of the table
     * @param string|integer $index Key of the configuration
     * @return array The relevant TCA configuration
     * @throws \Cobweb\ExternalImport\Exception\ConfigurationNotFoundException
     */
    public function findByTableAndIndex($table, $index)
    {
        if (isset($GLOBALS['TCA'][$table]['ctrl']['external'][$index])) {
            return $this->processCtrlConfiguration(
                    $GLOBALS['TCA'][$table]['ctrl']['external'][$index]
            );
        }
        throw new ConfigurationNotFoundException(
                sprintf('No ctrl configuration found for table %s, index %s', $table, $index),
                1492287150
        );
    }

    /**
     * Returns the "ctrl" part of all external import configurations for the given table.
     *
     * @param string $table Name of the table
     * @return array The relevant TCA configurations
     */
    public function findByTable($table)
    {
        $configurations = array();
        if (isset($GLOBALS['TCA'][$table]['ctrl']['external'])) {
            foreach ($GLOBALS['TCA'][$table]['ctrl']['external'] as $index => $externalCtrlConfiguration) {
                $configurations[$index] = $this->processCtrlConfiguration($externalCtrlConfiguration);
            }
        }
        return $configurations;
    }

    /**
     * Returns the columns part of the external import configuration for the given table and index.
     *
     * @param string $table Name of the table
     * @param string|integer $index Key of the configuration
     * @return array The relevant TCA configuration
     * @throws \Cobweb\ExternalImport\Exception\ConfigurationNotFoundException
     */
    public function findColumnsByTableAndIndex($table, $index)
    {
        $columns = array();
        if (isset($GLOBALS['TCA'][$table]['columns'])) {
            $columnsConfiguration = $GLOBALS['TCA'][$table]['columns'];
            ksort($columnsConfiguration);
            foreach ($columnsConfiguration as $columnName => $columnData) {
                if (isset($columnData['external'][$index])) {
                    $columns[$columnName] = $columnData['external'][$index];
                }
            }
        }
        if (count($columns) === 0) {
            throw new ConfigurationNotFoundException(
                    sprintf('No column configurations found for table %s, index %s', $table, $index),
                    1492287150
            );
        }
        return $columns;
    }

    /**
     * Finds all synchronizable configurations and returns them order by priority.
     *
     * @return array
     */
    public function findOrderedConfigurations()
    {
        $externalTables = array();
        foreach ($GLOBALS['TCA'] as $tableName => $sections) {
            if (isset($sections['ctrl']['external'])) {
                foreach ($sections['ctrl']['external'] as $index => $externalConfig) {
                    if (!empty($externalConfig['connector'])) {
                        // Default priority if not defined, set to very low
                        $priority = Importer::DEFAULT_PRIORITY;
                        if (isset($externalConfig['priority'])) {
                            $priority = $externalConfig['priority'];
                        }
                        if (!isset($externalTables[$priority])) {
                            $externalTables[$priority] = array();
                        }
                        $externalTables[$priority][] = array('table' => $tableName, 'index' => $index);
                    }
                }
            }
        }
        // Sort tables by priority (lower number is highest priority)
        ksort($externalTables);

        return $externalTables;
    }

    /**
     * Returns the full External Import configuration as an object for the given table and index.
     *
     * @param string $table Name of the table
     * @param string|integer $index Key of the configuration
     * @return Configuration
     * @throws \Cobweb\ExternalImport\Exception\ConfigurationNotFoundException
     */
    public function findConfigurationObject($table, $index)
    {
        $configuration = $this->objectManager->get(Configuration::class);
        $ctrlConfiguration = $this->findByTableAndIndex($table, $index);

        // Override the configuration index for columns, if so defined
        if (isset($ctrlConfiguration['useColumnIndex'])) {
            $index = $ctrlConfiguration['useColumnIndex'];
        }
        $columnsConfiguration = $this->findColumnsByTableAndIndex($table, $index);

        // Set the values in the Configuration object
        $configuration->setTable($table);
        $configuration->setIndex($index);
        $configuration->setCtrlConfiguration($ctrlConfiguration);
        $configuration->setColumnConfiguration($columnsConfiguration);
        return $configuration;
    }

    /**
     * Returns external import configurations based on their sync type.
     *
     * @param bool $isSynchronizable True for tables with synchronization configuration, false for others
     * @return array List of external import TCA configurations
     */
    public function findBySync($isSynchronizable)
    {
        $isSynchronizable = (bool)$isSynchronizable;
        $configurations = array();

        // Get a list of all external import Scheduler tasks, if Scheduler is active
        $tasks = array();
        if (ExtensionManagementUtility::isLoaded('scheduler')) {
            /** @var $schedulerRepository SchedulerRepository */
            $schedulerRepository = GeneralUtility::makeInstance(SchedulerRepository::class);
            $tasks = $schedulerRepository->fetchAllTasks();
        }

        // Loop on all tables and extract external_import-related information from them
        foreach ($GLOBALS['TCA'] as $tableName => $sections) {
            // Check if table has external info
            if (isset($sections['ctrl']['external'])) {
                // Check if user has read rights on it
                // If not, the table is skipped entirely
                if ($GLOBALS['BE_USER']->check('tables_select', $tableName)) {
                    $externalData = $sections['ctrl']['external'];
                    $hasWriteAccess = $GLOBALS['BE_USER']->check('tables_modify', $tableName);
                    foreach ($externalData as $index => $externalConfig) {
                        // Synchronizable tables have a connector configuration
                        // Non-synchronizable tables don't
                        if (
                                ($isSynchronizable && !empty($externalConfig['connector'])) ||
                                (!$isSynchronizable && empty($externalConfig['connector']))
                        ) {
                            // If priority is not defined, set to very low
                            // NOTE: the priority doesn't matter for non-synchronizable tables
                            $priority = 1000;
                            $description = '';
                            if (isset($externalConfig['priority'])) {
                                $priority = $externalConfig['priority'];
                            }
                            if (isset($externalConfig['description'])) {
                                $description = $GLOBALS['LANG']->sL($externalConfig['description']);
                            }
                            if (isset($externalConfig['useColumnIndex'])) {
                                $columnIndex = $externalConfig['useColumnIndex'];
                            } else {
                                $columnIndex = $index;
                            }
                            // Store the base configuration
                            $tableConfiguration = array(
                                    'id' => $tableName . '-' . $index,
                                    'table' => $tableName,
                                    'tableName' => $GLOBALS['LANG']->sL($sections['ctrl']['title']),
                                    'index' => $index,
                                    'columnIndex' => $columnIndex,
                                    'priority' => (int)$priority,
                                    'description' => htmlspecialchars($description),
                                    'writeAccess' => $hasWriteAccess
                            );
                            // Add Scheduler task information, if any
                            $taskKey = $tableName . '/' . $index;
                            if (array_key_exists($taskKey, $tasks)) {
                                $tableConfiguration['automated'] = 1;
                                $tableConfiguration['task'] = $tasks[$taskKey];
                            } else {
                                $tableConfiguration['automated'] = 0;
                                $tableConfiguration['task'] = null;
                            }
                            $configurations[] = $tableConfiguration;
                        }
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
    public function findGlobalWriteAccess()
    {

        // An admin user has full access
        if ($GLOBALS['BE_USER']->isAdmin()) {
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
                    if (!$GLOBALS['BE_USER']->check('tables_modify', $tableName)) {
                        $noAccessCount++;
                    }
                }
            }
            // If the user has no restriction, then access is full
            if ($noAccessCount === 0) {
                $hasGlobalWriteAccess = 'all';

                // Assess if user has rights to no table at all or at least to some
            } else {
                if ($noAccessCount === $numberOfTables) {
                    $hasGlobalWriteAccess = 'none';
                } else {
                    $hasGlobalWriteAccess = 'partial';
                }
            }
        }
        return $hasGlobalWriteAccess;
    }

    /**
     * Performs some processing on the "ctrl" configuration, like taking global values into account.
     *
     * @param array $configuration The external import configuration to process
     * @return array The processed configuration
     */
    protected function processCtrlConfiguration($configuration)
    {
        // If the pid is not set in the current configuration, use global storage pid
        $pid = 0;
        if (array_key_exists('pid', $configuration)) {
            $pid = (int)$configuration['pid'];
        } elseif (array_key_exists('storagePID', $this->extensionConfiguration)) {
            $pid = (int)$this->extensionConfiguration['storagePID'];
        }
        $configuration['pid'] = $pid;

        // Map old properties to new ones, to preserve backwards compatibility
        $oldPropertiesCount = 0;
        foreach (self::$renamedControlProperties as $oldName => $newName) {
            if (array_key_exists($oldName, $configuration)) {
                $configuration[$newName] = $configuration[$oldName];
                $oldPropertiesCount++;
            }
        }
        // If at least one old property was found, add an entry to the deprecation log
        if ($oldPropertiesCount > 0) {
            GeneralUtility::deprecationLog('Some old External Import properties were found in your configurations. Please use the Data Import module to check out which ones.');
        }

        return $configuration;
    }
}
