<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2009 Francois Suter (Cobweb) <typo3@cobweb.ch>
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
 * This class implements the abstract autosync wrapper for the Scheduler
 *
 * @author		Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package		TYPO3
 * @subpackage	tx_externalimport
 *
 * $Id$
 */
class tx_externalimport_autosync_wrapper_scheduler {
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
	 * This method fetches all tasks related to the external import extension
	 * The return array is structured per table/index
	 *
	 * @return	array	List of registered events/tasks, per table and index
	 */
	public function getAllTasks() {
		$taskList = array();
		$tasks = $this->scheduler->fetchTasksWithCondition("classname = '" . self::$taskClassName . "'", TRUE);
		foreach ($tasks as $taskObject) {
			$key = $taskObject->table;
			if ($key != 'all') {
				$key .= '/' . $taskObject->index;
			}
			$taskList[$key] = array(
								'uid' => $taskObject->getTaskUid(),
								'nextexecution' => $taskObject->getExecutionTime(),
								'interval' => $taskObject->getExecution()->getInterval(),
								'croncmd' => $taskObject->getExecution()->getCronCmd(),
								'start' => $taskObject->getExecution()->getStart(),
							);
		}
		return $taskList;
	}

	/**
	 * This method saves a given task
	 * If no uid is given, a new taks is created
	 *
	 * @param	array		$taskData: list of fields to save. Must include "uid" for an existing registered task
	 * @return	boolean		True or false depending on success or failure of action
	 */
	public function saveTask($taskData) {
		$result = FALSE;
		if (empty($taskData['uid'])) {
				// Create a new task instance and register the execution
				/**
				 * @var	tx_scheduler_Task
				 */
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
	 * This method removes the registration of a given task
	 *
	 * @param	integer		$uid: primary key of the task to remove
	 * @return	boolean		True or false depending on success or failure of action
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