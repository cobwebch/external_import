<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2011 Francois Suter (Cobweb) <typo3@cobweb.ch>
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
 * This class answers to AJAX calls from the 'external_import' extension
 *
 * @author		Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package		TYPO3
 * @subpackage	tx_externalimport
 *
 * $Id$
 */
class tx_externalimport_ajax {

	/**
	 * This method answers to the AJAX call and starts the synchronisation of a given table
	 *
	 * @param	array		$params: empty array passed by TYPO3's AJAX dispatcher
	 * @param	TYPO3AJAX	$ajaxObj: back-reference to the calling oject
	 * @return	void		(with 4.2)
	 */
	public function synchronizeExternalTable($params, &$ajaxObj) {
		$theTable = t3lib_div::_GP('table');
		$theIndex = t3lib_div::_GP('index');
		$importer = t3lib_div::makeInstance('tx_externalimport_importer');
		$messages = $importer->synchronizeData($theTable, $theIndex);
			// Render messages and pass them as a response
		$response = '';
		foreach ($messages as $severity => $messageList) {
			foreach ($messageList as $aMessage) {
				/** @var $messageObject t3lib_FlashMessage */
				$messageObject = t3lib_div::makeInstance(
					't3lib_FlashMessage',
					$aMessage,
					'',
					$severity
				);
				$response .= $messageObject->render();
			}
		}
		$ajaxObj->setContentFormat('json');
		$ajaxObj->setContent(array('content' => $response));
	}
}
?>