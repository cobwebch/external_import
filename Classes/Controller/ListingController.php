<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Francois Suter (typo3@cobweb.ch)
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Controller for the backend module
 *
 * @author		Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package		TYPO3
 * @subpackage	tx_externalimport
 *
 * $Id$
 */
class Tx_ExternalImport_Controller_ListingController extends Tx_Extbase_MVC_Controller_ActionController {

	/**
	 * Renders the list of all synchronizable tables
	 *
	 * This is pretty simple as most logic is encapsulated in an ExtJS-powered data grid
	 *
	 * @return void
	 */
	public function syncAction() {
	}

	/**
	 * Renders the list of all non-synchronizable tables
	 *
	 * This is pretty simple as most logic is encapsulated in an ExtJS-powered data grid
	 *
	 * @return void
	 */
	public function noSyncAction() {
	}

/*
	public function initializeAction() {
		$this->pageRenderer->loadExtJS();
		$this->pageRenderer->addExtDirectCode(array('TYPO3.ExternalImport'));
		$uxPath = $this->template->backPath . '../t3lib/js/extjs/ux/';
		$this->pageRenderer->addJsFile($uxPath . 'Ext.ux.FitToParent.js');
			// Pass some settings to the JavaScript application
		$this->pageRenderer->addInlineSettingArray(
			'external_import',
			array(
//				'ajaxTimeout' => (($this->extensionConfiguration['timelimit'] <= 0) ? 30000 : ($this->extensionConfiguration['timelimit'] * 1000)),
				'hasScheduler' => t3lib_extMgm::isLoaded('scheduler', FALSE)
			)
		);
			// Load application specific JS
		$this->pageRenderer->addJsFile(t3lib_extMgm::extRelPath('external_import') . 'Resources/Public/JavaScript/Application.js', 'text/javascript', FALSE);
		$this->pageRenderer->addJsFile($this->template->backPath . '../t3lib/js/extjs/notifications.js', 'text/javascript', FALSE);
			// Load the specific stylesheet
		$this->pageRenderer->addCssFile(t3lib_extMgm::extRelPath('external_import') . 'Resources/Public/StyleSheet/ExternalImport.css');
		$this->pageRenderer->addInlineLanguageLabelFile('EXT:external_import/Resources/Private/Language/locallang_module.xml');
	}
*/

	/**
	 * Renders the review module user dependent with all workspaces.
	 * The module will show all records of one workspace.
	 *
	 * @return void
	public function indexAction() {
		$wsService = t3lib_div::makeInstance('tx_Workspaces_Service_Workspaces');
		$this->view->assign('showGrid', !($GLOBALS['BE_USER']->workspace === 0 && !$GLOBALS['BE_USER']->isAdmin()));
		$this->view->assign('showAllWorkspaceTab', $GLOBALS['BE_USER']->isAdmin());
		$this->view->assign('pageUid', t3lib_div::_GP('id'));
		$this->view->assign('showLegend', !($GLOBALS['BE_USER']->workspace === 0 && !$GLOBALS['BE_USER']->isAdmin()));

		$wsList = $wsService->getAvailableWorkspaces();
		$activeWorkspace = $GLOBALS['BE_USER']->workspace;
		$performWorkspaceSwitch = FALSE;

		if (!$GLOBALS['BE_USER']->isAdmin()) {
			$wsCur = array($activeWorkspace => true);
			$wsList = array_intersect_key($wsList, $wsCur);
		} else {
			$wsList = $wsService->getAvailableWorkspaces();
			if (strlen(t3lib_div::_GP('workspace'))) {
				$switchWs = (int) t3lib_div::_GP('workspace');
				if (in_array($switchWs, array_keys($wsList)) && $activeWorkspace != $switchWs) {
					$activeWorkspace = $switchWs;
					$GLOBALS['BE_USER']->setWorkspace($activeWorkspace);
					$performWorkspaceSwitch = TRUE;
					t3lib_BEfunc::setUpdateSignal('updatePageTree');
				} elseif ($switchWs == tx_Workspaces_Service_Workspaces::SELECT_ALL_WORKSPACES) {
					$this->redirect('fullIndex');
				}
			}
		}
		$this->pageRenderer->addInlineSetting('Workspaces', 'isLiveWorkspace', ($GLOBALS['BE_USER']->workspace == 0) ? TRUE : FALSE);
		$this->view->assign('performWorkspaceSwitch', $performWorkspaceSwitch);
		$this->view->assign('workspaceList', $wsList);
		$this->view->assign('activeWorkspaceUid', $activeWorkspace);
		$this->view->assign('activeWorkspaceTitle', tx_Workspaces_Service_Workspaces::getWorkspaceTitle($activeWorkspace));
		$this->view->assign('showPreviewLink', $wsService->canCreatePreviewLink( t3lib_div::_GP('id'), $activeWorkspace));
		$GLOBALS['BE_USER']->setAndSaveSessionData('tx_workspace_activeWorkspace', $activeWorkspace);
	}
	 */

	/**
	 * Initializes the controller before invoking an action method.
	 *
	 * @return void
	protected function initializeAction() {

		$this->template->setExtDirectStateProvider();

		if (tx_Workspaces_Service_Workspaces::isOldStyleWorkspaceUsed()) {
			$message = t3lib_div::makeInstance(
				't3lib_FlashMessage',
				$GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xml:warning.oldStyleWorkspaceInUser'),
				'',
				t3lib_FlashMessage::WARNING
			);

			t3lib_FlashMessageQueue::addMessage($message);
		}

		$this->pageRenderer->loadExtJS();
		$this->pageRenderer->enableExtJSQuickTips();

		$states = $GLOBALS['BE_USER']->uc['moduleData']['Workspaces']['States'];
		$this->pageRenderer->addInlineSetting('Workspaces', 'States', $states);


			// Load  JavaScript:
		$this->pageRenderer->addExtDirectCode(array(
			'TYPO3.Workspaces'
		));

		$this->pageRenderer->addJsFile($this->backPath . '../t3lib/js/extjs/ux/flashmessages.js');
		$this->pageRenderer->addJsFile($this->backPath . '../t3lib/js/extjs/ux/Ext.grid.RowExpander.js');
		$this->pageRenderer->addJsFile($this->backPath . '../t3lib/js/extjs/ux/Ext.app.SearchField.js');
		$this->pageRenderer->addJsFile($this->backPath . '../t3lib/js/extjs/ux/Ext.ux.FitToParent.js');

		$resourcePath = t3lib_extMgm::extRelPath('workspaces') . 'Resources/Public/JavaScript/';

		$this->pageRenderer->addCssFile($resourcePath . 'gridfilters/css/GridFilters.css');
		$this->pageRenderer->addCssFile($resourcePath . 'gridfilters/css/RangeMenu.css');

		$jsFiles = array(
			'gridfilters/menu/RangeMenu.js',
			'gridfilters/menu/ListMenu.js',
			'gridfilters/GridFilters.js',
			'gridfilters/filter/Filter.js',
			'gridfilters/filter/StringFilter.js',
			'gridfilters/filter/DateFilter.js',
			'gridfilters/filter/ListFilter.js',
			'gridfilters/filter/NumericFilter.js',
			'gridfilters/filter/BooleanFilter.js',
			'gridfilters/filter/BooleanFilter.js',

			'configuration.js',
			'helpers.js',
			'actions.js',
			'component.js',
			'toolbar.js',
			'grid.js',
			'workspaces.js',
		);

		foreach ($jsFiles as $jsFile) {
			$this->pageRenderer->addJsFile($resourcePath . $jsFile);
		}
	}
	 */
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/workspaces/Classes/Controller/ReviewController.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/workspaces/Classes/Controller/ReviewController.php']);
}

?>