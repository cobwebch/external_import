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

$LANG->includeLLFile('EXT:external_import/mod1/locallang.xml');
$BE_USER->modAccess($MCONF, 1);	// This checks permissions and exits if the user has no permission for entry.

/**
 * Module 'External Data Import' for the 'external_import' extension.
 *
 * @author		Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package		TYPO3
 * @subpackage	tx_externalimport
 *
 * $Id$
 */
class tx_externalimport_module1 extends t3lib_SCbase {

	/**
	 * Simple flag to store if scheduler extension is loaded or not
	 *
	 * @var boolean $hasSchedulingTool
	 */
	protected $hasSchedulingTool = FALSE;

	/**
	 * Unserialized extension configuration options
	 *
	 * @var array $extensionConfiguration
	 */
	protected $extensionConfiguration;

	/**
	 * Reference to a Scheduler object (wrapped)
	 *
	 * @var tx_externalimport_autosync_wrapper_scheduler $schedulingObject
	 */
	protected $schedulingObject;

	/**
	 * API of $this->pageRendererObject can be found at
	 *
	 * @var t3lib_PageRenderer
	 */
	protected $pageRendererObject;

	/**
	 * Initializes the Module
	 * @return	void
	 */
	function init()	{
		parent::init();
			// Check if the scheduler are available
			// (stored to avoid running the same call several times)
		$this->hasSchedulingTool = t3lib_extMgm::isLoaded('scheduler', FALSE);
			// Read the extension's configuration
		$this->extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extensionConfiguration']['external_import']);
			// Make sure about some values
		$this->extensionConfiguration['timelimit'] = intval($this->extensionConfiguration['timelimit']);
		$this->extensionConfiguration['storagePID'] = intval($this->extensionConfiguration['storagePID']);
		$this->extensionConfiguration['previewLimit'] = intval($this->extensionConfiguration['previewLimit']);
		$this->extensionConfiguration['debug'] = (boolean)$this->extensionConfiguration['debug'];
		$this->extensionConfiguration['disableLog'] = (boolean)$this->extensionConfiguration['disableLog'];
	}

	/**
	 * Adds items to the ->MOD_MENU array. Used for the function menu selector.
	 *
	 * @return	void
	 */
	function menuConfig()	{
		$this->MOD_MENU = array(
			'function' => array(
				'sync' => $GLOBALS['LANG']->getLL('function_sync'),
				'nosync' => $GLOBALS['LANG']->getLL('function_nosync'),
			)
		);
		parent::menuConfig();
	}

	/**
	 * Main function of the module
	 *
	 * @return void
	 */
	function main()	{

			// Initialize document template
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->setModuleTemplate(t3lib_extMgm::extPath('external_import') . 'Resources/Private/Templates/module.html');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->pageRendererObject = $this->doc->getPageRenderer();
		$docHeaderButtons = $this->getButtons();

			// Draw the form
		$this->doc->form = '<form action="" method="post" enctype="multipart/form-data">';

			// Base JavaScript
		$this->doc->JScode = '
			<script language="javascript" type="text/javascript">
				function jumpToUrl(URL)	{
					document.location = URL;
				}
			</script>
		';
			// Load ExtJS library
		$this->pageRendererObject->loadExtJS();
//			$this->pageRendererObject->enableExtJsDebug();

			// Dynamically define some global JS values
		$this->doc->JScodeArray[] .= '
			var LOCALAPP = {
				ajaxUrl : \'' . $GLOBALS['BACK_PATH'] . 'ajax.php\',
				ajaxTimeout : ' . (($this->extensionConfiguration['timelimit'] <= 0) ? 30000 : ($this->extensionConfiguration['timelimit'] * 1000)) . ',
				syncRunningIcon : \'<img src="' . t3lib_extMgm::extRelPath('external_import') . 'Resources/Public/Icons/refresh_animated.gif" alt="' . $GLOBALS['LANG']->getLL('running_synchronisation') . '" title="' . $GLOBALS['LANG']->getLL('running_synchronisation') . '" border="0" />\',
				syncStoppedIcon : \'<img ' . (t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/refresh_n.gif')) . ' alt="' . $GLOBALS['LANG']->getLL('synchronise') . '" title="' . $GLOBALS['LANG']->getLL('manual_sync') . '" border="0" />\',
				running : \'' . $GLOBALS['LANG']->getLL('running') . '\',
				imageExpand_add : \'<img' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/new_el.gif', 'width="18" height="12"') . ' alt="+" title="' . $GLOBALS['LANG']->getLL('add_sync') . '" />\',
				imageCollapse_add : \'<img' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/icon_fatalerror.gif', 'width="18" height="12"') . ' alt="-" title="' . $GLOBALS['LANG']->getLL('cancel_edit_sync') . '" />\',
				imageExpand_edit : \'<img' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/edit2.gif', 'width="18" height="12"') . ' alt="+" title="' . $GLOBALS['LANG']->getLL('edit_sync') . '" />\',
				imageCollapse_edit : \'<img' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/edit2_d.gif', 'width="18" height="12"') . ' alt="-" title="' . $GLOBALS['LANG']->getLL('cancel_edit_sync') . '" />\'
			};';
			// Load application specific JS
		$this->pageRendererObject->addJsFile(t3lib_extMgm::extRelPath('external_import') . 'Resources/Public/JavaScript/Application.js', 'text/javascript', FALSE);
		$this->pageRendererObject->addJsFile($GLOBALS['BACK_PATH'] . '../t3lib/js/extjs/notifications.js', 'text/javascript', FALSE);
			// Load the specific stylesheet
		$this->pageRendererObject->addCssFile(t3lib_extMgm::extRelPath('external_import') . 'Resources/Public/Stylesheet/ExternalImport.css');
			// Load some localized labels
		$labels = array(
			'external_information' => $GLOBALS['LANG']->getLL('external_information')
		);
		$this->pageRendererObject->addInlineLanguageLabelArray($labels);
			// Render content:
		$this->moduleContent();

			// Compile document
		$markers['FUNC_MENU'] = t3lib_BEfunc::getFuncMenu(0, 'SET[function]', $this->MOD_SETTINGS['function'], $this->MOD_MENU['function']);
		$markers['CONTENT'] = $this->content;

			// Build the <body> for the module
		$this->content = $this->doc->startPage($GLOBALS['LANG']->getLL('title'));
		$this->content.= $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
		$this->content.= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
	}

	/**
	 * Prints out the module HTML
	 *
	 * @return	void
	 */
	function printContent()	{
		$this->content .= $this->doc->endPage();
		echo $this->content;
	}

	/**
	 * Generates the module content
	 *
	 * @return	void
	 */
	function moduleContent()	{
		switch((string)$this->MOD_SETTINGS['function'])	{

				// List tables that receive external data from the outside
				// (i.e. cannot be synchronised from the BE)
			case 'nosync':
				$this->listOtherTables();
				break;

				// Default view is the list of all external tables that can be synchronised
			default:
				$this->listSynchronizedTables();
				break;
		}
	}


	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return	array	all available buttons as an assoc. array
	 */
	protected function getButtons()	{

		$buttons = array(
			'csh' => '',
			'shortcut' => '',
		);
			// CSH
		$buttons['csh'] = t3lib_BEfunc::cshItem('_MOD_web_func', '', $GLOBALS['BACK_PATH']);

			// Reload view
		$buttons['reload'] = '<a href="' . $GLOBALS['MCONF']['_'] . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.reload', TRUE) . '">' .
			t3lib_iconWorks::getSpriteIcon('actions-system-refresh') .
			'</a>';

			// Shortcut
		if ($GLOBALS['BE_USER']->mayMakeShortcut())	{
			$buttons['shortcut'] = $this->doc->makeShortcutIcon('', 'function', $this->MCONF['name']);
		}

		return $buttons;
	}

	/**
	 * This method lists all the tables that can be synchronised from the BE
	 * These are the tables that have a "external" part defined in the "ctrl" section of their TCA
	 * and a "connector" defined in this "external" part
	 *
	 * @return	void
	 */
	protected function listSynchronizedTables() {
		$saveResult = '';
		$deleteResult = '';
		$existingTasks = array();

			// Get an instance of the Scheduler wrapper class
		if ($this->hasSchedulingTool) {
			$this->schedulingObject = t3lib_div::makeInstance('tx_externalimport_autosync_wrapper_scheduler');

			if ($this->CMD == 'delete') {
				$deleteResult = $this->deleteTask();
			}

				// Save a task registration, if any
			$saveResult = $this->saveTask();

				// Get all the registered tasks
			$existingTasks = $this->schedulingObject->getAllTasks();
		}

			// Get list of all synchronisable tables and extract general information about them
		$externalTables = array();
		$hasSomeWriteAccess = FALSE;
		$hasAllWriteAccess = TRUE;
		foreach ($GLOBALS['TCA'] as $tableName => $sections) {
				// Check if table has external info
			if (isset($sections['ctrl']['external'])) {
					// Check if user has read rights on it
				if ($GLOBALS['BE_USER']->check('tables_select', $tableName)) {
					$externalData = $sections['ctrl']['external'];
					$hasWriteAccess = $GLOBALS['BE_USER']->check('tables_modify', $tableName);
						// This general flag must be true if user has write access
						// to at least one table
					$hasSomeWriteAccess |= $hasWriteAccess;
						// This general flag must be true only if user has write
						// access to *all* tables
					$hasAllWriteAccess &= $hasWriteAccess;
					foreach ($externalData as $index => $externalConfig) {
						if (!empty($externalConfig['connector'])) {
								// If priority is not defined, set to very low
							$priority = 1000;
							$description = '';
							if (isset($externalConfig['priority'])) {
								$priority = $externalConfig['priority'];
							}
							if (isset($externalConfig['description'])) {
								$description = $GLOBALS['LANG']->sL($externalConfig['description']);
							}
							$externalTables[] = array('tablename' => $tableName, 'index' => $index, 'priority' => $priority, 'description' => $description, 'writeAccess' => $hasWriteAccess);
						}
					}
				} else {
						// This general flag must be true only if user has write
						// access to *all* tables
					$hasAllWriteAccess &= FALSE;
				}
			}
		}

			// Sort tables by priority (lower number is highest priority)
		usort($externalTables, array('tx_externalimport_module1', 'prioritySort'));

			// Prepare table to display list of external tables
		if (count($externalTables) == 0) {
			$tableList = '<p>' . $GLOBALS['LANG']->getLL('external_tables_none') . '</p>';
		}
		else {

				// First initialise the table layout
			$tableLayout = array (
				'table' => array ('<table class="typo3-dblist">', '</table>'),
				'0' => array (
					'tr' => array('<tr class="t3-row-header">', '</tr>'),
				),
				'defRow' => array (
					'tr' => array('<tr class="db_list_normal" valign="top">', '</tr>'),
					'defCol' => array('<td>', '</td>'),
				)
			);

			$table = array();

				// First row is header row
			$tr = 0;
			$table[$tr] = array();
			$table[$tr][] = '&nbsp;'; // Table icon
			$table[$tr][] = $GLOBALS['LANG']->getLL('table'); // Table name
			$table[$tr][] = $GLOBALS['LANG']->getLL('description'); // Sync description
			$table[$tr][] = $GLOBALS['LANG']->getLL('priority'); // Priority
			$table[$tr][] = '&nbsp;'; // Action icons
			if ($hasSomeWriteAccess) {
				$table[$tr][] = '&nbsp;'; // Action result
			}
			if ($this->hasSchedulingTool) {
				$table[$tr][] = $GLOBALS['LANG']->getLL('autosync'); // Sync form
			}

				// Prepare space icon
			$spaceIcon = t3lib_iconWorks::getSpriteIcon('empty-empty', array('style' => 'background-position: 0 10px;'));;

				// Generate table row for each table
			foreach ($externalTables as $tableData) {
					// Prepare the data for each cell
				$tr++;
				$tableName = $tableData['tablename'];
				$ctrlData = $GLOBALS['TCA'][$tableName]['ctrl'];
				$tableIndex = $tableData['index'];
				$taskDataKey = $tableName . '/' . $tableIndex;
				$taskData = isset($existingTasks[$taskDataKey]) ? $existingTasks[$taskDataKey] : array();
					// Assemble the row
				$table[$tr] = array();
				$tableTitle = $GLOBALS['LANG']->sL($ctrlData['title']);
				$table[$tr][] = t3lib_iconWorks::getSpriteIconForRecord($tableName, array());
				$table[$tr][] = $tableTitle . ' (' . $tableName . ')';
				$table[$tr][] = '[' . $tableIndex . ']' . ((empty($tableData['description'])) ? '' : ' ' . htmlspecialchars($tableData['description']));
				$table[$tr][] = $tableData['priority'];
					// Action icons
				$syncIcon = $spaceIcon;
				if ($tableData['writeAccess']) {
					$syncIcon = '<span id="container' . $tr . '" onclick="syncTable(\'' . $tr . '\', \'' . $tableName . '\', \'' . $tableIndex . '\')"><img ' . (t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/refresh_n.gif')) . ' alt="' . $GLOBALS['LANG']->getLL('synchronise') . '" title="' . $GLOBALS['LANG']->getLL('manual_sync') . '" border="0" /></span>';
				}
				$elementID = 'info' . $tr;
				$infoIcon = '<img class="external-information" id="' . $elementID . '" ' . (t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/zoom2.gif')) . ' alt="' . $GLOBALS['LANG']->getLL('view_details') . '" title="' . $GLOBALS['LANG']->getLL('view_details') . '" border="0" />';
					// Assemble the external import configuration information, but keep it hidden
					// It is fetched via JavaScript upon clicking the above icon and displayed inside a MessageBox
				$infoIcon .= '<div id="' . $elementID . '-content" style="display: none;"><div class="informationBlock">' . $this->displayExternalInformation($tableData) . '</div></div>';
				$table[$tr][] = $syncIcon . $infoIcon;
					// Action result
					// Prepare only if at least one table may be synchronized
				if ($hasSomeWriteAccess) {
					$table[$tr][] = '<div id="result' . $tr . '"></div>';
				}
					// Sync form
				if ($this->hasSchedulingTool) {
					$cellContent = $this->displaySyncForm($taskData, $tableName, $tableIndex, $tableData['writeAccess']);
					$table[$tr][] = '<div id="result' . $tr . '">' . $cellContent . '</div>';
				}
			}

				// Render the table
			$tableList = $this->doc->table($table, $tableLayout);
		}

			// Assemble content
		$content = '';
			// First of all display error message if no scheduling tool is available
		if (!$this->hasSchedulingTool) {
			$content .= $this->displayMessage($GLOBALS['LANG']->getLL('autosync_error'), 2);
			$content .= $this->doc->spacer(10);
		}
			// Display the result of task deletion, if any
		if (!empty($deleteResult)) {
			$content .= $deleteResult;
		}
			// Display the result of task registration, if any
		if (!empty($saveResult)) {
			$content .= $saveResult;
		}
		$content .= '<p>' . $GLOBALS['LANG']->getLL('external_tables_intro') . '</p>';
		$content .= $this->doc->spacer(10);
		$content .= $tableList;
		$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('external_tables'), $content, 0, 1);
		$this->content .= $this->doc->spacer(10);

			// Display form for automatic synchronisation
		$this->displayAutoSyncSection(isset($existingTasks['all']) ? $existingTasks['all'] : array(), $hasAllWriteAccess);
	}

	/**
	 * This method lists tables that have an external section, but that do not rely on a connector.
	 * In such cases data is not fetched by external_import and stored into those tables. On the contrary,
	 * data is pushed towards those tables using the external_import API
	 *
	 * @return	void
	 */
	protected function listOtherTables() {

			// Get list of all non-synchronizable tables and extract general information about them
		$externalTables = array();
		foreach ($GLOBALS['TCA'] as $tableName => $sections) {
				// Check if table has external info and user has read-rights on it
			if (isset($sections['ctrl']['external']) && $GLOBALS['BE_USER']->check('tables_select', $tableName)) {
				$externalData = $sections['ctrl']['external'];
				foreach ($externalData as $index => $externalConfig) {
					if (empty($externalConfig['connector'])) {
						$description = '';
						if (isset($externalConfig['description'])) {
							$description = $GLOBALS['LANG']->sL($externalConfig['description']);
						}
						$externalTables[] = array('tablename' => $tableName, 'index' => $index, 'description' => $description);
					}
				}
			}
		}
		ksort($externalTables);

			// Prepare the list of tables
		if (count($externalTables) == 0) {
			$tableList = '<p>' . $GLOBALS['LANG']->getLL('nosync_tables_none') . '</p>';
		} else {

				// Initialise the table layout
			$tableLayout = array (
				'table' => array ('<table class="typo3-dblist">', '</table>'),
				'0' => array (
					'tr' => array('<tr class="t3-row-header">', '</tr>'),
				),
				'defRow' => array (
					'tr' => array('<tr class="db_list_normal" valign="top">', '</tr>'),
					'defCol' => array('<td>', '</td>'),
				)
			);

			$table = array();

				// First row is header row
			$tr = 0;
			$table[$tr] = array();
			$table[$tr][] = '&nbsp;'; // Table icon
			$table[$tr][] = $GLOBALS['LANG']->getLL('table'); // Table name
			$table[$tr][] = $GLOBALS['LANG']->getLL('description'); // Sync description
			$table[$tr][] = '&nbsp;'; // Info icon column

				// Generate table row for each table
			foreach ($externalTables as $tableData) {
				$tr++;
				$tableName = $tableData['tablename'];
				$tableTitle = $GLOBALS['LANG']->sL($GLOBALS['TCA'][$tableName]['ctrl']['title']);
				$table[$tr] = array();
				$table[$tr][] = t3lib_iconWorks::getSpriteIconForRecord($tableName, array());
				$table[$tr][] = $tableTitle . ' (' . $tableName . ')';
				$table[$tr][] = '[' . $tableData['index'] . ']' . ((empty($tableData['description'])) ? '' : ' '.$tableData['description']);
					// Info icon
				$elementID = 'info' . $tr;
				$infoIcon = '<img class="external-information" id="' . $elementID . '" ' . (t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/zoom2.gif')) . ' alt="' . $GLOBALS['LANG']->getLL('view_details') . '" title="' . $GLOBALS['LANG']->getLL('view_details') . '" border="0" />';
					// Assemble the external import configuration information, but keep it hidden
					// It is fetched via JavaScript upon clicking the above icon and displayed inside a MessageBox
				$infoIcon .= '<div id="' . $elementID . '-content" style="display: none;"><div class="informationBlock">' . $this->displayExternalInformation($tableData) . '</div></div>';
				$table[$tr][] = $infoIcon;
			}

				// Render the table
			$tableList = $this->doc->table($table, $tableLayout);
		}

			// Assemble content
		$content = '<p>' . $GLOBALS['LANG']->getLL('nosync_tables_intro') . '</p>';
		$content .= $this->doc->spacer(10);
		$content .= $tableList;
		$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('nosync_tables'), $content, 0, 1);
	}

	/**
	 * This method displays information and input form for setting a schedule for automatic synchronisation
	 *
	 * @param	array		$taskData: array containing the registration information, if registered
	 * @param	boolean		$hasAllWriteAccess: TRUE if user has write-access to *all* tables, FALSE otherwise
	 *
	 * @return	void
	 */
	protected function displayAutoSyncSection($taskData, $hasAllWriteAccess) {
		$content = '';
		if ($this->hasSchedulingTool) {

				// Display auto sync set up form
			$content .= '<p>' . $GLOBALS['LANG']->getLL('autosync_intro') . '</p>';
			$content .= $this->doc->spacer(5);
			$content .= $this->displaySyncForm($taskData, 'all', 0, $hasAllWriteAccess);
			$content .= $this->doc->spacer(10);

				// Add to module's output
			$this->content .= $this->doc->divider(5);
			$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('full_autosync'), $content, 0, 1);
		}
	}

	/**
	 * This method checks input data and stores a task registration if applicable
	 *
	 * @return	string	HTML to display with feedback about save process
	 */
	protected function saveTask() {
		$content = '';
			// If there was an input, register the event/task
		$inputParameters = t3lib_div::_GPmerged('tx_externalimport');
		if (count($inputParameters) > 0) {
			$hasError = false;
			$errorMessages = '';

				// Check validity of input
				// Default value is time() - 1 so that the start date is set ever so slightly
				// in the past. This will force the calculation of the next execution date
				// otherwise the current time would be used
			$startdate = (empty($inputParameters['start'])) ? time() - 1 : strtotime($inputParameters['start']);
			if ($startdate === false || $startdate === -1) {
				$errorMessages .= $this->displayMessage($GLOBALS['LANG']->getLL('error_invalid_start_date'), 3);
				$hasError = true;
			}
				// Store back the calculated timestamp
			$inputParameters['start'] = $startdate;

				// Check if a cron command was used
				// If the period does not contain five parts, assume it is a number of seconds
			$periodParts = t3lib_div::trimExplode(' ', $inputParameters['period_value'], TRUE);
			$interval = 0;
			$croncmd = '';
			if (count($periodParts) == 5) {
				$croncmd = $inputParameters['period_value'];
			} else {
				$interval = intval($inputParameters['period_value']);
				if ($interval < 1) {
					$errorMessages .= $this->displayMessage($GLOBALS['LANG']->getLL('error_invalid_frequency'), 3);
					$hasError = true;
				}
			}

				// If input was invalid, issue error and do nothing more
			if ($hasError) {
				$content .= $errorMessages;
				$content .= $this->doc->spacer(10);

				// Input is valid
			} else {
				$inputParameters['interval'] = $interval;
				$inputParameters['croncmd'] = $croncmd;

				$result = $this->schedulingObject->saveTask($inputParameters);

				if ($result) {
					$content .= $this->displayMessage($GLOBALS['LANG']->getLL('autosync_saved'), -1);
				} else {
					$content .= $this->displayMessage($GLOBALS['LANG']->getLL('autosync_save_failed'), 3);
				}
				$content .= $this->doc->spacer(10);
			}
		}
		return $content;
	}

	/**
	 * This method deletes a given task and returns information about action completion
	 *
	 * @return	string	Result message to display
	 */
	protected function deleteTask() {
		$uid = t3lib_div::_GP('uid');
		$result = $this->schedulingObject->deleteTask($uid);
		if ($result) {
			$message = $this->displayMessage($GLOBALS['LANG']->getLL('delete_done'), -1);
		} else {
			$message = $this->displayMessage($GLOBALS['LANG']->getLL('delete_failed'), 3);
		}
		return $message;
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
		}
		else {
			return ($a['priority'] < $b['priority']) ? -1 : 1;
		}
	}

	/**
	 * This method displays the synchronisation input form, for a given table and index
	 *
	 * @param array $data Array of information for the related registered event, if it exists. Pass an empty array otherwise.
	 * @param string $table Name of the table to synchronize
	 * @param mixed $index Key of the synchronization configuration
	 * @param boolean $hasWriteAccess TRUE if the user has write access to the table, FALSE otherwise
	 * @return string HTML of the form to display
	 */
	protected function displaySyncForm($data, $table, $index = 0, $hasWriteAccess = FALSE) {
		$form = '';
			 // No event registration, display a message to that effect
		if (count($data) == 0) {
			$form .= '<p>' . $GLOBALS['LANG']->getLL('no_autosync') . '</p>';

			// An event exists, display next execution time
		} else {
			$message = sprintf($GLOBALS['LANG']->getLL('next_autosync'), date('d.m.Y H:i:s', $data['nextexecution']));
			if (empty($data['croncmd'])) {
				$message .= ' ' . sprintf($GLOBALS['LANG']->getLL('frequency_seconds'), $data['interval']);
			} else {
				$message .= ' ' . sprintf($GLOBALS['LANG']->getLL('frequency_cron'), $data['croncmd']);
			}
			$form .= '<p>' . $message . '</p>';
		}
			// Display the rest of the form only if the user has write access
		if ($hasWriteAccess) {
			$idAttribute = 'syncForm_' . $table . '_' . $index;
			$form .= $this->doc->spacer(5);
				// Add an icon for toggling the add or edit form
			if (isset($data['uid'])) {
				$label = $GLOBALS['LANG']->getLL('edit_sync');
				$icon = '<img' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/edit2.gif', 'width="18" height="12"') . ' alt="+" title="' . $label . '" />';
				$action = 'edit';
			} else {
				$label = $GLOBALS['LANG']->getLL('add_sync');
				$icon = '<img' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/new_el.gif', 'width="18" height="12"') . ' alt="+" title="' . $label . '" />';
				$action = 'add';
			}
			$editIcon = '<span id="' . $idAttribute . '_container" onclick="toggleSyncForm(\'' . $idAttribute . '\', \'' . $action . '\');">';
			$editIcon .= $icon;
			$editIcon .= '</span>';
			$form .= $editIcon;
				// Add an icon for toggling edit form
			if (isset($data['uid'])) {
				$label = $GLOBALS['LANG']->getLL('delete_sync');
				$icon = '<img' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/garbage.gif', 'width="18" height="12"') . ' alt="+" />';
				$deleteIcon = '<a href="?CMD=delete&uid=' . $data['uid'] . '" onclick="return confirm(\'' . $GLOBALS['LANG']->getLL('delete_sync_confirm') . '\')" title="' . $label . '">';
				$deleteIcon .= $icon;
				$deleteIcon .= '</a>';
				$form .= $deleteIcon;
			}
				// Wrap the whole form inside a div to be able to hide it easily
			$form .= '<div id="' . $idAttribute . '_wrapper" style="display:none">';
				// Assemble the form itself
			$form .= '<form name="syncForm" id="' . $idAttribute . '" method="POST" action="">';
			$form .= '<input type="hidden" name="CMD" value="save" />';
			$form .= '<input type="hidden" name="tx_externalimport[sync]" value="' . $table . '" />';
			$form .= '<input type="hidden" name="tx_externalimport[index]" value="' . $index . '" />';
			$form .= '<input type="hidden" name="tx_externalimport[uid]" value="' . ((isset($data['uid'])) ? $data['uid'] : 0) . '" />';
			$form .= '<p><label>' . $GLOBALS['LANG']->getLL('start_date') . '</label>&nbsp;<input type="text" name="tx_externalimport[start]" size="20" value="" />&nbsp;' . $GLOBALS['LANG']->getLL('start_date_help') . '</p>';
			$form .= '<p><label>' . $GLOBALS['LANG']->getLL('frequency') . '</label>&nbsp;<input type="text" name="tx_externalimport[period_value]" size="10" value="" />&nbsp;' . $GLOBALS['LANG']->getLL('frequency_help') . '</p>';
			$form .= '<p><input type="submit" name="tx_externalimport[submit]" value="' . $GLOBALS['LANG']->getLL('set_sync') . '" /></p>';
			$form .= '</form>';
			$form .= '</div>';
		}
		return $form;
	}

	/**
	 * This method renders information about the parameters of a given synchronisation configuration
	 *
	 * @param	array	$tableData: information about the synchronisation (table, index)
	 * @return	string	HTML to display
	 */
	protected function displayExternalInformation($tableData) {
		$externalInformation = '';
			// First initialise the table layout
		$tableLayout = array (
							'table' => array ('<table border="0" cellspacing="1" cellpadding="0" class="informationTable">', '</table>'),
							'defRow' => array (
								'tr' => array('<tr class="bgColor4-20" valign="top">', '</tr>'),
								'defCol' => array('<td>', '</td>'),
							)
						);
			// Prepare ctrl information
		$externalCtrlConfiguration = $GLOBALS['TCA'][$tableData['tablename']]['ctrl']['external'][$tableData['index']];
		$table = array();
		$tr = 0;
			// Connector information
		if (isset($externalCtrlConfiguration['connector'])) {
			$table[$tr][] = $GLOBALS['LANG']->getLL('connector');
			$table[$tr][] = $externalCtrlConfiguration['connector'];
			$tr++;
			$table[$tr][] = $GLOBALS['LANG']->getLL('connector.details');
			$table[$tr][] = $this->dumpArray($externalCtrlConfiguration['parameters']);
			$tr++;
		}
			// Data information
		$table[$tr][] = $GLOBALS['LANG']->getLL('data_type');
		$table[$tr][] = $externalCtrlConfiguration['data'];
		$tr++;
		if (isset($externalCtrlConfiguration['nodetype'])) {
			$table[$tr][] = $GLOBALS['LANG']->getLL('reference_node');
			$table[$tr][] = $externalCtrlConfiguration['nodetype'];
			$tr++;
		}
		$table[$tr][] = $GLOBALS['LANG']->getLL('external_key');
		$table[$tr][] = $externalCtrlConfiguration['reference_uid'];
		$tr++;
			// PID information
		$pid = 0;
		if (isset($externalCtrlConfiguration['pid'])) {
			$pid = $externalCtrlConfiguration['pid'];
		} elseif (isset($this->extensionConfiguration['storagePID'])) {
			$pid = $this->extensionConfiguration['storagePID'];
		}
		$table[$tr][] = $GLOBALS['LANG']->getLL('storage_pid');
		$table[$tr][] = ($pid == 0) ? 0 : $this->getPageLink($pid);
		$tr++;
		$table[$tr][] = $GLOBALS['LANG']->getLL('enforce_pid');
		$table[$tr][] = (empty($externalCtrlConfiguration['enforcePid'])) ? $GLOBALS['LANG']->getLL('no') : $GLOBALS['LANG']->getLL('yes');
		$tr++;
		$table[$tr][] = $GLOBALS['LANG']->getLL('disableLog');
		if (isset($externalCtrlConfiguration['disableLog'])) {
			$value = (empty($externalCtrlConfiguration['disableLog'])) ? $GLOBALS['LANG']->getLL('no') : $GLOBALS['LANG']->getLL('yes');
		} else {
			$value = $GLOBALS['LANG']->getLL('undefined');
		}
		$table[$tr][] = $value;
		$tr++;
			// Additional fields
		$table[$tr][] = $GLOBALS['LANG']->getLL('additional_fields');
		$table[$tr][] = (empty($externalCtrlConfiguration['additional_fields'])) ? '-' : $externalCtrlConfiguration['additional_fields'];
		$tr++;
			// Control options
		$table[$tr][] = $GLOBALS['LANG']->getLL('where_clause');
		$table[$tr][] = (empty($externalCtrlConfiguration['where_clause'])) ? $GLOBALS['LANG']->getLL('none') : $externalCtrlConfiguration['where_clause'];
		$tr++;
		$table[$tr][] = $GLOBALS['LANG']->getLL('disabled_operations');
		$table[$tr][] = (empty($externalCtrlConfiguration['disabledOperations'])) ? $GLOBALS['LANG']->getLL('none') : $externalCtrlConfiguration['disabledOperations'];
		$tr++;
		$table[$tr][] = $GLOBALS['LANG']->getLL('minimum_records');
		$table[$tr][] = (empty($externalCtrlConfiguration['minimumRecords'])) ? '-' : $externalCtrlConfiguration['minimumRecords'];

			// Render general information
		$externalInformation .= '<h4>' . $GLOBALS['LANG']->getLL('general_information') . '</h4>';
		$externalInformation .= $this->doc->table($table, $tableLayout);

			// Prepare columns mapping information
		t3lib_div::loadTCA($tableData['tablename']);
		$columnsConfiguration = $GLOBALS['TCA'][$tableData['tablename']]['columns'];
		ksort($columnsConfiguration);
		$table = array();
		$tr = 0;
		foreach ($columnsConfiguration as $column => $columnData) {
			if (isset($columnData['external'][$tableData['index']])) {
				$table[$tr][] = $column;
				$table[$tr][] = $this->dumpArray($columnData['external'][$tableData['index']]);
				$tr++;
			}
		}
			// Render columns mapping information
		$externalInformation .= '<h4>' . $GLOBALS['LANG']->getLL('columns_mapping') . '</h4>';
		$externalInformation .= $this->doc->table($table, $tableLayout);
		return $externalInformation;
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
	 * This method takes a message and a severity level and returns an appropriate box
	 * ready for display
	 *
	 * @param	string		$message: the message to display
	 * @param	integer		$severity: severity of the message (-1 = ok, 0 = info, 1 = notice, 2 = warning, 3 = error)
	 * @return	string		HTML to display
	 */
	protected function displayMessage($message, $severity = 0) {
		$style = 'padding: 4px;';
		switch ($severity) {
			case -1:
				$style .= ' background-color: #0f0; color: #000;';
				break;
			case 1:
				$style .= ' background-color: #fff; color: #000;';
				break;
			case 2:
				$style .= ' background-color: #f90; color: #000;';
				break;
			case 3:
				$style .= ' background-color: #f00; color: #fff;';
				break;
			default:
				$style .= ' background-color: #6cf; color: #000;';
		}
		$messageDisplay = '<p style="' . $style . '">' . $message . '</p>';
		return $messageDisplay;
	}

    /**
     * Returns a linked icon with title from a page
     *
     * @param integer $uid ID of the page
     * @return string HTML for icon, title and link
     */
    protected function  getPageLink($uid) {
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
			$string = '<a href="#" onclick="' . htmlspecialchars($editOnClick) . '" title="' . $GLOBALS['LANG']->getLL('jump_to_page') . '">' . $elementIcon . $pageTitle . '</a>';
		}
		return $string;
    }
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/external_import/mod1/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/external_import/mod1/index.php']);
}




	// Make instance:
	/** @var $SOBE tx_externalimport_module1 */
$SOBE = t3lib_div::makeInstance('tx_externalimport_module1');
$SOBE->init();

	// Include files?
foreach ($SOBE->include_once as $INC_FILE){
	include_once($INC_FILE);
}

$SOBE->main();
$SOBE->printContent();

?>