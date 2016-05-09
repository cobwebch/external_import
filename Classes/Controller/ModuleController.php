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
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Controller for the backend module
 *
 * @author Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package TYPO3
 * @subpackage tx_externalimport
 */
class ModuleController extends ActionController {

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
	public function injectConfigurationRepository(ConfigurationRepository $configurationRepository) {
		$this->configurationRepository = $configurationRepository;
	}

    /**
     * Injects an instance of the scheduler repository.
     *
     * @param SchedulerRepository $schedulerRepository
     */
    public function injectSchedulerRepository(SchedulerRepository $schedulerRepository)
    {
        $this->schedulerRepository = $schedulerRepository;
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
	protected function initializeView(ViewInterface $view) {
        // Do not initialize the view for certain actions (which just do processing and do not display anything)
        $currentAction = $this->request->getControllerActionName();
        if ($currentAction !== 'synchronize' && $currentAction !== 'createTask' && $currentAction !== 'updateTask' && $currentAction !== 'deleteTask') {
            if ($view instanceof BackendTemplateView) {
          			parent::initializeView($view);
          		}
                  $view->getModuleTemplate()->getPageRenderer()->addCssFile(
                          ExtensionManagementUtility::extRelPath('external_import') . 'Resources/Public/StyleSheet/ExternalImport.css'
                  );
                  $this->view->getModuleTemplate()->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/ExternalImport/Module');

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
	public function listSynchronizableAction() {
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
            }
            catch (\Exception $e) {
                // The above code should really work, nothing to do if it doesn't
            }
        }
        // Try to get the task that performs synchronization for all configurations
        try {
            $fullSynchronizationTask = $this->schedulerRepository->fetchFullSynchronizationTask();
        }
        catch (\Exception $e) {
            $fullSynchronizationTask = null;
        }
        $this->view->assignMultiple(
                array(
                        'configurations' => $configurations,
                        'fullSynchronizationTask' => $fullSynchronizationTask
                )
        );
	}

	/**
	 * Renders the list of all non-synchronizable tables.
	 *
	 * @return void
	 */
	public function listNonSynchronizableAction() {
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
            }
            catch (\Exception $e) {
                // The above code should really work, nothing to do if it doesn't
            }
        }
        $this->view->assignMultiple(
                array(
                        'configurations' => $configurations
                )
        );
	}

    /**
     * Performs the synchronization for the given external import configuration.
     *
     * @param string $table The name of the table to synchronize
     * @param string $index Key of the external configuration
     * @return void
     */
    public function synchronizeAction($table, $index)
    {
        // Synchronize the chosen data
        /** @var Importer $importer */
        $importer = GeneralUtility::makeInstance(Importer::class);
        $messages = $importer->synchronizeData($table, $index);

        // Perform reporting
        // Check if there are too many messages, to avoid cluttering the interface
        // Remove extra messages and add warning about it
        foreach ($messages as $severity => $messageList) {
            $numMessages = count($messageList);
            if ($numMessages > 5) {
                array_splice($messageList, 5);
                $messageList[] = sprintf(
                        $GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xml:moreMessages'),
                        $numMessages
                );
                $messages[$severity] = $messageList;
            }
            // Output the messages
            foreach ($messageList as $aMessage) {
                try {
                    $this->addFlashMessage(
                            $aMessage,
                            '',
                            $severity
                    );
                }
                catch (\Exception $e) {
                    // Do nothing, just avoid crashing for failing to display a flash message
                }
            }
        }
        // Redirect to the list of synchronizable tables
        $this->redirect('listSynchronizable');
    }

    /**
     * Displays the detailed view for the chosen configuration.
     *
     * @param string $table The name of the table to synchronize
     * @param string $index Key of the external configuration
     * @return void
     */
    public function viewConfigurationAction($table, $index)
    {
        // Define which action to back to for the close button (depends on whether the configuration is synchronizable or not)
        $controlConfiguration = $this->configurationRepository->findByTableAndIndex(
                $table,
                $index
        );
        if (empty($controlConfiguration['connector'])) {
            $returnAction = 'listNonSynchronizable';
        } else {
            $returnAction = 'listSynchronizable';
        }
        // Add a close button to the toolbar
        $this->prepareCloseButton($returnAction);

        $this->view->assignMultiple(
                array(
                        'table' => $table,
                        'index' => $index,
                        'ctrlConfiguration' => $controlConfiguration,
                        'columnConfiguration' => $this->configurationRepository->findColumnsByTableAndIndex(
                                $table,
                                isset($controlConfiguration['useColumnIndex']) ? $controlConfiguration['useColumnIndex'] : $index
                        )
                )
        );
    }

    /**
     * Displays the form for defining a new scheduler task.
     *
     * @param string $table Name of the table to set a task for
     * @param string $index Index of the configuration to set a task for
     * @return void
     */
    public function newTaskAction($table, $index = '')
    {
        // Add a close button to the toolbar
        $this->prepareCloseButton('listSynchronizable');
        $this->view->getModuleTemplate()->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/DateTimePicker');

        $this->view->assignMultiple(
                array(
                        'table' => $table,
                        'index' => $index,
                        'groups' => $this->schedulerRepository->fetchAllGroups()
                )
        );
    }

    /**
     * Saves the data to a new scheduler task.
     *
     * @param string $table Name of the table for which to set an automated task for
     * @param string $frequency Automation frequency
     * @param int $group Scheduler task group
     * @param int $start_date_hr Automation start date
     * @param string $index Index for which to set an automated task for
     * @validate $frequency \Cobweb\ExternalImport\Validator\FrequencyValidator
     * @validate $start_date_hr \Cobweb\ExternalImport\Validator\DateTimeValidator
     * @return void
     */
    public function createTaskAction($table, $frequency, $group, $start_date_hr, $index = '')
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
        }
        catch (\Exception $e) {
            $this->addFlashMessage(
                    LocalizationUtility::translate(
                            'autosync_save_failed',
                            'external_import',
                            array(
                                    $e->getMessage()
                            )
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
     */
    public function editTaskAction($uid)
    {
        // Add a close button to the toolbar
        $this->prepareCloseButton('listSynchronizable');
        $this->view->getModuleTemplate()->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/DateTimePicker');

        try {
            $task = $this->schedulerRepository->fetchTaskByUid($uid);
            $this->view->assignMultiple(
                    array(
                            'task' => $task,
                            'groups' => $this->schedulerRepository->fetchAllGroups()
                    )
            );
        }
        catch (\Exception $e) {
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
     * @param int $start_date_hr Automation start date
     * @validate $frequency \Cobweb\ExternalImport\Validator\FrequencyValidator
     * @validate $start_date_hr \Cobweb\ExternalImport\Validator\DateTimeValidator
     * @return void
     */
    public function updateTaskAction($uid, $frequency, $group, $start_date_hr)
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
        }
        catch (\Exception $e) {
            $this->addFlashMessage(
                    LocalizationUtility::translate(
                            'autosync_save_failed',
                            'external_import',
                            array(
                                    $e->getMessage()
                            )
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
     */
    public function deleteTaskAction($uid)
    {
        try {
            $this->schedulerRepository->deleteTask($uid);
            $this->addFlashMessage(
                    LocalizationUtility::translate(
                            'delete_done',
                            'external_import'
                    )
            );
        }
        catch (\Exception $e) {
            $this->addFlashMessage(
                    LocalizationUtility::translate(
                            'delete_failed',
                            'external_import',
                            array(
                                    $e->getMessage()
                            )
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
    protected function prepareDocHeaderMenu()
    {
        $uriBuilder = $this->objectManager->get(UriBuilder::class);
        $uriBuilder->setRequest($this->request);

        /** @var Menu $menu */
        $menu = GeneralUtility::makeInstance(Menu::class);
        $menu->setIdentifier('_externalImportMenu');

        /** @var MenuItem $languageListMenuItem */
        $languageListMenuItem = GeneralUtility::makeInstance(MenuItem::class);
        $action = 'listSynchronizable';
        $isActive = $this->request->getControllerActionName() === $action;
        $languageListMenuItem->setTitle(
                LocalizationUtility::translate(
                        'function_sync',
                        'external_import'
                )
        );
        $uri = $uriBuilder->reset()->uriFor(
                $action,
                array(),
                'Module'
        );
        $languageListMenuItem->setHref($uri)->setActive($isActive);

        /** @var MenuItem $translationMenuItem */
        $translationMenuItem = GeneralUtility::makeInstance(MenuItem::class);
        $action = 'listNonSynchronizable';
        $isActive = $this->request->getControllerActionName() === $action;
        $translationMenuItem->setTitle(
                LocalizationUtility::translate(
                        'function_nosync',
                        'external_import'
                )
        );
        $uri = $uriBuilder->reset()->uriFor(
                $action,
                array(),
                'Module'
        );
        $translationMenuItem->setHref($uri)->setActive($isActive);

        $menu->addMenuItem($languageListMenuItem);
        $menu->addMenuItem($translationMenuItem);
        $this->view->getModuleTemplate()->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
    }

    /**
     * Adds a close button to the docheader.
     *
     * @param string $returnAction Name of the action to return to
     * @return void
     */
    protected function prepareCloseButton($returnAction)
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
     * Overrides parent method to avoid displaying default error message.
     *
     * @return bool
     */
    protected function getErrorFlashMessage()
    {
        return false;
    }
}
