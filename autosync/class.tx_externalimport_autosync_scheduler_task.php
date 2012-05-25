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
 * This class executes Scheduler events for automatic synchronisations of external data
 *
 * @author		Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package		TYPO3
 * @subpackage	tx_externalimport
 *
 * $Id$
 */
class tx_externalimport_autosync_scheduler_Task extends tx_scheduler_Task {
	/**
	 * @var	string	Name of the table to synchronize ("all" for all tables)
	 */
	public $table;
	/**
	 * @var	mixed	Index of the particular synchronization
	 */
	public $index;

	/**
	 * Executes the job registered in the Scheduler task
	 *
	 * @throws Exception
	 * @return boolean
	 */
	public function execute() {
		$result = TRUE;
		$reportContent = '';

			// Instantiate the import object and call appropriate method depending on command
			/** @var $importer tx_externalimport_importer */
		$importer = t3lib_div::makeInstance('tx_externalimport_importer');
			// Get the extension's configuration from the importer object
		$extensionConfiguration = $importer->getExtensionConfiguration();
			// Synchronize all tables
		$globalStatus = 'OK';
		if ($this->table == 'all') {
			$allMessages = $importer->synchronizeAllTables();
				// If necessary, prepare a report with all messages
			if (!empty($extensionConfiguration['reportEmail'])) {
				foreach ($allMessages as $key => $messages) {
					list($table, $index) = explode('/', $key);
					$reportContent .= $importer->reportForTable($table, $index, $messages);
					$reportContent .= "\n\n";
					if (count($messages['error']) > 0) {
						$globalStatus = 'ERROR';
					} elseif (count($messages['warning']) > 0) {
						$globalStatus = 'WARNING';
					}
				}
					// Assemble the subject and send the mail
				$subject = (empty($extensionConfiguration['reportSubject'])) ? '' : $extensionConfiguration['reportSubject'];
				$subject .= ' [' . $globalStatus . '] ' . 'Full synchronization';
				$importer->sendMail($subject, $reportContent);
			}
		} else {
			$messages = $importer->synchronizeData($this->table, $this->index);
				// If necessary, prepare a report with all messages
			if (!empty($extensionConfiguration['reportEmail'])) {
				$reportContent .= $importer->reportForTable($this->table, $this->index, $messages);
				$reportContent .= "\n\n";
				if (count($messages['error']) > 0) {
					$globalStatus = 'ERROR';
				} elseif (count($messages['warning']) > 0) {
					$globalStatus = 'WARNING';
				}
					// Assemble the subject and send the mail
				$subject = (empty($extensionConfiguration['reportSubject'])) ? '' : $extensionConfiguration['reportSubject'];
				$subject .= ' [' . $globalStatus . '] ' . 'Synchronization of table ' . $this->table . ', index ' . $this->index;
				$importer->sendMail($subject, $reportContent);
			}
		}
			// If any warning or error happened, throw an exception
		if ($globalStatus != 'OK') {
			throw new Exception('One or more errors or warnings happened. Please consult the log.', 1258116760);
		}
		return $result;
	}

	/**
	 * This method returns the synchronized table and index as additional information
	 *
	 * @return	string	Information to display
	 */
	public function getAdditionalInformation() {
		$info = '';
		if ($this->table == 'all') {
			$info = $GLOBALS['LANG']->sL('LLL:EXT:external_import/locallang.xml:allTables');
		} else {
			$info = sprintf($GLOBALS['LANG']->sL('LLL:EXT:external_import/locallang.xml:tableAndIndex'), $this->table, $this->index);
		}
		return $info;
	}
}
?>