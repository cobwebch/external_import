<?php

declare(strict_types=1);

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
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Controller for the "Data Import" backend module
 */
class DataModuleController extends ActionController
{

    protected ModuleTemplateFactory $moduleTemplateFactory;

    protected ?ModuleTemplate $moduleTemplate = null;

    protected PageRenderer $pageRenderer;

    protected ConfigurationRepository $configurationRepository;

    protected SchedulerRepository $schedulerRepository;

    protected IconFactory $iconFactory;

    public function __construct(
        ModuleTemplateFactory $moduleTemplateFactory,
        PageRenderer $pageRenderer,
        IconFactory $iconFactory,
        ConfigurationRepository $configurationRepository,
        SchedulerRepository $schedulerRepository
    ) {
        $this->moduleTemplateFactory = $moduleTemplateFactory;
        $this->pageRenderer = $pageRenderer;
        $this->iconFactory = $iconFactory;
        $this->configurationRepository = $configurationRepository;
        $this->schedulerRepository = $schedulerRepository;
    }

    public function initializeAction(): void
    {
        $this->moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $this->moduleTemplate->setTitle(
            'External Import - ' .
            $this->getLanguageService()->sL('LLL:EXT:external_import/Resources/Private/Language/DataModule.xlf:mlang_tabs_tab')
        );
    }

    /**
     * Renders the list of all synchronizable tables.
     *
     * @return ResponseInterface
     */
    public function listSynchronizableAction(): ResponseInterface
    {
        $this->prepareView('listSynchronizable');

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
                    AbstractMessage::INFO
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

        $this->moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Renders the list of all non-synchronizable tables.
     *
     * @return ResponseInterface
     */
    public function listNonSynchronizableAction(): ResponseInterface
    {
        $this->prepareView('listNonSynchronizable');

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
                    AbstractMessage::INFO
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

        $this->moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Performs the synchronization for the given external import configuration.
     *
     * @param string $table The name of the table to synchronize
     * @param string $index Key of the external configuration
     * @return ResponseInterface
     */
    public function synchronizeAction(string $table, string $index): ResponseInterface
    {
        // Synchronize the chosen data
        $importer = GeneralUtility::makeInstance(Importer::class);
        $importer->setContext('manual');
        $messages = $importer->synchronize($table, $index);
        $this->prepareMessages($messages);

        // Redirect to the list of synchronizable tables
        return $this->redirect('listSynchronizable');
    }

    /**
     * Runs a preview of a given configuration.
     *
     * @param string $table The name of the table to synchronize
     * @param string $index Key of the external configuration
     * @param string $stepClass Name of the Step class to preview
     * @return ResponseInterface
     */
    public function previewAction(string $table, string $index, string $stepClass = ''): ResponseInterface
    {
        // Add a close button to the toolbar
        $this->prepareView('', 'listSynchronizable');

        // Load the configuration
        $stepList = [];
        $previewData = null;
        try {
            $configuration = $this->configurationRepository->findConfigurationObject(
                $table,
                $index
            );

            if ($stepClass !== '') {
                // Synchronize the chosen configuration in preview mode
                $importer = GeneralUtility::makeInstance(Importer::class);
                $importer->setContext('manual');
                $importer->setPreviewStep($stepClass);
                $messages = $importer->synchronize($table, $index);
                $this->prepareMessages($messages, false);
                $previewData = $importer->getPreviewData();
            }
            // The step list should use the class names also as keys
            $steps = $configuration->getSteps();
            foreach ($steps as $step) {
                $stepList[$step] = $step;
            }
        } catch (\Exception $e) {
            $this->addFlashMessage(
                LocalizationUtility::translate(
                    'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:exceptionOccurred',
                    'external_import',
                    [
                        $e->getMessage(),
                        $e->getCode()
                    ]
                ),
                '',
                AbstractMessage::ERROR
            );
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

        $this->moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Displays the detailed view for the chosen configuration.
     *
     * @param string $table The name of the table to synchronize
     * @param string $index Key of the external configuration
     * @return ResponseInterface
     */
    public function viewConfigurationAction(string $table, string $index): ResponseInterface
    {
        $configuration = null;
        $connector = '';
        try {
            $configuration = $this->configurationRepository->findConfigurationObject(
                $table,
                $index
            );
            $connector = $configuration->getGeneralConfigurationProperty('connector');
        } catch (\Exception $e) {
            $this->addFlashMessage(
                LocalizationUtility::translate(
                    'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:exceptionOccurred',
                    'external_import',
                    [
                        $e->getMessage(),
                        $e->getCode()
                    ]
                ),
                '',
                AbstractMessage::ERROR
            );
        }

        // Define which action to go back to for the close button (depends on whether the configuration is synchronizable or not)
        if (empty($connector)) {
            $returnAction = 'listNonSynchronizable';
        } else {
            $returnAction = 'listSynchronizable';
        }
        // Add a close button to the toolbar
        $this->prepareView('', $returnAction);

        $this->view->assignMultiple(
            [
                'table' => $table,
                'index' => $index,
                'configuration' => $configuration,
                'storageRecord' => BackendUtility::getRecord('pages', $configuration->getStoragePid())
            ],
        );

        $this->moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Displays the form for defining a new scheduler task.
     *
     * @param string $table Name of the table to set a task for
     * @param string $index Index of the configuration to set a task for
     * @return ResponseInterface
     */
    public function newTaskAction(string $table, string $index = ''): ResponseInterface
    {
        // Add a close button to the toolbar
        $this->prepareView('', 'listSynchronizable');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/DateTimePicker');

        $this->view->assignMultiple(
            [
                'table' => $table,
                'index' => $index,
                'groups' => $this->schedulerRepository->fetchAllGroups(),
            ]
        );

        $this->moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Saves the data to a new scheduler task.
     *
     * @param string $table Name of the table for which to set an automated task for
     * @param string $frequency Automation frequency
     * @param int $group Scheduler task group
     * @param string $index Index for which to set an automated task for
     * @return ResponseInterface
     * @\TYPO3\CMS\Extbase\Annotation\Validate(param="frequency", validator="\Cobweb\ExternalImport\Validator\FrequencyValidator")
     */
    public function createTaskAction(
        string $table,
        string $frequency,
        int $group,
        string $index = ''
    ): ResponseInterface {
        try {
            $this->schedulerRepository->saveTask(
                $this->schedulerRepository->prepareTaskData(
                    $frequency,
                    $group,
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
                AbstractMessage::ERROR
            );
        }
        return $this->redirect('listSynchronizable');
    }

    /**
     * Displays the editing form for the given scheduler task.
     *
     * @param int $uid Id of the task to edit
     * @return ResponseInterface
     */
    public function editTaskAction(int $uid): ResponseInterface
    {
        // Add a close button to the toolbar
        $this->prepareView('', 'listSynchronizable');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/DateTimePicker');

        try {
            $task = $this->schedulerRepository->fetchTaskByUid($uid);
            $this->view->assignMultiple(
                [
                    'task' => $task,
                    'groups' => $this->schedulerRepository->fetchAllGroups(),
                ]
            );
        } catch (\Exception $e) {
            $this->addFlashMessage(
                LocalizationUtility::translate(
                    'error_invalid_task',
                    'external_import'
                ),
                '',
                AbstractMessage::ERROR
            );
            return $this->redirect('listSynchronizable');
        }

        $this->moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Saves the data to the existing scheduler task.
     *
     * @param int $uid Id of the task to update
     * @param string $frequency Automation frequency
     * @param int $group Scheduler task group
     * @return ResponseInterface
     * @\TYPO3\CMS\Extbase\Annotation\Validate(param="frequency", validator="\Cobweb\ExternalImport\Validator\FrequencyValidator")
     */
    public function updateTaskAction(int $uid, string $frequency, int $group): ResponseInterface
    {
        try {
            $this->schedulerRepository->saveTask(
                $this->schedulerRepository->prepareTaskData(
                    $frequency,
                    $group,
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
                AbstractMessage::ERROR
            );
        }
        return $this->redirect('listSynchronizable');
    }

    /**
     * Deletes the given scheduler task.
     *
     * @param int $uid Id of the scheduler task to delete
     * @return ResponseInterface
     */
    public function deleteTaskAction(int $uid): ResponseInterface
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
                AbstractMessage::ERROR
            );
        }
        return $this->redirect('listSynchronizable');
    }

    /**
     * Prepares the view when the action actually displays something.
     *
     * Some actions just perform something and redirect to another view.
     *
     * @param string $menuAction
     * @param string $closeButtonAction
     * @return void
     */
    protected function prepareView(string $menuAction = '', string $closeButtonAction = ''): void
    {
        $this->loadResources();
        // If the view is called by one of the actions in the menu, render the menu
        if ($menuAction !== '') {
            $this->prepareMainMenu($menuAction);
        }
        // Add a close button, if a return action is defined
        if ($closeButtonAction !== '') {
            $this->prepareCloseButton($closeButtonAction);
        }
    }

    /**
     * Loads the resources (JS, CSS) needed by some action views.
     *
     * @return void
     */
    protected function loadResources(): void
    {
        $publicResourcesPath = PathUtility::getAbsoluteWebPath(
            ExtensionManagementUtility::extPath('external_import') . 'Resources/Public/'
        );
        $this->pageRenderer->addCssFile($publicResourcesPath . 'StyleSheet/ExternalImport.css');
        $this->pageRenderer->addRequireJsConfiguration(
            [
                'paths' => [
                    'datatables' => $publicResourcesPath . 'JavaScript/Contrib/datatables'
                ]
            ]
        );
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/ExternalImport/DataModule');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:external_import/Resources/Private/Language/JavaScript.xlf');

        // Evaluate write access on all tables
        $globalWriteAccess = $this->configurationRepository->findGlobalWriteAccess();
        $this->view->assign('globalWriteAccess', $globalWriteAccess);
    }

    /**
     * Defines the menu items in the docheader.
     *
     * @param string $action
     * @return void
     */
    protected function prepareMainMenu(string $action): void
    {
        $this->uriBuilder->setRequest($this->request);

        $menu = $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $menu->setIdentifier('ExternalImportMenu');

        // Link to synchronizable tables view
        $menu->addMenuItem(
            $menu->makeMenuItem()
            ->setTitle(LocalizationUtility::translate('function_sync', 'external_import'))
            ->setHref($this->uriBuilder->uriFor('listSynchronizable'))
            ->setActive($action === 'listSynchronizable')
        );
        // Link to non-synchronizable tables view
        $menu->addMenuItem(
            $menu->makeMenuItem()
            ->setTitle(LocalizationUtility::translate('function_nosync', 'external_import'))
            ->setHref($this->uriBuilder->uriFor('listNonSynchronizable'))
            ->setActive($action === 'listNonSynchronizable')
        );

        $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
    }

    /**
     * Adds a close button to the docheader.
     *
     * @param string $returnAction Name of the action to return to
     * @return void
     */
    protected function prepareCloseButton(string $returnAction): void
    {
        $closeIcon = $this->iconFactory->getIcon('actions-close', Icon::SIZE_SMALL);
        $closeButton = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->makeLinkButton()
            ->setIcon($closeIcon)
            ->setTitle(LocalizationUtility::translate('back_to_list', 'external_import'))
            ->setHref(
                $this->uriBuilder->uriFor($returnAction)
            );
        $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->addButton($closeButton);
    }

    /**
     * Stores the messages returned by External Import as flash messages.
     *
     * The list is trimmed if there are too many messages.
     *
     * @param array $messages List of messages from an External Import run
     * @param bool $storeInSession Whether to store the flash messages in session or not
     */
    protected function prepareMessages(array $messages, bool $storeInSession = true): void
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
    protected function getErrorFlashMessage(): bool
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

    /**
     * Returns the global language object.
     *
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
