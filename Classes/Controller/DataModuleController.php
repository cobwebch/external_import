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
use Cobweb\ExternalImport\Domain\Repository\SchedulerRepository;
use Cobweb\ExternalImport\Importer;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\Menu\Menu;
use TYPO3\CMS\Backend\Template\Components\Menu\MenuItem;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Controller for the "Data Import" backend module
 *
 * @author Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package TYPO3
 * @subpackage tx_externalimport
 */
class DataModuleController extends ActionController
{

    /**
     * @var BackendTemplateView
     */
    protected $view;

    /**
     * @var ConfigurationRepository
     */
    protected $configurationRepository;

    /**
     * @var SchedulerRepository
     */
    protected $schedulerRepository;

    /**
     * Injects an instance of the configuration repository.
     *
     * @param ConfigurationRepository $configurationRepository
     * @return void
     */
    public function injectConfigurationRepository(ConfigurationRepository $configurationRepository): void
    {
        $this->configurationRepository = $configurationRepository;
    }

    /**
     * Injects an instance of the scheduler repository.
     *
     * @param SchedulerRepository $schedulerRepository
     * @return void
     */
    public function injectSchedulerRepository(SchedulerRepository $schedulerRepository): void
    {
        $this->schedulerRepository = $schedulerRepository;
    }

    /**
     * Initializes the template to use for all actions.
     *
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentNameException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException
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
        // Do not initialize the view for certain actions (which just do processing and do not display anything)
        $currentAction = $this->request->getControllerActionName();
        if ($currentAction !== 'synchronize' && $currentAction !== 'createTask' && $currentAction !== 'updateTask' && $currentAction !== 'deleteTask') {
            if ($view instanceof BackendTemplateView) {
                parent::initializeView($view);
            }
            $pageRenderer = $view->getModuleTemplate()->getPageRenderer();
            $pageRenderer->addCssFile('EXT:external_import/Resources/Public/StyleSheet/ExternalImport.css');
            // For TYPO3 v10, load datatables from local contrib folder
            // TODO: remove check once compat with v9 is droppped
            if (VersionNumberUtility::convertVersionNumberToInteger(VersionNumberUtility::getNumericTypo3Version()) > 10000000) {
                $pageRenderer->addRequireJsConfiguration(
                        [
                                'paths' => [
                                        'datatables' => '../typo3conf/ext/external_import/Resources/Public/JavaScript/Contrib/jquery.dataTables'
                                ]
                        ]
                );
            }
            $pageRenderer->loadRequireJsModule('TYPO3/CMS/ExternalImport/DataModule');
            $pageRenderer->addInlineLanguageLabelFile('EXT:external_import/Resources/Private/Language/JavaScript.xlf');

            // Evaluate write access on all tables
            $globalWriteAccess = $this->configurationRepository->findGlobalWriteAccess();
            $view->assign('globalWriteAccess', $globalWriteAccess);
        }
    }

    /**
     * Renders the list of all synchronizable tables.
     *
     * @return void
     */
    public function listSynchronizableAction(): void
    {
        $this->prepareDocHeaderMenu();

        $configurations = $this->configurationRepository->findBySync(true);
        // Issue information message if there are no configurations at all
        if (count($configurations) === 0) {
            try {
                $this->addFlashMessage(
                        LocalizationUtility::translate(
                                'no_configurations_warning',
                                'external_import'
                        ),
                        '',
                        FlashMessage::INFO
                );
            } catch (\Exception $e) {
                // The above code should really work, nothing to do if it doesn't
            }
        }
        // Try to get the task that performs synchronization for all configurations
        try {
            $fullSynchronizationTask = $this->schedulerRepository->fetchFullSynchronizationTask();
        } catch (\Exception $e) {
            $fullSynchronizationTask = null;
        }
        $this->view->assignMultiple(
                [
                        'configurations' => $configurations,
                        'fullSynchronizationTask' => $fullSynchronizationTask,
                        'rights' => [
                                'sync' => $this->getBackendUser()->check(
                                        'custom_options',
                                        'tx_externalimport_bemodule_actions:sync'
                                ),
                                'scheduler' => $this->getBackendUser()->check(
                                        'custom_options',
                                        'tx_externalimport_bemodule_actions:scheduler'
                                )
                        ]
                ]
        );
    }

    /**
     * Renders the list of all non-synchronizable tables.
     *
     * @return void
     */
    public function listNonSynchronizableAction(): void
    {
        $this->prepareDocHeaderMenu();

        $configurations = $this->configurationRepository->findBySync(false);
        // Issue information message if there are no configurations at all
        if (count($configurations) === 0) {
            try {
                $this->addFlashMessage(
                        LocalizationUtility::translate(
                                'no_configurations_warning',
                                'external_import'
                        ),
                        '',
                        FlashMessage::INFO
                );
            } catch (\Exception $e) {
                // The above code should really work, nothing to do if it doesn't
            }
        }
        $this->view->assignMultiple(
                [
                        'configurations' => $configurations
                ]
        );
    }

    /**
     * Performs the synchronization for the given external import configuration.
     *
     * @param string $table The name of the table to synchronize
     * @param string $index Key of the external configuration
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */
    public function synchronizeAction($table, $index): void
    {
        // Synchronize the chosen data
        /** @var Importer $importer */
        $importer = $this->objectManager->get(Importer::class);
        $importer->setContext('manual');
        $messages = $importer->synchronize($table, $index);
        $this->prepareMessages($messages);

        // Redirect to the list of synchronizable tables
        $this->redirect('listSynchronizable');
    }

    /**
     * Runs a preview of a given configuration.
     *
     * @param string $table The name of the table to synchronize
     * @param string $index Key of the external configuration
     * @param string $stepClass Name of the Step class to preview
     */
    public function previewAction($table, $index, $stepClass = ''): void
    {
        // Add a close button to the toolbar
        $this->prepareCloseButton('listSynchronizable');

        $previewData = null;
        if ($stepClass !== '') {
            // Synchronize the chosen configuration in preview mode
            /** @var Importer $importer */
            $importer = $this->objectManager->get(Importer::class);
            $importer->setContext('manual');
            $importer->setPreviewStep($stepClass);
            $messages = $importer->synchronize($table, $index);
            $this->prepareMessages($messages, false);
            $previewData = $importer->getPreviewData();
        }
        // The step list should use the class names also as keys
        $steps = Importer::SYNCHRONYZE_DATA_STEPS;
        $stepList = [];
        foreach ($steps as $step) {
            $stepList[$step] = $step;
        }
        $this->view->assignMultiple(
                [
                        'table' => $table,
                        'index' => $index,
                        'steps' => $stepList,
                        'stepClass' => $stepClass,
                        'previewData' => $previewData
                ]
        );
    }

    /**
     * Displays the detailed view for the chosen configuration.
     *
     * @param string $table The name of the table to synchronize
     * @param string $index Key of the external configuration
     * @return void
     */
    public function viewConfigurationAction($table, $index): void
    {
        $configuration = $this->configurationRepository->findConfigurationObject(
                $table,
                $index
        );
        $connector = $configuration->getCtrlConfigurationProperty('connector');
        // Define which action to go back to for the close button (depends on whether the configuration is synchronizable or not)
        if (empty($connector)) {
            $returnAction = 'listNonSynchronizable';
        } else {
            $returnAction = 'listSynchronizable';
        }
        // Add a close button to the toolbar
        $this->prepareCloseButton($returnAction);

        $this->view->assignMultiple(
                [
                        'table' => $table,
                        'index' => $index,
                        'configuration' => $configuration
                ]
        );
    }

    /**
     * Displays the form for defining a new scheduler task.
     *
     * @param string $table Name of the table to set a task for
     * @param string $index Index of the configuration to set a task for
     * @return void
     */
    public function newTaskAction($table, $index = ''): void
    {
        // Add a close button to the toolbar
        $this->prepareCloseButton('listSynchronizable');
        $this->view->getModuleTemplate()->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/DateTimePicker');

        $this->view->assignMultiple(
                [
                        'table' => $table,
                        'index' => $index,
                        'groups' => $this->schedulerRepository->fetchAllGroups(),
                        'errors' => $this->controllerContext->getRequest()->getOriginalRequestMappingResults()->getFlattenedErrors()
                ]
        );
    }

    /**
     * Saves the data to a new scheduler task.
     *
     * @param string $table Name of the table for which to set an automated task for
     * @param string $frequency Automation frequency
     * @param int $group Scheduler task group
     * @param \DateTime $start_date_hr Automation start date
     * @param string $index Index for which to set an automated task for
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     * @\TYPO3\CMS\Extbase\Annotation\Validate(param="frequency", validator="\Cobweb\ExternalImport\Validator\FrequencyValidator")
     */
    public function createTaskAction($table, $frequency, $group, \DateTime $start_date_hr = null, $index = ''): void
    {
        try {
            $this->schedulerRepository->saveTask(
                    $this->schedulerRepository->prepareTaskData(
                            $frequency,
                            $group,
                            $start_date_hr,
                            $table,
                            $index
                    )
            );
            $this->addFlashMessage(
                    LocalizationUtility::translate(
                            'autosync_saved',
                            'external_import'
                    )
            );
        } catch (\Exception $e) {
            $this->addFlashMessage(
                    LocalizationUtility::translate(
                            'autosync_save_failed',
                            'external_import',
                            [
                                    $e->getMessage()
                            ]
                    ),
                    '',
                    FlashMessage::ERROR
            );
        }
        $this->redirect('listSynchronizable');
    }

    /**
     * Displays the editing form for the given scheduler task.
     *
     * @param int $uid Id of the task to edit
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */
    public function editTaskAction($uid): void
    {
        // Add a close button to the toolbar
        $this->prepareCloseButton('listSynchronizable');
        $this->view->getModuleTemplate()->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/DateTimePicker');

        try {
            $task = $this->schedulerRepository->fetchTaskByUid($uid);
            $this->view->assignMultiple(
                    [
                            'task' => $task,
                            'groups' => $this->schedulerRepository->fetchAllGroups(),
                            'errors' => $this->controllerContext->getRequest()->getOriginalRequestMappingResults()->getFlattenedErrors()
                    ]
            );
        } catch (\Exception $e) {
            $this->addFlashMessage(
                    LocalizationUtility::translate(
                            'error_invalid_task',
                            'external_import'
                    ),
                    '',
                    FlashMessage::ERROR
            );
            $this->redirect('listSynchronizable');
        }
    }

    /**
     * Saves the data to the existing scheduler task.
     *
     * @param int $uid Id of the task to update
     * @param string $frequency Automation frequency
     * @param int $group Scheduler task group
     * @param \DateTime $start_date_hr Automation start date
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     * @\TYPO3\CMS\Extbase\Annotation\Validate(param="frequency", validator="\Cobweb\ExternalImport\Validator\FrequencyValidator")
     */
    public function updateTaskAction($uid, $frequency, $group, \DateTime $start_date_hr = null): void
    {
        try {
            $this->schedulerRepository->saveTask(
                    $this->schedulerRepository->prepareTaskData(
                            $frequency,
                            $group,
                            $start_date_hr,
                            '',
                            '',
                            $uid
                    )
            );
            $this->addFlashMessage(
                    LocalizationUtility::translate(
                            'autosync_saved',
                            'external_import'
                    )
            );
        } catch (\Exception $e) {
            $this->addFlashMessage(
                    LocalizationUtility::translate(
                            'autosync_save_failed',
                            'external_import',
                            [
                                    $e->getMessage()
                            ]
                    ),
                    '',
                    FlashMessage::ERROR
            );
        }
        $this->redirect('listSynchronizable');
    }

    /**
     * Deletes the given scheduler task.
     *
     * @param int $uid Id of the scheduler task to delete
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */
    public function deleteTaskAction($uid): void
    {
        try {
            $this->schedulerRepository->deleteTask($uid);
            $this->addFlashMessage(
                    LocalizationUtility::translate(
                            'delete_done',
                            'external_import'
                    )
            );
        } catch (\Exception $e) {
            $this->addFlashMessage(
                    LocalizationUtility::translate(
                            'delete_failed',
                            'external_import',
                            [
                                    $e->getMessage()
                            ]
                    ),
                    '',
                    FlashMessage::ERROR
            );
        }
        $this->redirect('listSynchronizable');
    }

    /**
     * Defines the menu items in the docheader.
     *
     * @return void
     */
    protected function prepareDocHeaderMenu(): void
    {
        $uriBuilder = $this->objectManager->get(UriBuilder::class);
        $uriBuilder->setRequest($this->request);

        /** @var Menu $menu */
        $menu = GeneralUtility::makeInstance(Menu::class);
        $menu->setIdentifier('_externalImportMenu');

        // Link to synchronizable tables view
        /** @var MenuItem $synchronizationMenuItem */
        $synchronizationMenuItem = GeneralUtility::makeInstance(MenuItem::class);
        $action = 'listSynchronizable';
        $isActive = $this->request->getControllerActionName() === $action;
        $synchronizationMenuItem->setTitle(
                LocalizationUtility::translate(
                        'function_sync',
                        'external_import'
                )
        );
        $uri = $uriBuilder->reset()->uriFor(
                $action,
                [],
                'DataModule'
        );
        $synchronizationMenuItem->setHref($uri)->setActive($isActive);

        // Link to non-synchronizable tables view
        /** @var MenuItem $noSynchronizationMenuItem */
        $noSynchronizationMenuItem = GeneralUtility::makeInstance(MenuItem::class);
        $action = 'listNonSynchronizable';
        $isActive = $this->request->getControllerActionName() === $action;
        $noSynchronizationMenuItem->setTitle(
                LocalizationUtility::translate(
                        'function_nosync',
                        'external_import'
                )
        );
        $uri = $uriBuilder->reset()->uriFor(
                $action,
                [],
                'DataModule'
        );
        $noSynchronizationMenuItem->setHref($uri)->setActive($isActive);

        $menu->addMenuItem($synchronizationMenuItem);
        $menu->addMenuItem($noSynchronizationMenuItem);
        $this->view->getModuleTemplate()->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
    }

    /**
     * Adds a close button to the docheader.
     *
     * @param string $returnAction Name of the action to return to
     * @return void
     */
    protected function prepareCloseButton($returnAction): void
    {
        $closeIcon = $this->view->getModuleTemplate()->getIconFactory()->getIcon('actions-close', Icon::SIZE_SMALL);
        $closeButton = $this->view->getModuleTemplate()->getDocHeaderComponent()->getButtonBar()->makeLinkButton()
                ->setIcon($closeIcon)
                ->setTitle(LocalizationUtility::translate('back_to_list', 'external_import'))
                ->setHref(
                        $this->uriBuilder->uriFor($returnAction)
                );
        $this->view->getModuleTemplate()->getDocHeaderComponent()->getButtonBar()->addButton(
                $closeButton,
                ButtonBar::BUTTON_POSITION_LEFT
        );
    }

    /**
     * Stores the messages returned by External Import as flash messages.
     *
     * The list is trimmed if there are too many messages.
     *
     * @param array $messages List of messages from an External Import run
     * @param bool $storeInSession Whether to store the flash messages in session or not
     */
    protected function prepareMessages($messages, $storeInSession = true): void
    {
        // If there are too many messages, Remove extra messages and add warning about it
        // to avoid cluttering the interface
        foreach ($messages as $severity => $messageList) {
            $numMessages = count($messageList);
            if ($numMessages > 5) {
                array_splice($messageList, 5);
                $messageList[] = sprintf(
                        LocalizationUtility::translate('moreMessages', 'external_import'),
                        $numMessages
                );
                $messages[$severity] = $messageList;
            }
            // Store the  messages as Flash messages
            foreach ($messageList as $aMessage) {
                try {
                    $this->addFlashMessage(
                            $aMessage,
                            '',
                            $severity,
                            $storeInSession
                    );
                } catch (\Exception $e) {
                    // Do nothing, just avoid crashing for failing to display a flash message
                }
            }
        }
    }
    /**
     * Overrides parent method to avoid displaying default error message.
     *
     * @return bool
     */
    protected function getErrorFlashMessage()
    {
        return false;
    }

    /**
     * Returns the global BE user object.
     *
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
