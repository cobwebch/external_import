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
		$messages = array();

			// Try synchronizing the table
			// Catch the exception, if any, and issue it as a proper error message
		try {
			$messages = $importer->synchronizeData($theTable, $theIndex);
		}
		catch (Exception $e) {
			$messages[t3lib_FlashMessage::ERROR] = array();
			$messages[t3lib_FlashMessage::ERROR][] = sprintf($GLOBALS['LANG']->sL('LLL:EXT:external_import/locallang.xml:exceptionOccurred'), $e->getMessage(), $e->getCode());
		}

			// Render messages and pass them as a response
		$response = '';
		foreach ($messages as $severity => $messageList) {
			$numMessages = count($messageList);
			$originalNumMessages = $numMessages;
				// Check if there are lots of errors or warnings (which is perfectly possible)
				// We can't let too many messages through, because the AJAX response will be too large and the AJAX call will appear as having failed
				// Limit to 5 and set flag to issue additional message
			$hasTooManyMessages = FALSE;
			if (($severity == t3lib_FlashMessage::ERROR || $severity == t3lib_FlashMessage::WARNING) && ($numMessages > 5)) {
				$numMessages = 5;
				$hasTooManyMessages = TRUE;
			}
			for ($i = 0; $i < $numMessages; $i++) {
				/** @var $messageObject t3lib_FlashMessage */
				$messageObject = t3lib_div::makeInstance(
					't3lib_FlashMessage',
					$messageList[$i],
					'',
					$severity
				);
				$response .= $messageObject->render();
			}
				// If there were too many messages, issue a new message to that effect
			if ($hasTooManyMessages) {
				/** @var $messageObject t3lib_FlashMessage */
				$message = sprintf($GLOBALS['LANG']->sL('LLL:EXT:external_import/locallang.xml:moreMessages'), $originalNumMessages);
				$messageObject = t3lib_div::makeInstance(
					't3lib_FlashMessage',
					$message,
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