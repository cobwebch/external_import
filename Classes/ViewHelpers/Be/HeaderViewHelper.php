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
 * This class is used to load application-specific files (JS and CSS) for the BE module
 *
 * @author		Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package		TYPO3
 * @subpackage	tx_externalimport
 *
 * $Id$
 */
class Tx_ExternalImport_ViewHelpers_Be_HeaderViewHelper extends Tx_Fluid_ViewHelpers_Be_AbstractBackendViewHelper {

	/**
	 * @var t3lib_PageRenderer
	 */
	private $pageRenderer;

	/**
	 * @return Tx_ExternalImport_ViewHelpers_Be_HeaderViewHelper
	 */
	public function __construct() {
		$this->pageRenderer = $this->getDocInstance()->getPageRenderer();
	}

	public function render() {

		$doc = $this->getDocInstance();

			// Load ExtDirect
		$this->pageRenderer->addExtDirectCode(array('TYPO3.ExternalImport'));
			// Load the FitToParent ExtJS plugin
		$uxPath = $doc->backPath . '../t3lib/js/extjs/ux/';
		$this->pageRenderer->addJsFile($uxPath . 'Ext.ux.FitToParent.js');
			// Pass some settings to the JavaScript application
		$this->pageRenderer->addInlineSettingArray(
			'external_import',
			array(
				'hasScheduler' => t3lib_extMgm::isLoaded('scheduler', FALSE)
			)
		);
			// Load application specific JS
		$this->pageRenderer->addJsFile(t3lib_extMgm::extRelPath('external_import') . 'Resources/Public/JavaScript/Application.js', 'text/javascript', FALSE);
		$this->pageRenderer->addJsFile($doc->backPath . '../t3lib/js/extjs/notifications.js', 'text/javascript', FALSE);
			// Load the specific stylesheet
		$this->pageRenderer->addCssFile(t3lib_extMgm::extRelPath('external_import') . 'Resources/Public/Stylesheet/ExternalImport.css');
		$this->pageRenderer->addInlineLanguageLabelFile('EXT:external_import/Resources/Private/Language/locallang.xml');
	}
}

?>