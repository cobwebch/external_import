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
 * This class is a wrapper around the scheduling object (Gabriel or Scheduler)
 * It is not desgined to be instantiated directly, but should be derived for specific implementations
 *
 * @author		Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package		TYPO3
 * @subpackage	tx_externalimport
 *
 * $Id: class.tx_externalimport_ajax.php 18238 2009-03-24 08:01:10Z francois $
 */
abstract class tx_externalimport_autosync_wrapper {
	/**
	 * This method fetches all events/tasks related to the external import extension
	 * The return array is structured per table/index
	 *
	 * @return	array	List of registered events/tasks, per table and index
	 */
	abstract public function getAllTasks();

	/**
	 * This method saves a given event/task
	 * If no uid is given, a new event/taks is created
	 *
	 * @param	array		$taskData: list of fields to save. Must include "uid" for an existing registered task
	 * @return	boolean		True or false depending on success or failure of action
	 */
	abstract public function saveTask($taskData);
}
?>