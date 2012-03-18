<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2012 Francois Suter (Cobweb) <typo3@cobweb.ch>
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
 * This class answers to ExtDirect calls from the 'external_import' BE module
 *
 * @author		Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package		TYPO3
 * @subpackage	tx_externalimport
 *
 * $Id$
 */
class Tx_ExternalImport_ExtDirect_Server {
	/**
	 * @var array The extension's configuration
	 */
	protected $extensionConfiguration = array();

	public function __construct() {
			// Read the extension's configuration
		$this->extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extensionConfiguration']['external_import']);
	}

	/**
	 * Returns the list of all external import configurations, depending on the give flag
	 *
	 * @param boolean $isSynchronizable TRUE to get the synchronizable configurations, FALSE otherwise
	 * @return array
	 */
	public function getConfigurations($isSynchronizable) {
		$configurations = array();

		$hasAllWriteAccess = TRUE;
			// Loop on all tables and extract external_import-related information from them
		foreach ($GLOBALS['TCA'] as $tableName => $sections) {
				// Check if table has external info
			if (isset($sections['ctrl']['external'])) {
					// Check if user has read rights on it
					// If not, the table is skipped entirely
				if ($GLOBALS['BE_USER']->check('tables_select', $tableName)) {
					$externalData = $sections['ctrl']['external'];
					$hasWriteAccess = $GLOBALS['BE_USER']->check('tables_modify', $tableName);
					foreach ($externalData as $index => $externalConfig) {
							// Synchronizable tables have a connector configuration
							// Non-synchronizable tables don't
						if (
							($isSynchronizable && !empty($externalConfig['connector'])) ||
							(!$isSynchronizable && empty($externalConfig['connector']))
						) {
								// If priority is not defined, set to very low
								// NOTE: the priority doesn't matter for non-synchronizable tables
							$priority = 1000;
							$description = '';
							if (isset($externalConfig['priority'])) {
								$priority = $externalConfig['priority'];
							}
							if (isset($externalConfig['description'])) {
								$description = $GLOBALS['LANG']->sL($externalConfig['description']);
							}
							$configurations[] = array(
								'id' => $tableName . '-' . $index,
								'table' => $tableName,
								'tableName' => $GLOBALS['LANG']->sL($sections['ctrl']['title']) . ' (' . $tableName . ')',
								'icon' => t3lib_iconWorks::getSpriteIconForRecord($tableName, array()),
								'index' => $index,
								'priority' => $priority,
								'description' => $description,
								'writeAccess' => $hasWriteAccess
							);
						}
					}
				} else {
						// This general flag must be true only if user has write
						// access to *all* tables
					$hasAllWriteAccess &= FALSE;
				}
			}
		}

			// Return the results
		return array(
			'data' => $configurations
		);
	}

	/**
	 * Returns the general external import configuration (the "ctrl" part) for a given table and index
	 *
	 * @param string $table Name of the table
	 * @param string|integer $index Key of the external configuration
	 * @return string Content to display
	 */
	public function getGeneralConfiguration($table, $index) {
		$externalInformation = '<table border="0" cellspacing="1" cellpadding="0" class="informationTable">';
			// Prepare ctrl information
		$externalCtrlConfiguration = $GLOBALS['TCA'][$table]['ctrl']['external'][$index];

			// Connector information
		if (isset($externalCtrlConfiguration['connector'])) {
			$externalInformation .= '<tr class="bgColor4-20" valign="top">';
			$externalInformation .= '<td>' . $GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:connector') . '</td>';
			$externalInformation .= '<td>' . $externalCtrlConfiguration['connector'] . '</td>';
			$externalInformation .= '</tr>';
			$externalInformation .= '<tr class="bgColor4-20" valign="top">';
			$externalInformation .= '<td>' . $GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:connector.details') . '</td>';
			$externalInformation .= '<td>' . $this->dumpArray($externalCtrlConfiguration['parameters']) . '</td>';
			$externalInformation .= '</tr>';
		}
			// Data information
		$externalInformation .= '<tr class="bgColor4-20" valign="top">';
		$externalInformation .= '<td>' . $GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:data_type') . '</td>';
		$externalInformation .= '<td>' . $externalCtrlConfiguration['data'] . '</td>';
		$externalInformation .= '</tr>';
		if (isset($externalCtrlConfiguration['nodetype'])) {
			$externalInformation .= '<tr class="bgColor4-20" valign="top">';
			$externalInformation .= '<td>' . $GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:reference_node') . '</td>';
			$externalInformation .= '<td>' . $externalCtrlConfiguration['nodetype'] . '</td>';
			$externalInformation .= '</tr>';
		}
		$externalInformation .= '<tr class="bgColor4-20" valign="top">';
		$externalInformation .= '<td>' . $GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:external_key') . '</td>';
		$externalInformation .= '<td>' . $externalCtrlConfiguration['reference_uid'] . '</td>';
		$externalInformation .= '</tr>';
			// PID information
		$pid = 0;
		if (isset($externalCtrlConfiguration['pid'])) {
			$pid = $externalCtrlConfiguration['pid'];
		} elseif (isset($this->extensionConfiguration['storagePID'])) {
			$pid = $this->extensionConfiguration['storagePID'];
		}
		$externalInformation .= '<tr class="bgColor4-20" valign="top">';
		$externalInformation .= '<td>' . $GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:storage_pid') . '</td>';
		$externalInformation .= '<td>' . (($pid == 0) ? 0 : $this->getPageLink($pid)) . '</td>';
		$externalInformation .= '</tr>';
		$externalInformation .= '<tr class="bgColor4-20" valign="top">';
		$externalInformation .= '<td>' . $GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:enforce_pid') . '</td>';
		$externalInformation .= '<td>' . ((empty($externalCtrlConfiguration['enforcePid'])) ? $GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:no') : $GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:yes')) . '</td>';
		$externalInformation .= '</tr>';
		$externalInformation .= '<tr class="bgColor4-20" valign="top">';
		$externalInformation .= '<td>' . $GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:disableLog') . '</td>';
		if (isset($externalCtrlConfiguration['disableLog'])) {
			$value = ((empty($externalCtrlConfiguration['disableLog'])) ? $GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:no') : $GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:yes')) . '</td>';
		} else {
			$value = $GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:undefined') . '</td>';
		}
		$externalInformation .= '<td>' . $value . '</td>';
		$externalInformation .= '</tr>';
			// Additional fields
		$externalInformation .= '<tr class="bgColor4-20" valign="top">';
		$externalInformation .= '<td>' . $GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:additional_fields') . '</td>';
		$externalInformation .= '<td>' . ((empty($externalCtrlConfiguration['additional_fields'])) ? '-' : $externalCtrlConfiguration['additional_fields']) . '</td>';
		$externalInformation .= '</tr>';
			// Control options
		$externalInformation .= '<tr class="bgColor4-20" valign="top">';
		$externalInformation .= '<td>' . $GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:where_clause') . '</td>';
		$externalInformation .= '<td>' . ((empty($externalCtrlConfiguration['where_clause'])) ? $GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:none') : $externalCtrlConfiguration['where_clause']) . '</td>';
		$externalInformation .= '</tr>';
		$externalInformation .= '<tr class="bgColor4-20" valign="top">';
		$externalInformation .= '<td>' . $GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:disabled_operations') . '</td>';
		$externalInformation .= '<td>' . ((empty($externalCtrlConfiguration['disabledOperations'])) ? $GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:none') : $externalCtrlConfiguration['disabledOperations']) . '</td>';
		$externalInformation .= '</tr>';
		$externalInformation .= '<tr class="bgColor4-20" valign="top">';
		$externalInformation .= '<td>' . $GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:minimum_records') . '</td>';
		$externalInformation .= '<td>' . ((empty($externalCtrlConfiguration['minimumRecords'])) ? '-' : $externalCtrlConfiguration['minimumRecords']) . '</td>';
		$externalInformation .= '</tr>';
		$externalInformation .= '</table>';

		$externalInformation .= '</table>';
		return $externalInformation;
	}

	/**
	 * Returns the external import columns configuration for a given table and index
	 *
	 * @param string $table Name of the table
	 * @param string|integer $index Key of the external configuration
	 * @return string Content to display
	 */
	public function getColumnsConfiguration($table, $index) {
		$externalInformation = '';
			// Prepare ctrl information
		$externalCtrlConfiguration = $GLOBALS['TCA'][$table]['ctrl']['external'][$index];

		$externalInformation .= '<table border="0" cellspacing="1" cellpadding="0" class="informationTable">';

			// Prepare columns mapping information
		t3lib_div::loadTCA($table);
		$columnsConfiguration = $GLOBALS['TCA'][$table]['columns'];
		ksort($columnsConfiguration);
		foreach ($columnsConfiguration as $column => $columnData) {
			if (isset($columnData['external'][$index])) {
				$externalInformation .= '<tr class="bgColor4-20" valign="top">';
				$externalInformation .= '<td>' . $column . '</td>';
				$externalInformation .= '<td>' . $this->dumpArray($columnData['external'][$index]) . '</td>';
				$externalInformation .= '</tr>';
			}
		}

		$externalInformation .= '</table>';
		return $externalInformation;
	}

	/**
	 * Starts the synchronization of the given configuration (table/index)
	 *
	 * @param string $table The name of the table to synchronize
	 * @param string|integer $index Key of the external configuration
	 * @return array List of status messages
	 */
	public function launchSynchronization($table, $index) {
			/** @var $importer tx_externalimport_importer */
		$importer = t3lib_div::makeInstance('tx_externalimport_importer');
		$messages = array();

			// Synchronize the table
		$messages = $importer->synchronizeData($table, $index);
			// Check if there are too many messages, to avoid cluttering the interface
			// Remove extra messages and add warning about it
		foreach ($messages as $severity => $messageList) {
			$numMessages = count($messageList);
			if ($numMessages > 5) {
				array_splice($messageList, 5);
				$messageList[] = sprintf($GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:moreMessages'), $numMessages);
				$messages[$severity] = $messageList;
			}
		}
		return $messages;
	}

	/**
	 * Utility method used to sort ctrl sections according to the priority value in the external information block
	 *
	 * @param	array	$a: first ctrl section to compare
	 * @param	array	$b: second ctrl section to compare
	 *
	 * @return	int		1, 0 or -1 if a is smaller, equal or greater than b, respectively
	 */
	public function prioritySort($a, $b) {
		if ($a['priority'] == $b['priority']) {
			return 0;
		} else {
			return ($a['priority'] < $b['priority']) ? -1 : 1;
		}
	}

	/**
	 * Dump a PHP array to a HTML table
	 * (This is somewhat similar to t3lib_div::view_array() but with styling ;-)
	 *
	 * @param	array	$array: Array to display
	 * @return	string	HTML table assembled from array
	 */
	protected function dumpArray($array) {
		$table = '<table border="0" cellpadding="1" cellspacing="1" bgcolor="#8a8a8a">';
		foreach ($array as $key => $value) {
			$table .= '<tr class="bgColor4-20" valign="top">';
			$table .= '<td>' . $key . '</td>';
			$table .= '<td>';
			if (is_array($value)) {
				$table .= $this->dumpArray($value);
			} else {
				$table .= $value;
			}
			$table .= '</td>';
			$table .= '</tr>';
		}
		$table .= '</table>';
		return $table;
	}


	/**
	 * Returns a linked icon with title from a page
	 *
	 * @param integer $uid ID of the page
	 * @return string HTML for icon, title and link
	 */
	protected function getPageLink($uid) {
		$string = '';
		if (!empty($uid)) {
			$page = t3lib_BEfunc::getRecord('pages', $uid);
				// If the page doesn't exist, the result is null, but we need rather an empty array
			if ($page === NULL) {
				$page = array();
			}
			$pageTitle = t3lib_BEfunc::getRecordTitle('pages', $page, 1);
			$iconAltText = t3lib_BEfunc::getRecordIconAltText($page, 'pages');

				// Create icon for record
			$elementIcon = t3lib_iconWorks::getSpriteIconForRecord('pages', $page, array('title' => $iconAltText));

				// Return item with link to Web > List
			$editOnClick = "top.goToModule('web_list', '', '&id=" . $uid . "')";
			$string = '<a href="#" onclick="' . htmlspecialchars($editOnClick) . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:jump_to_page') . '">' . $elementIcon . $pageTitle . '</a>';
		}
		return $string;
	}
}
?>