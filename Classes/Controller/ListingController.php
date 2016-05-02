<?php
namespace Cobweb\ExternalImport\Controller;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Cobweb\ExternalImport\Domain\Repository\ConfigurationRepository;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;

/**
 * Controller for the backend module
 *
 * @author Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package TYPO3
 * @subpackage tx_externalimport
 */
class ListingController extends ActionController {
	/**
	 * @var ConfigurationRepository
	 */
	protected $configurationRepository;

	/**
	 * @var string Name of the refresh icon (see self::initializeAction())
	 */
	protected $refreshIcon = 'actions-system-refresh';

	/**
	 * Injects an instance of the configuration repository
	 *
	 * @param ConfigurationRepository $configurationRepository
	 * @return void
	 */
	public function injectConfigurationRepository(ConfigurationRepository $configurationRepository) {
		$this->configurationRepository = $configurationRepository;
	}

	/**
	 * Initializes the view before invoking an action method.
	 *
	 * Override this method to solve assign variables common for all actions
	 * or prepare the view in another way before the action is called.
	 *
	 * @param ViewInterface $view The view to be initialized
	 * @return void
	 * @api
	 */
	protected function initializeView(ViewInterface $view) {
		// Evaluate write access on all tables
		$globalWriteAccess = $this->configurationRepository->findGlobalWriteAccess();
		$view->assign('globalWriteAccess', $globalWriteAccess);
		$view->assign('view', strtolower($this->request->getControllerActionName()));
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
