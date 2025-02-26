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

namespace Cobweb\ExternalImport\Updates;

use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\ChattyInterface;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * Update log entries to replace user id with user name
 *
 * TODO: remove in the next major version
 */
#[UpgradeWizard('logUsernameMigration')]
final class LogUsernameMigration implements UpgradeWizardInterface, ChattyInterface
{
    private const TABLE_NAME = 'tx_externalimport_domain_model_log';
    private OutputInterface $output;

    public function getTitle(): string
    {
        return 'Update external import logs';
    }

    public function getDescription(): string
    {
        return 'Replace user id with user name in existing external import log entries.';
    }

    public function executeUpdate(): bool
    {
        $users = $this->getBackendUsers();
        try {
            $queryBuilder = $this->getQueryBuilderForLogTable();
            $logs = $queryBuilder->select('uid', 'cruser_id')
                ->from(self::TABLE_NAME)
                ->executeQuery()
                ->fetchAllAssociative();
            foreach ($logs as $log) {
                $username = $users[$log['cruser_id']] ?? 'Unknown';
                $queryBuilder = $this->getQueryBuilderForLogTable();
                $queryBuilder->update(self::TABLE_NAME)
                    ->set(
                        'username',
                        sprintf(
                            '%s (%d)',
                            $username,
                            $log['cruser_id']
                        )
                    )
                    ->where(
                        $queryBuilder->expr()->eq('uid', $log['uid'])
                    )
                    ->executeStatement();
            }
        } catch (\Throwable $e) {
            $this->output->writeln(
                sprintf(
                    '<error>An error occurred querying the database: %s [%d]</error>',
                    $e->getMessage(),
                    $e->getCode()
                )
            );
            return false;
        }
        return true;
    }

    public function updateNecessary(): bool
    {
        // It's hard to check whether an update is really necessary or not, and since the implications
        // are minimal, let's just always do it
        return true;
    }

    public function getPrerequisites(): array
    {
        return [];
    }

    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    private function getQueryBuilderForLogTable(): QueryBuilder
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);
    }

    /**
     * Get the list of existing backend users
     */
    private function getBackendUsers(): array
    {
        $users = [];
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('be_users');
        try {
            $results = $queryBuilder->select('uid', 'username')
                ->from('be_users')
                ->executeQuery()
                ->fetchAllAssociative();
            foreach ($results as $result) {
                $users[$result['uid']] = $result['username'];
            }
        } catch (\Doctrine\DBAL\Exception) {
            // Nothing to do, let user list be empty
        }
        return $users;
    }
}
