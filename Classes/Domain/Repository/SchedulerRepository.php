<?php

declare(strict_types=1);

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

use Cobweb\ExternalImport\Domain\Model\ConfigurationKey;
use Cobweb\ExternalImport\Exception\SchedulerRepositoryException;
use Cobweb\ExternalImport\Task\AutomatedSyncTask;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Scheduler\CronCommand\NormalizeCommand;
use TYPO3\CMS\Scheduler\Domain\Repository\SchedulerTaskRepository;
use TYPO3\CMS\Scheduler\Exception\InvalidTaskException;
use TYPO3\CMS\Scheduler\Task\TaskSerializer;
use TYPO3\CMS\Scheduler\Validation\Validator\TaskValidator;

/**
 * Pseudo-repository class for Scheduler tasks
 *
 * This is not a true repository from an Extbase point of view. It implements only a few features of a complete repository.
 */
class SchedulerRepository implements SingletonInterface
{
    /**
     * @var string Name of the related task class
     */
    public static string $taskClassName = AutomatedSyncTask::class;

    /**
     * List of all tasks (stored locally in case the repository is called several times)
     *
     * @var array
     */
    protected array $tasks = [];

    public function __construct(protected SchedulerTaskRepository $schedulerTaskRepository, protected TaskSerializer $taskSerializer)
    {
        $this->loadAllSynchronisationTasks();
    }

    /**
     * Fetches all tasks related to the external import extension.
     *
     * The return array is structured per table/index.
     *
     * @return array List of registered events/tasks, per table and index
     */
    public function fetchAllTasks(): array
    {
        $taskList = [];
        /** @var $taskObject AutomatedSyncTask */
        foreach ($this->tasks as $taskObject) {
            $configurationKey = GeneralUtility::makeInstance(ConfigurationKey::class);
            $configurationKey->setTableAndIndex($taskObject->table, (string)$taskObject->index);
            $key = $configurationKey->getConfigurationKey();
            $taskList[$key] = $this->assembleTaskInformation($taskObject);
        }
        return $taskList;
    }

    /**
     * Retrieves a scheduler task based on its id.
     *
     * @param int $uid Id of the task to retrieve
     * @return array
     * @throws \InvalidArgumentException
     */
    public function fetchTaskByUid(int $uid): array
    {
        /** @var $taskObject AutomatedSyncTask */
        foreach ($this->tasks as $taskObject) {
            if ($taskObject->getTaskUid() === $uid) {
                return $this->assembleTaskInformation($taskObject);
            }
        }
        // We didn't find a matching task, throw an exception
        throw new \InvalidArgumentException(
            'The chosen task could not be found',
            1463732926
        );
    }

    /**
     * Fetches the specific task that synchronizes all tables.
     *
     * @return array Information about the task, if defined
     * @throws \InvalidArgumentException
     */
    public function fetchFullSynchronizationTask(): array
    {
        // Check all tasks object to find the one with the "all" keyword as a table
        /** @var $taskObject AutomatedSyncTask */
        foreach ($this->tasks as $taskObject) {
            if ($taskObject->table === 'all') {
                return $this->assembleTaskInformation($taskObject);
            }
        }
        throw new \InvalidArgumentException(
            'No task registered for full synchronization',
            1337344319
        );
    }

    /**
     * Returns the list of all scheduler task groups.
     *
     * @return array
     */
    public function fetchAllGroups(): array
    {
        $groups = [
            0 => '',
        ];
        try {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('tx_scheduler_task_group');
            $rows = $queryBuilder->select('uid', 'groupName')
                ->from('tx_scheduler_task_group')
                ->orderBy('groupName')
                ->executeQuery();
            while ($row = $rows->fetchAssociative()) {
                $groups[$row['uid']] = $row['groupName'];
            }
        } catch (\Throwable) {
            // Nothing to do, let an empty groups list be returned
        }
        return $groups;
    }

    /**
     * Fetch all tasks based on the \Cobweb\ExternalImport\Task\AutomatedSyncTask class
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function loadAllSynchronisationTasks(): void
    {
        $tasks = [];
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_scheduler_task');

        $queryBuilder
            ->select('serialized_task_object')
            ->from('tx_scheduler_task')
            ->where(
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
            );

        $result = $queryBuilder->executeQuery();
        while ($row = $result->fetchAssociative()) {
            try {
                $task = $this->taskSerializer->deserialize($row['serialized_task_object']);
            } catch (InvalidTaskException) {
                continue;
            }

            // Add the task to the list only if it is valid
            if (get_class($task) === self::$taskClassName && (new TaskValidator())->isValid($task)) {
                $task->setScheduler();
                $this->tasks[] = $task;
            }
        }
    }

    /**
     * Grabs the information about a given external import task and stores it into an array
     *
     * @param AutomatedSyncTask $taskObject The task to handle
     * @return array The information about the task
     */
    protected function assembleTaskInformation(AutomatedSyncTask $taskObject): array
    {
        $cronCommand = $taskObject->getExecution()->getCronCmd();
        $interval = $taskObject->getExecution()->getInterval();
        $displayFormat = $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] . ' ' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'];
        // TODO: this used to be configured according to $GLOBALS['TYPO3_CONF_VARS']['SYS']['USdateFormat']. Something else may emerge in the future.
        // Reference: https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/12.0/Breaking-96550-TYPO3_CONF_VARSSYSUSdateFormatRemoved.html
        $editFormat = 'H:i d-m-Y';

        $startTimestamp = $taskObject->getExecution()->getStart();
        return [
            'uid' => $taskObject->getTaskUid(),
            'table' => $taskObject->table,
            'index' => $taskObject->index,
            'disabled' => $taskObject->isDisabled(),
            // Format date as needed for display
            'nextexecution' => date($displayFormat, $taskObject->getExecutionTime()),
            'interval' => $interval,
            'croncmd' => $cronCommand,
            'frequency' => ($cronCommand !== '') ? $cronCommand : $interval,
            'frequencyText' => ($cronCommand !== '') ? $cronCommand : LocalizationUtility::translate(
                'number_of_seconds',
                'external_import',
                [$interval]
            ),
            'group' => $taskObject->getTaskGroup(),
            // Format date and time as needed for form input
            'startTimestamp' => $startTimestamp,
            'startDate' => empty($startTimestamp) ? '' : date(
                $editFormat,
                $taskObject->getExecution()->getStart()
            ),
        ];
    }

    /**
     * Saves or updates a given task.
     *
     * If no uid is given, a new task is created.
     *
     * @param array $taskData List of fields to save. Must include "uid" for an existing registered task
     * @throws SchedulerRepositoryException
     */
    public function saveTask(array $taskData): void
    {
        if ($taskData['uid'] === 0) {
            // Create a new task instance and register the execution
            /** @var $task AutomatedSyncTask */
            $task = GeneralUtility::makeInstance(self::$taskClassName);
            $task->registerRecurringExecution(
                $taskData['start'] ?? 0,
                $taskData['interval'],
                0,
                false,
                $taskData['croncmd']
            );
            // Set the data specific to external import
            $task->table = $taskData['table'];
            $task->index = $taskData['index'];
            $task->setTaskGroup($taskData['group']);
            $result = $this->schedulerTaskRepository->add($task);
        } else {
            $task = $this->schedulerTaskRepository->findByUid((int)$taskData['uid']);
            // Stop any existing execution(s)...
            $task->stop();
            /// ...and replace it(them) by a new one
            $task->registerRecurringExecution(
                $taskData['start'] ?? 0,
                $taskData['interval'],
                0,
                false,
                $taskData['croncmd']
            );
            $task->setTaskGroup($taskData['group']);
            $result = $task->save();
        }
        if ($result === false) {
            throw new SchedulerRepositoryException(
                LocalizationUtility::translate(
                    'taskSaveFailed',
                    'external_import'
                ),
                1509896783
            );
        }
    }

    /**
     * Removes the registration of a given task.
     *
     * @param int $uid Primary key of the task to remove
     * @return bool True or false depending on success or failure of action
     */
    public function deleteTask(int $uid): bool
    {
        if ($uid > 0) {
            $task = $this->schedulerTaskRepository->findByUid($uid);
            // Stop any existing execution(s) and save
            return $this->schedulerTaskRepository->remove($task);
        }
        return false;
    }

    /**
     * Prepares the arguments as proper data for a scheduler task.
     *
     * @param string $frequency Automation frequency
     * @param int $group Scheduler task group
     * @param string $table Name of the table for which to set an automated task for
     * @param string $index Index for which to set an automated task for
     * @param int $uid Id of an existing task (will be 0 for a new task)
     * @return array
     */
    public function prepareTaskData(
        string $frequency,
        int $group,
        string $table = '',
        string $index = '',
        int $uid = 0
    ): array {
        // Assemble base data
        $taskData = [
            'uid' => $uid,
            'table' => $table,
            'index' => $index,
            'group' => $group,
            'interval' => 0,
            'croncmd' => '',
        ];
        // Handle frequency, which may be a simple number of seconds or a cron command
        // Try interpreting the frequency as a cron command
        try {
            NormalizeCommand::normalize($frequency);
            $taskData['croncmd'] = $frequency;
        } // If the cron command was invalid, we may still have a valid frequency in seconds
        catch (\Exception) {
            // Check if the frequency is a valid number
            // If yes, assume it is a frequency in seconds
            if (is_numeric($frequency)) {
                $taskData['interval'] = (int)$frequency;
            }
        }
        return $taskData;
    }
}
