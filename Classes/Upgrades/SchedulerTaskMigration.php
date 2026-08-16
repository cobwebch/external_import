<?php

declare(strict_types=1);

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

namespace Cobweb\ExternalImport\Upgrades;

use Cobweb\ExternalImport\Domain\Model\ConfigurationKey;
use Cobweb\ExternalImport\Task\AutomatedSyncTask;
use Cobweb\ExternalImport\Task\SynchronizationTask;
use TYPO3\CMS\Core\Attribute\UpgradeWizard;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Upgrades\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Core\Upgrades\UpgradeWizardInterface;
use TYPO3\CMS\Scheduler\Task\TaskSerializer;

/**
 * Migrate from old to new synchronization task.
 *
 * TODO: remove in next major version
 */
#[UpgradeWizard('externalImport_schedulerTaskMigration')]
final class SchedulerTaskMigration implements UpgradeWizardInterface
{
    public function __construct(
        private ConnectionPool $connectionPool,
        private TaskSerializer $taskSerializer,
    ) {}

    public function getTitle(): string
    {
        return 'Migrate old AutomatedSyncTask to newer SynchronizationTask';
    }

    public function getDescription(): string
    {
        return '
            Extract information from existing Scheduler entries, change task type and set information in a structured way.
            Please execute "Migrate the contents of the tx_scheduler_task database table into a more structured form" wizard first.
        ';
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     * @throws \JsonException
     */
    public function executeUpdate(): bool
    {
        $failures = 0;
        $queryBuilder = $this->getQueryBuilder();
        $tasks = $queryBuilder->select('*')
            ->from('tx_scheduler_task')
            ->where(
                $queryBuilder->expr()->eq(
                    'tasktype',
                    $queryBuilder->createNamedParameter(AutomatedSyncTask::class)
                )
            )
            ->executeQuery()
            ->fetchAllAssociative();
        foreach ($tasks as $task) {
            $parameters = json_decode($task['parameters'], true, 512, JSON_THROW_ON_ERROR);
            $configurationKey = new ConfigurationKey();
            $configurationKey->setTableAndIndex($parameters['table'], (string)$parameters['index']);
            $queryBuilder = $this->getQueryBuilder();
            $result = $queryBuilder->update('tx_scheduler_task')
                ->set('tasktype', SynchronizationTask::class)
                ->set('sync_item', $configurationKey->getConfigurationKey())
                ->set('sync_storage', $task['parameters']['storage'] ?? 0)
                ->where(
                    $queryBuilder->expr()->eq('uid', $task['uid'])
                )
                ->executeStatement();
            if ($result < 1) {
                $failures++;
            }
        }
        return $failures === 0;
    }

    /**
     * Return true if at least on task of type AutomatedSyncTask is found
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function updateNecessary(): bool
    {
        $queryBuilder = $this->getQueryBuilder();
        $tasks = $queryBuilder->count('tasktype')
            ->from('tx_scheduler_task')
            ->where(
                $queryBuilder->expr()->eq(
                    'tasktype',
                    $queryBuilder->createNamedParameter(AutomatedSyncTask::class)
                )
            )
            ->executeQuery()
            ->fetchOne();
        return $tasks > 0;
    }

    public function getPrerequisites(): array
    {
        return [DatabaseUpdatedPrerequisite::class];
    }

    private function getQueryBuilder(): QueryBuilder
    {
        return $this->connectionPool->getQueryBuilderForTable('tx_scheduler_task');
    }
}
