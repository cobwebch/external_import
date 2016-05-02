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

use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
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
     * Returns the "ctrl" part of the external import configuration for the given table and index
     *
     * @param string $table Name of the table
     * @param string|integer $index Key of the configuration
     * @return array The relevant TCA configuration
     */
    public function findByTableAndIndex($table, $index)
    {
        if (isset($GLOBALS['TCA'][$table]['ctrl']['external'][$index])) {
            return $GLOBALS['TCA'][$table]['ctrl']['external'][$index];
        } else {
            return null;
        }
    }

    /**
     * Returns the columns part of the external import configuration for the given table and index
     *
     * @param string $table Name of the table
     * @param string|integer $index Key of the configuration
     * @return array The relevant TCA configuration
     */
    public function findColumnsByTableAndIndex($table, $index)
    {
        if (isset($GLOBALS['TCA'][$table]['columns'])) {
            $columns = array();
            $columnsConfiguration = $GLOBALS['TCA'][$table]['columns'];
            ksort($columnsConfiguration);
            foreach ($columnsConfiguration as $columnName => $columnData) {
                if (isset($columnData['external'][$index])) {
                    $columns[$columnName] = $columnData['external'][$index];
                }
            }
        } else {
            $columns = null;
        }
        return $columns;
    }

    /**
     * Returns all relevant external import configurations
     *
     * @param object $parameters List of parameters passed to the method (as stdClass object)
     * @return array List of external import TCA configurations
     */
    public function findByType($parameters)
    {
        $synchronizable = (boolean)$parameters->synchronizable;
        $configurations = array();

        // Get a list of all external import Scheduler tasks, if Scheduler is active
        $tasks = array();
        if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('scheduler')) {
            /** @var $schedulerRepository SchedulerRepository */
            $schedulerRepository = GeneralUtility::makeInstance(SchedulerRepository::class);
            $tasks = $schedulerRepository->fetchAllTasks();
        }

        // Loop on all tables and extract external_import-related information from them
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
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
                                ($synchronizable && !empty($externalConfig['connector'])) ||
                                (!$synchronizable && empty($externalConfig['connector']))
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
                                    'tableName' => $GLOBALS['LANG']->sL($sections['ctrl']['title']) . ' (' . $tableName . ')',
                                    'icon' => $iconFactory->getIconForRecord($tableName, array(), Icon::SIZE_SMALL)->render(),
                                    'index' => $index,
                                    'columnIndex' => $columnIndex,
                                    'priority' => (int)$priority,
                                    'description' => htmlspecialchars($description),
                                    'writeAccess' => $hasWriteAccess
                            );
                            // Add Scheduler task information, if any
                            $taskKey = $tableName . '/' . $index;
                            if (isset($tasks[$taskKey])) {
                                $tableConfiguration['automated'] = 1;
                                $tableConfiguration['task'] = $tasks[$taskKey];
                            } else {
                                $tableConfiguration['automated'] = 0;
                                $tableConfiguration['task'] = null;
                            }
                            // Provide empty text for dummy column
                            // (see "Empty column" comment in Resources/Public/JavaScript/Application.js)
                            $tableConfiguration['dummy'] = '';
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
     * Checks if user has write access to some, all or none of the tables having an external configuration
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
            if ($noAccessCount == 0) {
                $hasGlobalWriteAccess = 'all';

                // Assess if user has rights to no table at all or at least to some
            } else {
                if ($noAccessCount == $numberOfTables) {
                    $hasGlobalWriteAccess = 'none';
                } else {
                    $hasGlobalWriteAccess = 'partial';
                }
            }
        }
        return $hasGlobalWriteAccess;
    }
}
