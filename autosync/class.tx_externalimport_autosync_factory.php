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
 * This class just implements a factory method to return the appropriate autosync wrapper
 * give a parameter
 *
 * @author		Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package		TYPO3
 * @subpackage	tx_externalimport
 *
 * $Id: class.tx_externalimport_ajax.php 18238 2009-03-24 08:01:10Z francois $
 */
class tx_externalimport_autosync_factory {

	/**
	 * This method returns a wrapper for Gabriel or the Scheduler
	 * depending on the requested type
	 *
	 * @param	string	$type: type of wrapper needed, should be "gabriel" or "scheduler"
	 * @return	object	The wrapper object
	 */
	static public function getAutosyncWrapper($type) {
		if ($type == 'gabriel') {
			require_once(t3lib_extMgm::extPath('external_import', 'autosync/class.tx_externalimport_autosync_wrapper_gabriel.php'));
			$wrapperObject = t3lib_div::makeInstance('tx_externalimport_autosync_wrapper_gabriel');
		} elseif ($type == 'scheduler') {
			require_once(t3lib_extMgm::extPath('external_import', 'autosync/class.tx_externalimport_autosync_wrapper_scheduler.php'));
			$wrapperObject = t3lib_div::makeInstance('tx_externalimport_autosync_wrapper_scheduler');
		} else {
			throw new OutOfRangeException('Invalid type given', 1253707122);
		}
		return $wrapperObject;
	}
}
?>