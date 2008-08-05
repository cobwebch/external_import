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
* $Id: class.tx_externalimport_ajax.php 3977 2008-07-20 17:28:23Z fsuter $
***************************************************************/

require_once(t3lib_extMgm::extPath('external_import').'class.tx_externalimport_importer.php');

/**
 * This class answers to AJAX calls from the 'external_import' extension
 *
 * @author	Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package	TYPO3
 * @subpackage	tx_externalimport
 */
class tx_externalimport_ajax {

	/**
	 * This method executes the method requested by the AJAX call and returns the result
	 * This method will not be needed anymore when switching to TYPO3 4.2
	 *
	 * @return	array	list of messages ordered by status (error, warning, success)
	 */
	public function execute() {
		$method = t3lib_div::_GP('function');
		$messages = array();

		if (!empty($method)) {

// Call method with dummy parameters

			$messages = $this->$method(array(), $this);

// Encode messages in UTF-8 to prepare for JSON encoding

			foreach ($messages as $status => $messageList) {
				$numMessages = count($messageList);
				for ($i = 0; $i < $numMessages; $i++) {
					$messages[$status][$i] = utf8_encode($messages[$status][$i]);
				}
			}
		}
		return $messages;
	}

	/**
	 * This method answers to the AJAX call and starts the synchronisation of a given table
	 *
	 * @return	array	list of messages ordered by status (error, warning, success)
	 * @return	void	(with 4.2)
	 */
	public function synchronizeExternalTable($params, &$ajaxObj) {
		$theTable = t3lib_div::_GP('table');
		$importer = t3lib_div::makeInstance('tx_externalimport_importer');
		$messages = $importer->synchronizeData($theTable);

// Pre-4.2 calling method

		if (get_class($ajaxObj) == 'tx_externalimport_ajax') {
			return $messages;
		}

// Post-4.2 calling method

		else {
			$ajaxObj->setContentFormat('json');
			foreach ($messages as $code => $messageList) {
				if (count($messageList) > 0) {
					$ajaxObj->addContent($code, $messageList);
				}
			}
		}
	}
}
?>