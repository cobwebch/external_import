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
*
* $Id$
***************************************************************/

require_once(t3lib_extMgm::extPath('gabriel', 'class.tx_gabriel_event.php'));
require_once(t3lib_extMgm::extPath('external_import', 'class.tx_externalimport_importer.php'));

/**
 * This class executes Gabriel events for automatic synchronisations of external data
 *
 * @author		Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package		TYPO3
 * @subpackage	tx_externalimport
 */
class tx_externalimport_autosync_gabriel_event extends tx_gabriel_event {
	public $extKey = 'external_import';
	protected $extConf = array(); // Extension configuration

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
		$this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extKey]);
	}

	/**
	 * This method executes the task registered in the Gabriel event
	 *
	 * @return	void
	 */
	public function execute() {
		$reportContent = '';
			// Make sure we have a language object
			// If initialised, use existing, if not, initialise it
		if (!isset($GLOBALS['LANG'])) {
			require_once(PATH_typo3 . 'sysext/lang/lang.php');
			$GLOBALS['LANG'] = t3lib_div::makeInstance('language');
			$GLOBALS['LANG']->init($GLOBALS['BE_USER']->uc['lang']);
		}
		$GLOBALS['LANG']->includeLLFile('EXT:' . $this->extKey . '/locallang.xml');
			// Load language file
		$GLOBALS['LANG']->includeLLFile('EXT:' . $this->extKey . '/autosync/locallang.xml');

			// Instantiate the import object and call appropriate method depending on command
		$importer = t3lib_div::makeInstance('tx_externalimport_importer');
			// Synchronize all tables
		if ($this->commands['sync'] == 'all') {
			$allMessages = $importer->synchronizeAllTables();
				// If necessary, prepare a report with all messages
			if (!empty($this->extConf['reportEmail'])) {
				foreach ($allMessages as $key => $messages) {
					list($table, $index) = explode('/', $key);
					$reportContent .= $this->reportForTable($table, $index, $messages);
					$reportContent .= "\n\n";
				}
			}
		}
		echo $reportContent;
	}

	/**
	 * This method writes the report for a given table
	 *
	 * @param	string		$table: name of the table
	 * @param	interger	$index: number of the synchronisation configuration
	 * @param	array		$messages: list of messages for the given table
	 * @return	string		Formatted text of the report
	 */
	protected function reportForTable($table, $index, $messages) {
		$report = sprintf($GLOBALS['LANG']->getLL('synchronizeTableX'), $table, $index) . "\n\n";
		foreach ($messages as $type => $messageList) {
			$report .= $GLOBALS['LANG']->getLL('label.' . $type) . "\n";
			if (count($messageList) == 0) {
				$report .= "\t" . $GLOBALS['LANG']->getLL('no.' . $type) . "\n";
			} else {
				foreach ($messageList as $aMessage) {
					$report .= "\t- " . $aMessage . "\n";
				}
			}
		}
		$report .= "\n";
		return $report;
	}
}
?>