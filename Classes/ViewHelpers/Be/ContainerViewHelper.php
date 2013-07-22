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
 * This class extends the base BE container View Helper to add specific initializations
 *
 * @author		Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package		TYPO3
 * @subpackage	tx_externalimport
 *
 * $Id$
 */
class Tx_ExternalImport_ViewHelpers_Be_ContainerViewHelper extends Tx_Fluid_ViewHelpers_Be_ContainerViewHelper {

	/**
	 * Render start page with template.php and pageTitle
	 *
	 * @param string  $pageTitle title tag of the module. Not required by default, as BE modules are shown in a frame
	 * @param boolean $enableJumpToUrl If TRUE, includes "jumpTpUrl" javascript function required by ActionMenu. Defaults to TRUE
	 * @param boolean $enableClickMenu If TRUE, loads clickmenu.js required by BE context menus. Defaults to TRUE
	 * @param boolean $loadPrototype specifies whether to load prototype library. Defaults to TRUE
	 * @param boolean $loadScriptaculous specifies whether to load scriptaculous libraries. Defaults to FALSE
	 * @param string  $scriptaculousModule additional modules for scriptaculous
	 * @param boolean $loadExtJs specifies whether to load ExtJS library. Defaults to FALSE
	 * @param boolean $loadExtJsTheme whether to load ExtJS "grey" theme. Defaults to FALSE
	 * @param string  $extJsAdapter load alternative adapter (ext-base is default adapter)
	 * @param boolean $enableExtJsDebug if TRUE, debug version of ExtJS is loaded. Use this for development only
	 * @param string $addCssFile Custom CSS file to be loaded
	 * @param string $addJsFile Custom JavaScript file to be loaded
	 * @param string $globalWriteAccess Whether uses has full access ("all"), "partial" access or none (to sync tables)
	 * @param string $view Name of the current view ("sync" or "nosync")
	 * @return string
	 * @see template
	 * @see t3lib_PageRenderer
	 */
	public function render($pageTitle = '', $enableJumpToUrl = TRUE, $enableClickMenu = TRUE, $loadPrototype = TRUE, $loadScriptaculous = FALSE, $scriptaculousModule = '', $loadExtJs = FALSE, $loadExtJsTheme = TRUE, $extJsAdapter = '', $enableExtJsDebug = FALSE, $addCssFile = NULL, $addJsFile = NULL, $globalWriteAccess = 'none', $view = 'sync') {
		$extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['external_import']);

		$doc = $this->getDocInstance();
		$pageRenderer = $doc->getPageRenderer();

			// Load ExtDirect
		$pageRenderer->addExtDirectCode(array('TYPO3.ExternalImport'));
			// Load the FitToParent ExtJS plugin
		$uxPath = $doc->backPath . '../t3lib/js/extjs/ux/';
		$pageRenderer->addJsFile($uxPath . 'Ext.ux.FitToParent.js');
		// Pass some settings to the JavaScript application
		// First calculate the time limit (which is multiplied by 1000, because JS uses milliseconds)
		// Defaults to 30 seconds
		$timeLimitConfiguration = intval($extensionConfiguration['timelimit']);
		// If the time limit is 0, duration is supposed to be unlimited. Set 1 day as arbitrary value.
		if ($timeLimitConfiguration === 0) {
			$timeLimit = 86400 * 1000;
		} else {
			$timeLimit = ($timeLimitConfiguration > 0) ? $timeLimitConfiguration * 1000 : 30000;
		}
		$pageRenderer->addInlineSettingArray(
			'external_import',
			array(
				'timelimit' => $timeLimit,
				'hasScheduler' => t3lib_extMgm::isLoaded('scheduler', FALSE),
				'globalWriteAccess' => $globalWriteAccess,
				'dateFormat' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'],
				'timeFormat' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'],
				'view' => $view
			)
		);
			// Load JS-powered flash messages library
		$pageRenderer->addJsFile($doc->backPath . '../t3lib/js/extjs/notifications.js', 'text/javascript', FALSE);
			// Load the specific language file
		$pageRenderer->addInlineLanguageLabelFile('EXT:external_import/Resources/Private/Language/locallang.xml');
		$pageRenderer->addInlineLanguageLabelFile('EXT:lang/locallang_common.xml');

		$output = parent::render($pageTitle, $enableJumpToUrl, $enableClickMenu, $loadPrototype, $loadScriptaculous, $scriptaculousModule, $loadExtJs, $loadExtJsTheme, $extJsAdapter, $enableExtJsDebug, $addCssFile, $addJsFile);
		return $output;
	}
}

?>