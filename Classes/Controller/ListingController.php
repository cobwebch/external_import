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
	 * @var Tx_ExternalImport_Domain_Repository_ConfigurationRepository
	 */
	protected $configurationRepository;

	/**
	 * @var string Name of the refresh icon (see self::initializeAction())
	 */
	protected $refreshIcon = 'actions-system-refresh';

	/**
	 * Injects an instance of the configuration repository
	 *
	 * @param Tx_ExternalImport_Domain_Repository_ConfigurationRepository $configurationRepository
	 * @return void
	 */
	public function injectConfigurationRepository(Tx_ExternalImport_Domain_Repository_ConfigurationRepository $configurationRepository) {
		$this->configurationRepository = $configurationRepository;
	}

	/**
	 * Initializes the view before invoking an action method.
	 *
	 * Override this method to solve assign variables common for all actions
	 * or prepare the view in another way before the action is called.
	 *
	 * @param Tx_Extbase_MVC_View_ViewInterface $view The view to be initialized
	 * @return void
	 * @api
	 */
	protected function initializeView(Tx_Extbase_MVC_View_ViewInterface $view) {
			// If the Scheduler is loaded, check full write access rights
			// (i.e. if user has write-rights to every table with external data)
		if (t3lib_extMgm::isLoaded('scheduler', FALSE)) {
			$globalWriteAccess = $this->configurationRepository->findGlobalWriteAccess();

			// If the Scheduler is not loaded, no sync can be done or defined anyway
		} else {
			$globalWriteAccess = 'none';
		}
		$view->assign('globalWriteAccess', $globalWriteAccess);
		$view->assign('view', strtolower($this->request->getControllerActionName()));
			// If TYPO3 version is lower then 4.7, use the old icon name for refresh
			// (this is necessary due to the way the Fluid BE View Helper for icon buttons was coded)
		if (t3lib_utility_VersionNumber::convertVersionNumberToInteger(TYPO3_branch) < '4007000') {
			$this->refreshIcon = 'refresh_n';
		}
		$view->assign('refresh_icon', $this->refreshIcon);
	}

	/**
	 * Renders the list of all synchronizable tables
	 *
	 * All logic is encapsulated in an ExtJS-powered data grid
	 *
	 * @return void
	 */
	public function syncAction() {
	}

	/**
	 * Renders the list of all non-synchronizable tables
	 *
	 * All logic is encapsulated in an ExtJS-powered data grid
	 *
	 * @return void
	 */
	public function noSyncAction() {
	}
}
?>