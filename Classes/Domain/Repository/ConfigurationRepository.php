<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Francois Suter (typo3@cobweb.ch)
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Pseudo-repository for fetching external import configurations from the TCA
 *
 * This is not a true repository in the Extbase sense of the term, as it relies on reading its information
 * from the TCA and not a database. It also does not provide any persistence.
 *
 * @author		Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package		TYPO3
 * @subpackage	tx_externalimport
 *
 * $Id$
 */
class Tx_ExternalImport_Domain_Repository_ConfigurationRepository {
	/**
	 * Returns the "ctrl" part of the external import configuration for the given table and index
	 *
	 * @param string $table Name of the table
	 * @param string|integer $index Key of the configuration
	 * @return array The relevant TCA configuration
	 */
	public function findByTableAndIndex($table, $index) {
		if (isset($GLOBALS['TCA'][$table]['ctrl']['external'][$index])) {
			return $GLOBALS['TCA'][$table]['ctrl']['external'][$index];
		} else {
			return NULL;
		}
	}

	/**
	 * Returns the columns part of the external import configuration for the given table and index
	 *
	 * @param string $table Name of the table
	 * @param string|integer $index Key of the configuration
	 * @return array The relevant TCA configuration
	 */
	public function findColumnsByTableAndIndex($table, $index) {
		t3lib_div::loadTCA($table);
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
			$columns = NULL;
		}
		return $columns;
	}

	/**
	 * Returns all relevant external import configurations
	 *
	 * @param object $parameters List of parameters passed to the method (as stdClass object)
	 * @return array List of external import TCA configurations
	 */
	public function findByType($parameters) {
		$synchronizable = (boolean)$parameters->synchronizable;
		$configurations = array();

			// Get a list of all external import Scheduler tasks, if Scheduler is active
		$tasks = array();
		if (t3lib_extMgm::isLoaded('scheduler')) {
				/** @var $schedulerRepository Tx_ExternalImport_Domain_Repository_SchedulerRepository */
			$schedulerRepository = t3lib_div::makeInstance('Tx_ExternalImport_Domain_Repository_SchedulerRepository');
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
								// Store the base configuration
							$tableConfiguration = array(
								'id' => $tableName . '-' . $index,
								'table' => $tableName,
								'tableName' => $GLOBALS['LANG']->sL($sections['ctrl']['title']) . ' (' . $tableName . ')',
								'icon' => t3lib_iconWorks::getSpriteIconForRecord($tableName, array()),
								'index' => $index,
								'priority' => $priority,
								'description' => $description,
								'writeAccess' => $hasWriteAccess
							);
								// Add Scheduler task information, if any
							$taskKey = $tableName . '/' . $index;
							if (isset($tasks[$taskKey])) {
								$tableConfiguration['automated'] = 1;
								$tableConfiguration['task'] = $tasks[$taskKey];
							} else {
								$tableConfiguration['automated'] = 0;
								$tableConfiguration['task'] = NULL;
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
}
?>