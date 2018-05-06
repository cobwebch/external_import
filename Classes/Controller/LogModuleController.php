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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Controller for the "Data Import" backend module
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
    public function injectLogRepository(LogRepository $logRepository)
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
    public function listAction()
    {

    }

    /**
     * Returns the list of all log entries, in JSON format.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function getAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        // Methods that respond to AJAX calls do not go through the normal Extbase bootstrapping.
        // Thus some objects need to be instantiated "manually".
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->logRepository = $this->objectManager->get(LogRepository::class);

        // Get all the log entries and make them into an array for JSON encoding
        $logEntries = $this->logRepository->findAll();
        $logs = array();
        /** @var \Cobweb\ExternalImport\Domain\Model\Log $logEntry */
        foreach ($logEntries as $logEntry) {
            $logs[] = array(
                'status' => $logEntry->getStatus(),
                'date' => $logEntry->getCrdate()->format('U'),
                'user' => $logEntry->getCruserId()->getUserName(),
                'configuration' => $logEntry->getConfiguration(),
                'context' => $logEntry->getContext(),
                'message' => $logEntry->getMessage()
            );
        }
        // Send the response
        $response->getBody()->write(json_encode($logs));
        return $response;
    }
}