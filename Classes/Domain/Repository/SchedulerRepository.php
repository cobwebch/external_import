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
class Tx_ExternalImport_Domain_Repository_SchedulerRepository implements t3lib_Singleton {
	/**
	 * @var	string	Name of the related task class
	 */
	static public $taskClassName = 'tx_externalimport_autosync_scheduler_Task';

	/**
	 * List of all tasks (stored locally in case the repository is called several times)
	 *
	 * @var array
	 */
	protected $tasks = array();

	/**
	 * Local instance of the scheduler object
	 *
	 * @var	tx_scheduler
	 */
	protected $scheduler;

	/**
	 * @var string Display date format according to TYPO3 setup
	 */
	protected $dateFormat;

	public function __construct() {
		$this->scheduler = t3lib_div::makeInstance('tx_scheduler');
		$allTasks = $this->scheduler->fetchTasksWithCondition('', TRUE);
		/** @var $aTaskObject tx_scheduler_Task */
		foreach ($allTasks as $aTaskObject) {
			if (get_class($aTaskObject) == self::$taskClassName) {
				$this->tasks[] = $aTaskObject;
			}
		}

		$this->dateFormat = $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] . ' ' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'];
	}

	/**
	 * Fetches all tasks related to the external import extension
	 * The return array is structured per table/index
	 *
	 * @return array List of registered events/tasks, per table and index
	 */
	public function fetchAllTasks() {
		$taskList = array();
			/** @var $taskObject tx_externalimport_autosync_scheduler_Task */
		foreach ($this->tasks as $taskObject) {
			$key = $taskObject->table . '/' . $taskObject->index;
			$taskList[$key] = $this->assembleTaskInformation($taskObject);
		}
		return $taskList;
	}

	/**
	 * Fetches the specific task that synchronizes all tables
	 *
	 * @throws Exception
	 * @return array Information about the task, if defined
	 */
	public function fetchFullSynchronizationTask() {
			// Check all tasks object to find the one with the "all" keyword as a table
			/** @var $taskObject tx_externalimport_autosync_scheduler_Task */
		foreach ($this->tasks as $taskObject) {
			if ($taskObject->table == 'all') {
				$taskInformation = $this->assembleTaskInformation($taskObject);
				return $taskInformation;
			}
		}
		throw new Exception('No task registered for full synchronization', 1337344319);
	}

	/**
	 * Grabs the information about a given external import task and stores it into an array
	 *
	 * @param tx_externalimport_autosync_scheduler_Task $taskObject The task to handle
	 * @return array The information about the task
	 */
	protected function assembleTaskInformation(tx_externalimport_autosync_scheduler_Task $taskObject) {
		$cronCommand = $taskObject->getExecution()->getCronCmd();
		$interval = $taskObject->getExecution()->getInterval();
		$taskInformation = array(
			'uid' => $taskObject->getTaskUid(),
				// Format date as needed for display
			'nextexecution' => date($this->dateFormat, $taskObject->getExecutionTime()),
			'interval' => sprintf($GLOBALS['LANG']->sL('LLL:EXT:Resources/Private/Language/locallang.xml:number_of_seconds'), $interval),
			'croncmd' => $cronCommand,
			'frequency' => ($cronCommand == '') ? $interval : $cronCommand,
				// Format date and time as needed for form input
			'start_date' => date('m/d/Y', $taskObject->getExecution()->getStart()),
			'start_time' => date('H:i', $taskObject->getExecution()->getStart())
		);
		return $taskInformation;
	}

	/**
	 * Saves a given task
	 * If no uid is given, a new task is created
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
			// As of TYPO3 CMS 6.2, a task group must be defined
			if (method_exists($task, 'setTaskGroup')) {
				$task->setTaskGroup(0);
			}
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
	 * @param integer $uid Primary key of the task to remove
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