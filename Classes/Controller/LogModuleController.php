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

use Cobweb\ExternalImport\Domain\Repository\LogRepository;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;

/**
 * Controller for the "Log" backend module
 *
 * @author Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package TYPO3
 * @subpackage tx_externalimport
 */
class LogModuleController extends ActionController
{

    /**
     * @var BackendTemplateView
     */
    protected $view;

    /**
     * @var LogRepository
     */
    protected $logRepository;

    /**
     * Injects an instance of the log repository.
     *
     * @param LogRepository $logRepository
     * @return void
     */
    public function injectLogRepository(LogRepository $logRepository): void
    {
        $this->logRepository = $logRepository;
    }

    /**
     * Initializes the template to use for all actions.
     *
     * @return void
     */
    protected function initializeAction()
    {
        $this->defaultViewObjectName = BackendTemplateView::class;
    }

    /**
     * Initializes the view before invoking an action method.
     *
     * @param ViewInterface $view The view to be initialized
     * @return void
     * @api
     */
    protected function initializeView(ViewInterface $view)
    {
        if ($view instanceof BackendTemplateView) {
            parent::initializeView($view);
        }
        $pageRenderer = $view->getModuleTemplate()->getPageRenderer();
        $pageRenderer->addCssFile('EXT:external_import/Resources/Public/StyleSheet/ExternalImport.css');
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/ExternalImport/LogModule');
        $pageRenderer->addInlineLanguageLabelFile('EXT:external_import/Resources/Private/Language/JavaScript.xlf');
    }

    /**
     * Displays the list of all available log entries.
     *
     * @return void
     */
    public function listAction(): void
    {
    }
}