<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007 Francois Suter (Cobweb) <typo3@cobweb.ch>
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
* $Id: class.tx_externalimport_ajax.php 3684 2008-01-30 14:48:54Z fsuter $
***************************************************************/

require_once(t3lib_extMgm::extPath('gabriel','class.tx_gabriel_event.php'));
require_once(t3lib_extMgm::extPath('external_import').'class.tx_externalimport_importer.php');

/**
 * This class executes Gabriel events for automatic synchronisations of external data
 *
 * @author	Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package	TYPO3
 * @subpackage	tx_externalimport
 */
class tx_externalimport_autosync extends tx_gabriel_event {

	/**
	 * This method executes the task registered in the Gabriel event
	 *
	 * @return	void
	 */
	public function execute() {

// Get the crid for the event and extract the part after the colon (:)
// This part indicates which table to synchronise, or "all" for full synchronisation

		$crid = $this->gabriel->getEventCrid($this->eventUid);
//		list($ext, $command) = t3lib_div::trimExplode('::', $crid, 1);

// Instatiate the import object and call appropriate method depending on command

		$importer = t3lib_div::makeInstance('tx_externalimport_importer');
		if ($this->commands['sync'] == 'all') {
			$importer->synchronizeAllTables();
		}
	}
}
?>