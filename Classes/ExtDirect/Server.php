<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2012 Francois Suter (Cobweb) <typo3@cobweb.ch>
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
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * This class answers to ExtDirect calls from the 'external_import' BE module
 *
 * @author		Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package		TYPO3
 * @subpackage	tx_externalimport
 */
class Tx_ExternalImport_ExtDirect_Server {
	/**
	 * @var array The extension's configuration
	 */
	protected $extensionConfiguration = array();
	/**
	 * @var Tx_ExternalImport_Domain_Repository_ConfigurationRepository Pseudo-repository used to read TCA configurations
	 */
	protected $configurationRepository;
	/**
	 * @var null|tx_scheduler Scheduler object (if extension is installed)
	 */
	protected $scheduler = NULL;

	public function __construct() {
			// Read the extension's configuration
		$this->extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['external_import']);
			// Create an instance of the configuration repository
		$this->configurationRepository = t3lib_div::makeInstance('Tx_ExternalImport_Domain_Repository_ConfigurationRepository');
			// Get an instance of the scheduler (if available)
		if (t3lib_extMgm::isLoaded('scheduler')) {
			$this->scheduler = t3lib_div::makeInstance('tx_scheduler');
		}
	}

	/**
	 * Returns the list of all external import configurations, depending on the give flag
	 *
	 * @param boolean $isSynchronizable TRUE to get the synchronizable configurations, FALSE otherwise
	 * @return array
	 */
	public function getConfigurations($isSynchronizable) {
		return array(
			'data' => $this->configurationRepository->findByType($isSynchronizable)
		);
	}

	/**
	 * Returns the general external import configuration (the "ctrl" part) for a given table and index
	 *
	 * @param string $table Name of the table
	 * @param string|integer $index Key of the external configuration
	 * @return string Content to display
	 */
	public function getGeneralConfiguration($table, $index) {
		$externalInformation = '';
			// Get the ctrl information
		$externalCtrlConfiguration = $this->configurationRepository->findByTableAndIndex($table, $index);

		if (is_array($externalCtrlConfiguration)) {
				// Prepare the display
			$externalInformation .= '<table class="informationTable">';
			// Connector information
			if (isset($externalCtrlConfiguration['connector'])) {
				$externalInformation .= '<tr>';
				$externalInformation .= '<td>' . $GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:connector') . '</td>';
				$externalInformation .= '<td>' . htmlspecialchars($externalCtrlConfiguration['connector']) . '</td>';
				$externalInformation .= '</tr>';
				// For connector details, we call the processParameters hook from the importer class,
				// in order to show processed parameters to the user.
				$processedParameters = array();
				if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['external_import']['processParameters'])) {
					// The hook needs an instance of the importer class
					/** @var tx_externalimport_importer $importer */
					$importer = t3lib_div::makeInstance('tx_externalimport_importer');
					$importer->setTableName($table);
					$importer->setIndex($index);
					foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['external_import']['processParameters'] as $className) {
						$preProcessor = t3lib_div::getUserObj($className);
						$processedParameters = $preProcessor->processParameters($externalCtrlConfiguration['parameters'], $importer);
					}
				}
				$externalInformation .= '<tr>';
				$externalInformation .= '<td>' . $GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:connector.details') . '</td>';
				$externalInformation .= '<td>' . $this->dumpTwinArrays($externalCtrlConfiguration['parameters'], $processedParameters) . '</td>';
				$externalInformation .= '</tr>';
			}
				// Data information
			$externalInformation .= '<tr>';
			$externalInformation .= '<td>' . $GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:data_type') . '</td>';
			$externalInformation .= '<td>' . htmlspecialchars($externalCtrlConfiguration['data']) . '</td>';
			$externalInformation .= '</tr>';
				// Custom data handler
			if (isset($externalCtrlConfiguration['dataHandler'])) {
				$externalInformation .= '<tr>';
				$externalInformation .= '<td>' . $GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:data_handler') . '</td>';
				$error = '';
				try {
					$this->verifyCustomDataHandler($externalCtrlConfiguration['dataHandler']);
				}
				catch (Exception $e) {
						/** @var $flashMessage t3lib_FlashMessage */
					$flashMessage = t3lib_div::makeInstance(
						't3lib_FlashMessage',
						$e->getMessage(),
						'',
						t3lib_FlashMessage::ERROR
					);
					$error = $flashMessage->render();
				}
				$externalInformation .= '<td>' . htmlspecialchars($externalCtrlConfiguration['dataHandler']) . ((empty($error)) ? '' : $error) . '</td>';
				$externalInformation .= '</tr>';
			}
				// XML-related configuration
			if (isset($externalCtrlConfiguration['nodetype'])) {
				$externalInformation .= '<tr>';
				$externalInformation .= '<td>' . $GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:reference_node') . '</td>';
				$externalInformation .= '<td>' . htmlspecialchars($externalCtrlConfiguration['nodetype']) . '</td>';
				$externalInformation .= '</tr>';
			}
			if (isset($externalCtrlConfiguration['namespaces']) && is_array($externalCtrlConfiguration['namespaces']) && count($externalCtrlConfiguration['namespaces']) > 0) {
				$externalInformation .= '<tr>';
				$externalInformation .= '<td>' . $GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:namespaces') . '</td>';
				$externalInformation .= '<td>' . $this->dumpArray($externalCtrlConfiguration['namespaces']) . '</td>';
				$externalInformation .= '</tr>';
			}
			$externalInformation .= '<tr>';
			$externalInformation .= '<td>' . $GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:external_key') . '</td>';
			$externalInformation .= '<td>' . htmlspecialchars($externalCtrlConfiguration['reference_uid']) . '</td>';
			$externalInformation .= '</tr>';
				// PID information
			$pid = 0;
			if (isset($externalCtrlConfiguration['pid'])) {
				$pid = $externalCtrlConfiguration['pid'];
			} elseif (isset($this->extensionConfiguration['storagePID'])) {
				$pid = $this->extensionConfiguration['storagePID'];
			}
			$externalInformation .= '<tr>';
			$externalInformation .= '<td>' . $GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:storage_pid') . '</td>';
			$externalInformation .= '<td>' . (($pid == 0) ? 0 : $this->getPageLink($pid)) . '</td>';
			$externalInformation .= '</tr>';
			$externalInformation .= '<tr>';
			$externalInformation .= '<td>' . $GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:enforce_pid') . '</td>';
			$externalInformation .= '<td>' . ((empty($externalCtrlConfiguration['enforcePid'])) ? $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xml:no') : $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xml:yes')) . '</td>';
			$externalInformation .= '</tr>';
			$externalInformation .= '<tr>';
			$externalInformation .= '<td>' . $GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:disableLog') . '</td>';
			if (isset($externalCtrlConfiguration['disableLog'])) {
				$value = ((empty($externalCtrlConfiguration['disableLog'])) ? $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xml:no') : $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xml:yes'));
			} else {
				$value = $GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:undefined');
			}
			$externalInformation .= '<td>' . $value . '</td>';
			$externalInformation .= '</tr>';
			// Additional fields
			$externalInformation .= '<tr>';
			$externalInformation .= '<td>' . $GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:additional_fields') . '</td>';
			$externalInformation .= '<td>' . ((empty($externalCtrlConfiguration['additional_fields'])) ? '-' : htmlspecialchars($externalCtrlConfiguration['additional_fields'])) . '</td>';
			$externalInformation .= '</tr>';
			// Control options
			$externalInformation .= '<tr>';
			$externalInformation .= '<td>' . $GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:where_clause') . '</td>';
			$externalInformation .= '<td>' . ((empty($externalCtrlConfiguration['where_clause'])) ? '-' : htmlspecialchars($externalCtrlConfiguration['where_clause'])) . '</td>';
			$externalInformation .= '</tr>';
			$externalInformation .= '<tr>';
			$externalInformation .= '<td>' . $GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:disabled_operations') . '</td>';
			$externalInformation .= '<td>' . ((empty($externalCtrlConfiguration['disabledOperations'])) ? $GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:none') : htmlspecialchars($externalCtrlConfiguration['disabledOperations'])) . '</td>';
			$externalInformation .= '</tr>';
			$externalInformation .= '<tr>';
			$externalInformation .= '<td>' . $GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:minimum_records') . '</td>';
			$externalInformation .= '<td>' . ((empty($externalCtrlConfiguration['minimumRecords'])) ? '-' : intval($externalCtrlConfiguration['minimumRecords'])) . '</td>';
			$externalInformation .= '</tr>';
			$externalInformation .= '<tr>';
			$externalInformation .= '<td>' . $GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:clear_cache') . '</td>';
			$externalInformation .= '<td>' . ((empty($externalCtrlConfiguration['clearCache'])) ? '-' : htmlspecialchars($externalCtrlConfiguration['clearCache'])) . '</td>';
			$externalInformation .= '</tr>';
			if (isset($externalCtrlConfiguration['useColumnIndex'])) {
				$columnIndex = $externalCtrlConfiguration['useColumnIndex'];
			} else {
				$columnIndex = $GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:undefined');
			}
			$externalInformation .= '<tr>';
			$externalInformation .= '<td>' . $GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:columnIndex') . '</td>';
			$externalInformation .= '<td>' . htmlspecialchars($columnIndex) . '</td>';
			$externalInformation .= '</tr>';
			$externalInformation .= '</table>';
		}
		return $externalInformation;
	}

	/**
	 * Returns the external import columns configuration for a given table and index
	 *
	 * @param string $table Name of the table
	 * @param string|integer $index Key of the external configuration
	 * @return string Content to display
	 */
	public function getColumnsConfiguration($table, $index) {
		$externalInformation = '';
		$columns = $this->configurationRepository->findColumnsByTableAndIndex($table, $index);

		if (is_array($columns)) {
			$externalInformation .= '<table class="informationTable">';
			foreach ($columns as $columnName => $columnData) {
				$externalInformation .= '<tr>';
				$externalInformation .= '<td>' . $columnName . '</td>';
				$externalInformation .= '<td>' . $this->dumpArray($columnData) . '</td>';
				$externalInformation .= '</tr>';
			}

			$externalInformation .= '</table>';
		}
		return $externalInformation;
	}

	/**
	 * Fetches information about the "all" synchronization task, if defined, and returns it
	 *
	 * @return array Task information
	 */
	public function getFullSynchronizationTask() {
		$fullSyncTaskInformation = array();

			// Find out if a Scheduler task has been registered for all tables
		if (t3lib_extMgm::isLoaded('scheduler', FALSE)) {
				/** @var $schedulerRepository Tx_ExternalImport_Domain_Repository_SchedulerRepository */
			$schedulerRepository = t3lib_div::makeInstance('Tx_ExternalImport_Domain_Repository_SchedulerRepository');
			try {
				$task = $schedulerRepository->fetchFullSynchronizationTask();
					// Create a fake configuration and add the task to it
				$fullSyncTaskInformation['table'] = 'all';
				$fullSyncTaskInformation['index'] = 0;
				$fullSyncTaskInformation['task'] = $task;
				$fullSyncTaskInformation['id'] = $task['uid'];
			}
			catch (Exception $e) {
				// Nothing to do
			}
		}
		return $fullSyncTaskInformation;
	}
	/**
	 * Starts the synchronization of the given configuration (table/index)
	 *
	 * @param string $table The name of the table to synchronize
	 * @param string|integer $index Key of the external configuration
	 * @return array List of status messages
	 */
	public function launchSynchronization($table, $index) {
			/** @var $importer tx_externalimport_importer */
		$importer = t3lib_div::makeInstance('tx_externalimport_importer');

			// Synchronize the table
		$messages = $importer->synchronizeData($table, $index);
			// Check if there are too many messages, to avoid cluttering the interface
			// Remove extra messages and add warning about it
		foreach ($messages as $severity => $messageList) {
			$numMessages = count($messageList);
			if ($numMessages > 5) {
				array_splice($messageList, 5);
				$messageList[] = sprintf($GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:moreMessages'), $numMessages);
				$messages[$severity] = $messageList;
			}
		}
		return $messages;
	}

	/**
	 * Processes the automate synchronization form
	 *
	 * @formHandler
	 * @param array $formData Submitted form data
	 * @return array Response
	 */
	public function saveSchedulerTask($formData) {
			// Exit early if the scheduler extension is not installed, as it is impossible to save tasks in such a case
			// Normally this should not happen, as the BE module should not offer the possibility to trigger such an action
			// without the Scheduler being available
		if ($this->scheduler === NULL) {
			$response = array(
				'succes' => FALSE,
				'errors' => array(
					'scheduler' => $GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:autosync_noscheduler')
				)
			);
			return $response;
		}

			// Initialize the response array
		$response = array(
			'errors' => array()
		);
		$success = TRUE;
			// Handle the frequency
			// Frequency cannot be empty
		$interval = 0;
		$cronCommand = '';
		if ($formData['frequency'] == '') {
			$success = FALSE;
			$response['errors']['scheduler'] = $GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:error_invalid_data');
			$response['errors']['frequency'] = $GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:error_empty_frequency');

			// Check validity of frequency. It must be either a number or a cron command
		} else {
				// Try interpreting the frequency as a cron command
			try {
				tx_scheduler_CronCmd_Normalize::normalize($formData['frequency']);
				$cronCommand = $formData['frequency'];
			}
				// If the cron command was invalid, we may still have a valid frequency in seconds
			catch (Exception $e) {
					// Check if the frequency is a valid number
					// If yes, assume it is a frequency in seconds, and unset cron error code
				if (is_numeric($formData['frequency'])) {
					$interval = intval($formData['frequency']);
				} else {
					$success = FALSE;
					$response['errors']['scheduler'] = $GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:error_invalid_data');
					$response['errors']['frequency'] = sprintf(
						$GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:error_wrong_frequency'),
						$e->getMessage()
					);
				}
			}
		}
			// Handle the start date
			// If date is empty, use time() - 1 as default, in order to trigger calculation of
			// soonest possible next execution date
		if (empty($formData['start_date'])) {
			$startDate = time() - 1;

			// Otherwise interpret date using strotime()
		} else {
			$startDateString = $formData['start_date'];
				// If the time is not empty, add it to the string to interpret
			if (!empty($formData['start_time'])) {
				$startDateString .= ' ' . $formData['start_time'];
			}
			$startDate = strtotime($startDateString);
				// If the date is invalid, log an error
			if ($startDate === FALSE) {
				$success = FALSE;
				$response['errors']['scheduler'] = $GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:error_invalid_data');
				$response['errors']['start_date'] = $GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:error_invalid_start_date');
			}
		}

			// If successful so far, save the Scheduler task (create or update)
		if ($success) {
			$data = array(
				'table' => $formData['table'],
				'index' => $formData['index'],
				'interval' => $interval,
				'croncmd' => $cronCommand,
				'start' => $startDate
			);
			if (empty($formData['uid'])) {
				try {
					$this->createTask($data);
				}
				catch (Exception $e) {
					$success = FALSE;
					$response['errors']['scheduler'] = $GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:autosync_save_failed');
				}
			} else {
				$data['uid'] = $formData['uid'];
				try {
					$this->updateTask($data);
				}
				catch (Exception $e) {
					$success = FALSE;
					$response['errors']['scheduler'] = $GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:autosync_save_failed');
				}
			}
		}
		$response['success'] = $success;
		return $response;
	}

	/**
	 * Creates a new task and registers it with the Scheduler
	 *
	 * @param array $data Necessary information about the task
	 * @return void
	 * @throws Exception
	 */
	protected function createTask($data) {
			// Create a new task instance and register the execution
			/** @var $task tx_externalimport_autosync_scheduler_Task */
		$task = t3lib_div::makeInstance('tx_externalimport_autosync_scheduler_Task');
		$task->registerRecurringExecution($data['start'], $data['interval'], 0, FALSE, $data['croncmd']);
			// Set the data specific to external import
		$task->table = $data['table'];
		$task->index = $data['index'];
		// As of TYPO3 CMS 6.2, a task group must be defined
		if (method_exists($task, 'setTaskGroup')) {
			$task->setTaskGroup(0);
		}
			// Schedule the task
		$result = $this->scheduler->addTask($task);
		if (!$result) {
			throw new Exception('Task could not be added to the Scheduler', 1334948634);
		}
	}

	/**
	 * Updates an existing task object
	 *
	 * @param array $data Necessary information about the task
	 * @return void
	 * @throws Exception
	 */
	protected function updateTask($data) {
			// Get the corresponding task object
		$task = $this->scheduler->fetchTask($data['uid']);
			// Stop any existing execution...
		$task->stop();
			/// ...and replace it by a new one
		$task->registerRecurringExecution($data['start'], $data['interval'], 0, FALSE, $data['croncmd']);
		$result = $task->save();
		if (!$result) {
			throw new Exception('Task could not be modified', 1334948921);
		}
	}

	/**
	 * Removes the requested Scheduler task
	 *
	 * @param integer $taskId Id of the task to remove
	 * @return array Response
	 */
	public function deleteSchedulerTask($taskId) {
			// Exit early if the scheduler extension is not installed, as it is impossible to save tasks in such a case
			// Normally this should not happen, as the BE module should not offer the possibility to trigger such an action
			// without the Scheduler being available
		if ($this->scheduler === NULL) {
			$response = array(
				'succes' => FALSE,
				'errors' => array(
					'scheduler' => $GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:autosync_noscheduler')
				)
			);
			return $response;
		}

			// Initialize the response array
		$response = array(
			'errors' => array()
		);
		if (empty($taskId)) {
			$success = FALSE;
		} else {
				// Get the corresponding task object
			try {
				$task = $this->scheduler->fetchTask($taskId);
					// Remove the task from the database
				$success = $this->scheduler->removeTask($task);
			}
			catch (Exception $e) {
				$success = FALSE;
			}
		}
		$response['success'] = $success;
		return $response;
	}

	/**
	 * Utility method used to sort ctrl sections according to the priority value in the external information block
	 *
	 * @param array	$a First ctrl section to compare
	 * @param array	$b Second ctrl section to compare
	 * @return integer 1, 0 or -1 if a is smaller, equal or greater than b, respectively
	 */
	public function prioritySort($a, $b) {
		if ($a['priority'] == $b['priority']) {
			return 0;
		} else {
			return ($a['priority'] < $b['priority']) ? -1 : 1;
		}
	}

	/**
	 * Dumps a PHP array to a HTML table.
	 *
	 * This is somewhat similar to t3lib_utility_Debug::view_array() but without the ugly red font.
	 *
	 * @param array $array Array to display
	 * @return string HTML table assembled from array
	 */
	protected function dumpArray($array) {
		$table = '<table>';
		foreach ($array as $key => $value) {
			$table .= '<tr>';
			$table .= '<td>' . htmlspecialchars($key) . '</td>';
			$table .= '<td>';
			if (is_array($value)) {
				$table .= $this->dumpArray($value);
			} else {
				$table .= htmlspecialchars($value);
			}
			$table .= '</td>';
			$table .= '</tr>';
		}
		$table .= '</table>';
		return $table;
	}

	/**
	 * Dumps two related PHP arrays to a HTML table.
	 *
	 * This is similar to dumpArray(), but using two arrays with the same keys.
	 * Data from the second array is written alongside data from the first array,
	 * with some highlighting in the markup.
	 *
	 * @param array $referenceArray Array to display
	 * @param array $copyArray Related array
	 * @return string HTML table assembled from array
	 */
	protected function dumpTwinArrays($referenceArray, $copyArray) {
		$table = '<table>';
		foreach ($referenceArray as $key => $value) {
			$table .= '<tr>';
			$table .= '<td>' . htmlspecialchars($key) . '</td>';
			$table .= '<td>';
			if (is_array($value)) {
				$table .= $this->dumpTwinArrays(
					$value,
					(isset($copyArray[$key])) ? $copyArray[$key] : array()
				);
			} else {
				$cellContent = htmlspecialchars($value);
				// Add related value, if it exists and only if it is different (avoid cluttering display)
				if (isset($copyArray[$key]) && $copyArray[$key] != $value) {
					$cellContent .= '<br><em>' . htmlspecialchars($copyArray[$key]) . '</em>';
				}
				$table .= $cellContent;
			}
			$table .= '</td>';
			$table .= '</tr>';
		}
		$table .= '</table>';
		return $table;
	}

	/**
	 * Verifies if the custom handler is registered and meets expectations
	 *
	 * This method returns nothing. We are interested only in the exceptions it may throw
	 *
	 * @param string $class Name of the d
	 * @throws Exception
	 */
	protected function verifyCustomDataHandler($class) {
			// Check if the class exists
		if (class_exists($class)) {
				// Instantiate the custom handler
			$dataHandler = t3lib_div::makeInstance($class);
				// Verify that it implements the required interface
			if (!($dataHandler instanceof tx_externalimport_dataHandler)) {
				throw new Exception(
					$GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:data_handler_wrong'),
					1339271389
				);
			}

			// If the class does not exist, throw an exception
		} else {
			throw new Exception(
				$GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:data_handler_noclass'),
				1339271389
			);
		}
	}

	/**
	 * Returns a linked icon with title from a page
	 *
	 * @param integer $uid ID of the page
	 * @return string HTML for icon, title and link
	 */
	protected function getPageLink($uid) {
		$string = '';
		if (!empty($uid)) {
			$page = t3lib_BEfunc::getRecord('pages', $uid);
				// If the page doesn't exist, the result is null, but we need rather an empty array
			if ($page === NULL) {
				$page = array();
			}
			$pageTitle = t3lib_BEfunc::getRecordTitle('pages', $page, 1);
			$iconAltText = t3lib_BEfunc::getRecordIconAltText($page, 'pages');

				// Create icon for record
			$elementIcon = t3lib_iconWorks::getSpriteIconForRecord('pages', $page, array('title' => $iconAltText));

				// Return item with link to Web > List
			$editOnClick = "top.goToModule('web_list', '', '&id=" . $uid . "')";
			$string = '<a href="#" onclick="' . htmlspecialchars($editOnClick) . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:jump_to_page') . '">' . $elementIcon . $pageTitle . '</a>';
		}
		return $string;
	}
}
?>