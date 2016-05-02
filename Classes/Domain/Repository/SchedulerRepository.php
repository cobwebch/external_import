<?php
namespace Cobweb\ExternalImport\Domain\Repository;

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

use Cobweb\ExternalImport\Task\AutomatedSyncTask;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Scheduler;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * Pseudo-repository class for Scheduler tasks
 *
 * This is not a true repository from an Extbase point of view. It implements only a few features of a complete repository.
 *
 * @author Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package TYPO3
 * @subpackage tx_externalimport
 */
class SchedulerRepository implements SingletonInterface
{
    /**
     * @var    string    Name of the related task class
     */
    static public $taskClassName = 'tx_externalimport_autosync_scheduler_Task';

    /**
     * List of all tasks (stored locally in case the repository is called several times)
     *
     * @var array
     */
    protected $tasks = array();

    /**
     * Local instance of the scheduler object
     *
     * @var Scheduler
     */
    protected $scheduler;

    /**
     * @var string Display date format according to TYPO3 setup
     */
    protected $dateFormat;

    public function __construct()
    {
        $this->scheduler = GeneralUtility::makeInstance(Scheduler::class);
        $allTasks = $this->scheduler->fetchTasksWithCondition('', true);
        /** @var $aTaskObject AbstractTask */
        foreach ($allTasks as $aTaskObject) {
            if (get_class($aTaskObject) === self::$taskClassName) {
                $this->tasks[] = $aTaskObject;
            }
        }

        $this->dateFormat = $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] . ' ' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'];
    }

    /**
     * Fetches all tasks related to the external import extension
     * The return array is structured per table/index
     *
     * @return array List of registered events/tasks, per table and index
     */
    public function fetchAllTasks()
    {
        $taskList = array();
        /** @var $taskObject AutomatedSyncTask */
        foreach ($this->tasks as $taskObject) {
            $key = $taskObject->table . '/' . $taskObject->index;
            $taskList[$key] = $this->assembleTaskInformation($taskObject);
        }
        return $taskList;
    }

    /**
     * Fetches the specific task that synchronizes all tables
     *
     * @throws \Exception
     * @return array Information about the task, if defined
     */
    public function fetchFullSynchronizationTask()
    {
        // Check all tasks object to find the one with the "all" keyword as a table
        /** @var $taskObject AutomatedSyncTask */
        foreach ($this->tasks as $taskObject) {
            if ($taskObject->table === 'all') {
                return $this->assembleTaskInformation($taskObject);
            }
        }
        throw new \Exception('No task registered for full synchronization', 1337344319);
    }

    /**
     * Grabs the information about a given external import task and stores it into an array
     *
     * @param AutomatedSyncTask $taskObject The task to handle
     * @return array The information about the task
     */
    protected function assembleTaskInformation(AutomatedSyncTask $taskObject)
    {
        $cronCommand = $taskObject->getExecution()->getCronCmd();
        $interval = $taskObject->getExecution()->getInterval();
        $taskInformation = array(
                'uid' => $taskObject->getTaskUid(),
                // Format date as needed for display
                'nextexecution' => date($this->dateFormat, $taskObject->getExecutionTime()),
                'interval' => sprintf($GLOBALS['LANG']->sL('LLL:EXT:Resources/Private/Language/locallang.xml:number_of_seconds'),
                        $interval),
                'croncmd' => $cronCommand,
                'frequency' => ($cronCommand === '') ? $interval : $cronCommand,
                // Format date and time as needed for form input
                'start_date' => date('m/d/Y', $taskObject->getExecution()->getStart()),
                'start_time' => date('H:i', $taskObject->getExecution()->getStart())
        );
        return $taskInformation;
    }

    /**
     * Saves a given task
     * If no uid is given, a new task is created
     *
     * @param array $taskData List of fields to save. Must include "uid" for an existing registered task
     * @return boolean True or false depending on success or failure of action
     */
    public function saveTask($taskData)
    {
        if (empty($taskData['uid'])) {
            // Create a new task instance and register the execution
            /** @var $task AbstractTask */
            $task = GeneralUtility::makeInstance(self::$taskClassName);
            $task->registerRecurringExecution(
                    $taskData['start'],
                    $taskData['interval'],
                    0,
                    false,
                    $taskData['croncmd']
            );
            // Set the data specific to external import
            $task->table = $taskData['sync'];
            $task->index = $taskData['index'];
            $task->setTaskGroup(0);
            $result = $this->scheduler->addTask($task);
        } else {
            $task = $this->scheduler->fetchTask($taskData['uid']);
            // Stop any existing execution(s)...
            $task->stop();
            /// ...and replace it(them) by a new one
            $task->registerRecurringExecution($taskData['start'], $taskData['interval'], 0, false,
                    $taskData['croncmd']);
            $result = $task->save();
        }
        return $result;
    }

    /**
     * Removes the registration of a given task
     *
     * @param integer $uid Primary key of the task to remove
     * @return boolean True or false depending on success or failure of action
     */
    public function deleteTask($uid)
    {
        $result = false;
        if (!empty($uid)) {
            $task = $this->scheduler->fetchTask($uid);
            // Stop any existing execution(s) and save
            $result = $this->scheduler->removeTask($task);
        }
        return $result;
    }
}
