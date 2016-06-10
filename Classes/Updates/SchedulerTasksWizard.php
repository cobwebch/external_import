<?php
namespace Cobweb\ExternalImport\Updates;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\AbstractUpdate;

// Manually include the old class
require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('external_import', 'Classes/Updates/class.Tx_ExternalImport_Autosync_Scheduler_Task.php'));

/**
 * Example transformation functions for the 'external_import' extension
 *
 * @author Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package TYPO3
 * @subpackage tx_externalimport
 */
class SchedulerTasksWizard extends AbstractUpdate
{
    /**
     * @var string Name of the old task class
     */
    static protected $legacyClassName = 'tx_externalimport_autosync_scheduler_Task';

    public function __construct()
    {
        $this->title = 'Migrate External Import Scheduler tasks';
    }

    /**
     * Checks if the update is required.
     *
     * Returns true if there's at least one task to migrate.
     *
     * @param string &$description The description for the update
     * @return bool Whether an update is required (TRUE) or not (FALSE)
     */
    public function checkForUpdate(&$description)
    {
        try {
            $tasksForUpdate = $this->fetchTaskRecords();
        }
        catch (\Exception $e) {
            $tasksForUpdate = array();
        }
        $numberOfTasks = count($tasksForUpdate);
        if ($numberOfTasks === 0) {
            return false;
        } else {
            if ($numberOfTasks === 1) {
                $description = 'There is <strong>one</strong> old External Import task to migrate. All data will be preserved.';
            } else {
                $description = sprintf(
                        'There are <strong>%d</strong> old External Import tasks to migrate. All data will be preserved.',
                        $numberOfTasks
                );
            }
            return true;
        }
    }

    /**
     * Updates the legacy External Import tasks.
     *
     * @param array &$dbQueries Queries done in this update
     * @param mixed &$customMessages Custom messages
     * @return bool Whether everything went smoothly or not
     */
    public function performUpdate(array &$dbQueries, &$customMessages)
    {
        $db = $this->getDatabaseConnection();
        try {
            $tasksForUpdate = $this->fetchTaskRecords();
            $successfulUpdates = 0;
            foreach ($tasksForUpdate as $taskData) {
                // Get the data from the object
                /** @var \tx_externalimport_autosync_scheduler_Task $oldTaskObject */
                $oldTaskObject = $taskData['unserialized_task_object'];

                // Create an instance of the new task
                /** @var AutomatedSyncTask $newTaskObject */
                $newTaskObject = GeneralUtility::makeInstance(AutomatedSyncTask::class);

                // Transfer data from old object to new task
                $newTaskObject->table = $oldTaskObject->table;
                $newTaskObject->index = $oldTaskObject->index;
                $newTaskObject->setTaskUid($oldTaskObject->getTaskUid());
                $newTaskObject->setDisabled($oldTaskObject->isDisabled());
                $newTaskObject->setExecution($oldTaskObject->getExecution());
                $newTaskObject->setExecutionTime($oldTaskObject->getExecutionTime());
                $newTaskObject->setDescription($oldTaskObject->getDescription());
                $newTaskObject->setTaskGroup($oldTaskObject->getTaskGroup());

                // Update database with new object
                $result = $db->exec_UPDATEquery(
                        'tx_scheduler_task',
                        'uid = ' . (int)$taskData['uid'],
                        array(
                                'serialized_task_object' => serialize($newTaskObject)
                        )
                );
                if ($result) {
                    $successfulUpdates++;
                }
                // Log query
                $dbQueries[] = $db->debug_lastBuiltQuery;
            }

            // Report about upgrade process
            if ($successfulUpdates === count($tasksForUpdate)) {
                $customMessages = 'All tasks were successfully migrated';
                return true;
            } else {
                $customMessages = 'Not all tasks could be migrated. Please check the list of queries to try and find what went wrong.';
                return false;
            }
        }
        catch (\Exception $e) {
            $customMessages = sprintf(
                    'An error occurred trying to fetch the tasks to update: %s (%s)',
                    $e->getMessage(),
                    $e->getCode()
            );
            return false;
        }
    }

    /**
     * Returns the list of task objects that need updating.
     *
     * @throws \InvalidArgumentException
     * @return array
     */
    protected function fetchTaskRecords()
    {
        $recordsToUpdate = array();
        // Fetch all Scheduler tasks
        $rows = $this->getDatabaseConnection()->exec_SELECTgetRows(
                'uid, serialized_task_object',
                'tx_scheduler_task',
                ''
        );
        // Keep only tasks whose object matches the legacy external import task
        foreach ($rows as $row) {
            $object = unserialize($row['serialized_task_object']);
            if ($object instanceof self::$legacyClassName) {
                $row['unserialized_task_object'] = $object;
                $recordsToUpdate[] = $row;
            }
        }
        return $recordsToUpdate;
    }
}
