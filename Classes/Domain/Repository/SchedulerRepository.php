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
 * Pseudo-repository class for Scheduler tasks
 *
 * This is not a true repository from an Extbase point of view. It implemented only a few features of a complete repository.
 *
 * @author		Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package		TYPO3
 * @subpackage	tx_externalimport
 *
 * $Id$
 */
class Tx_ExternalImport_Domain_Repository_SchedulerRepository {
	/**
	 * @var	string	Name of the related task class
	 */
	static public $taskClassName = 'tx_externalimport_autosync_scheduler_Task';

	/**
	 * Local instance of the scheduler object
	 *
	 * @var	tx_scheduler
	 */
	protected $scheduler;

	public function __construct() {
		$this->scheduler = t3lib_div::makeInstance('tx_scheduler');
	}

	/**
	 * TFetches all tasks related to the external import extension
	 * The return array is structured per table/index
	 *
	 * @return array List of registered events/tasks, per table and index
	 */
	public function fetchAllTasks() {
		$taskList = array();
		$tasks = $this->scheduler->fetchTasksWithCondition("classname = '" . self::$taskClassName . "'", TRUE);
		$dateFormat = $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] . ' ' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'];
			/** @var $taskObject tx_externalimport_autosync_scheduler_Task */
		foreach ($tasks as $taskObject) {
			$key = $taskObject->table;
			if ($key != 'all') {
				$key .= '/' . $taskObject->index;
			}
			$taskList[$key] = array(
				'uid' => $taskObject->getTaskUid(),
					// Format date as needed for display
				'nextexecution' => date($dateFormat ,$taskObject->getExecutionTime()),
				'interval' => $taskObject->getExecution()->getInterval(),
				'croncmd' => $taskObject->getExecution()->getCronCmd(),
					// Format date as needed for form input
				'start' => date('Y-m-d H:i:s', $taskObject->getExecution()->getStart()),
			);
		}
		return $taskList;
	}

	/**
	 * TSaves a given task
	 * If no uid is given, a new taks is created
	 *
	 * @param array $taskData List of fields to save. Must include "uid" for an existing registered task
	 * @return boolean True or false depending on success or failure of action
	 */
	public function saveTask($taskData) {
		if (empty($taskData['uid'])) {
				// Create a new task instance and register the execution
				/** @var $task tx_scheduler_Task */
			$task = t3lib_div::makeInstance(self::$taskClassName);
			$task->registerRecurringExecution($taskData['start'], $taskData['interval'], 0, FALSE, $taskData['croncmd']);
				// Set the data specific to external import
			$task->table = $taskData['sync'];
			$task->index = $taskData['index'];
			$result = $this->scheduler->addTask($task);
		} else {
			$task = $this->scheduler->fetchTask($taskData['uid']);
				// Stop any existing execution(s)...
			$task->stop();
				/// ...and replace it(them) by a new one
			$task->registerRecurringExecution($taskData['start'], $taskData['interval'], 0, FALSE, $taskData['croncmd']);
			$result = $task->save();
		}
		return $result;
	}

	/**
	 * Removes the registration of a given task
	 *
	 * @param integer $uid Frimary key of the task to remove
	 * @return boolean True or false depending on success or failure of action
	 */
	public function deleteTask($uid) {
		$result = FALSE;
		if (!empty($uid)) {
			$task = $this->scheduler->fetchTask($uid);
				// Stop any existing execution(s) and save
			$result = $this->scheduler->removeTask($task);
		}
		return $result;
	}
}
?>