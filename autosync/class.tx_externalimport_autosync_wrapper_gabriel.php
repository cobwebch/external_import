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

require_once(t3lib_extMgm::extPath('external_import', 'autosync/class.tx_externalimport_autosync_wrapper.php'));
require_once(t3lib_extMgm::extPath('external_import', 'autosync/class.tx_externalimport_autosync_gabriel_event.php'));

/**
 * This class implements the abstract autosync wrapper for Gabriel
 *
 * @author		Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package		TYPO3
 * @subpackage	tx_externalimport
 *
 * $Id: class.tx_externalimport_ajax.php 18238 2009-03-24 08:01:10Z francois $
 */
class tx_externalimport_autosync_wrapper_gabriel extends tx_externalimport_autosync_wrapper {
	static public $eventClassName = 'tx_externalimport_autosync_gabriel_event';

	/**
	 * This method fetches all events/tasks related to the external import extension
	 * The return array is structured per table/index
	 *
	 * @return	array	List of registered events/tasks, per table and index
	 */
	public function getAllTasks() {
		$events = array();
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_gabriel', 'crid LIKE ' . $GLOBALS['TYPO3_DB']->fullQuoteStr(self::$eventClassName . '%', 'tx_gabriel'));
		if ($res) {
			while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
					// Explode the crid into its constituant parts
				$crid = '';
				$additionaParametersString = '';
				list($crid, $additionaParametersString) = t3lib_div::trimExplode('::', $row['crid']);
				$additionalParameters = t3lib_div::explodeUrl2Array($additionaParametersString);
					// Assemble array key from crid parts
				$key = $additionalParameters['sync'];
				if (!empty($additionalParameters['index'])) {
					$key .= '/' . $additionalParameters['index'];
				}
					/**
					 * Get the event object
					 *
					 * @var tx_externalimport_autosync_gabriel_event
					 */
				$eventObject = unserialize($row['serialized_event_object']);
				$events[$key] = array(
									'uid' => $row['uid'],
									'nextexecution' => $row['nextexecution'],
									'interval' => $eventObject->executionPool[0]->interval,
									'croncmd' => $eventObject->executionPool[0]->croncmd,
									'start' => $eventObject->executionPool[0]->start,
								);
			}
		}
		return $events;
	}

	/**
	 * This method saves a given event/task
	 * If no uid is given, a new event/taks is created
	 *
	 * @param	array		$taskData: list of fields to save. Must include "uid" for an existing registered task
	 * @return	boolean		True or false depending on success or failure of action
	 */
	public function saveTask($taskData) {
			// Get an instance of Gabriel
		require_once(t3lib_extMgm::extPath('gabriel', 'class.tx_gabriel.php'));
			/**
			 * @var	tx_gabriel
			 */
		$gabriel = t3lib_div::makeInstance('tx_gabriel');
		if (empty($taskData['uid'])) {
				// Create a new event instance and register the execution
			$event = t3lib_div::makeInstance('tx_externalimport_autosync_gabriel_event');
			$event->registerRecurringExecution($taskData['start'], $taskData['interval'], 0);
				// Assemble the identifier and save the event
			$crid = 'tx_externalimport_autosync_gabriel_event::sync=' . $taskData['sync'];
			if (!empty($taskData['index'])) {
				$crid .= '&index=' . $taskData['index'];
			}
			$result = $gabriel->addEvent($event, $crid);
		} else {
			$event = $gabriel->fetchEvent($taskData['uid']);
				// Stop any existing execution(s)...
			$event->stop();
				/// ...and replace it(them) by a new one
			$event->registerRecurringExecution($taskData['start'], $taskData['interval'], 0);
			$result = $event->save();
		}
		return $result;
	}
}
?>