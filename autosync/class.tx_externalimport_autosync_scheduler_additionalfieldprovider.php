<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Francois Suter <typo3@cobweb.ch>
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
 * Aditional fields provider class for the Scheduler
 *
 * @author		Francois Suter <typo3@cobweb.ch>
 * @package		TYPO3
 * @subpackage	tx_externalimport
 *
 * $Id$
 */
class tx_externalimport_autosync_scheduler_AdditionalFieldProvider implements tx_scheduler_AdditionalFieldProvider {
	/**
	 * Name of the additional field
	 */
	static protected $fieldName = 'syncItem';

	/**
	 * This method is used to define new fields for adding or editing a task
	 * In this case, it adds an sleep time field
	 *
	 * @param	array					$taskInfo: reference to the array containing the info used in the add/edit form
	 * @param	object					$task: when editing, reference to the current task object. Null when adding.
	 * @param	tx_scheduler_Module		$parentObject: reference to the calling object (Scheduler's BE module)
	 * @return	array					Array containg all the information pertaining to the additional fields
	 *									The array is multidimensional, keyed to the task class name and each field's id
	 *									For each field it provides an associative sub-array with the following:
	 *										['code']		=> The HTML code for the field
	 *										['label']		=> The label of the field (possibly localized)
	 *										['cshKey']		=> The CSH key for the field
	 *										['cshLabel']	=> The code of the CSH label
	 */
	public function getAdditionalFields(array &$taskInfo, $task, tx_scheduler_Module $parentObject) {

			// Initialize extra field value
		if (empty($taskInfo[self::$fieldName])) {
			if ($parentObject->CMD == 'add') {
				$taskInfo[self::$fieldName] = 'all';
			} elseif ($parentObject->CMD == 'edit') {
					// In case of edit, set to internal value if no data was submitted already
				$taskInfo[self::$fieldName] = $task->table . '/' . $task->index;
			}
		}

			// Write the code for the field
		$fieldID = 'task_' . self::$fieldName;
		$fieldCode  = '<select name="tx_scheduler[' . self::$fieldName . ']" id="' . $fieldID . '">';
		$selected = '';
		if ($taskInfo[self::$fieldName] == 'all') {
			$selected = ' selected="selected"';
		}
		$fieldCode .= '<option value="all"' . $selected . '>' . $GLOBALS['LANG']->sL('LLL:EXT:external_import/locallang.xml:all') . '</option>';
			// Loop on the TCA of all tables to find those with an external import configuration
		foreach ($GLOBALS['TCA'] as $tableName => $sections) {
			if (isset($sections['ctrl']['external'])) {
				$externalData = $sections['ctrl']['external'];
				foreach ($externalData as $index => $externalConfig) {
						// Take only synchronized tables
					if (!empty($externalConfig['connector'])) {
						$code = $tableName . '/' . $index;
						$selected = '';
						if ($taskInfo[self::$fieldName] == $code) {
							$selected = ' selected="selected"';
						}
						$label  = $GLOBALS['LANG']->sL('LLL:EXT:external_import/locallang.xml:table') . ': ' . $tableName;
						$label .= ', ' . $GLOBALS['LANG']->sL('LLL:EXT:external_import/locallang.xml:index') . ': ' . $index;
						$fieldCode .= '<option value="' . $code . '"' . $selected . '>' . $label . '</option>';
					}
				}
			}
		}
		$fieldCode .= '</select>';
		$additionalFields = array();
		$additionalFields[$fieldID] = array(
			'code'     => $fieldCode,
			'label'    => 'LLL:EXT:external_import/locallang.xml:field.' . self::$fieldName,
			'cshKey'   => '_MOD_user_txexternalimportM1',
			'cshLabel' => $fieldID
		);

		return $additionalFields;
	}

	/**
	 * This method checks any additional data that is relevant to the specific task
	 * If the task class is not relevant, the method is expected to return true
	 *
	 * @param	array					$submittedData: reference to the array containing the data submitted by the user
	 * @param	tx_scheduler_Module		$parentObject: reference to the calling object (Scheduler's BE module)
	 * @return	boolean					True if validation was ok (or selected class is not relevant), false otherwise
	 */
	public function validateAdditionalFields(array &$submittedData, tx_scheduler_Module $parentObject) {
			// Since only a valid value could be chosen from the selected, always return true
		return TRUE;
	}

	/**
	 * This method is used to save any additional input into the current task object
	 * if the task class matches
	 *
	 * @param	array				$submittedData: array containing the data submitted by the user
	 * @param	tx_scheduler_Task	$task: reference to the current task object
	 * @return	void
	 */
	public function saveAdditionalFields(array $submittedData, tx_scheduler_Task $task) {
		if ($submittedData[self::$fieldName] == 'all') {
			$task->table = 'all';
			$task->index = 0;
		} else {
			list($table, $index) = explode('/', $submittedData[self::$fieldName]);
			$task->table = $table;
			$task->index = $index;
		}
	}
}
?>