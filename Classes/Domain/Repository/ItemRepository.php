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

namespace Cobweb\ExternalImport\Domain\Repository;

use Cobweb\ExternalImport\Exception\DeletedRecordException;
use Cobweb\ExternalImport\Exception\InvalidRecordException;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Barebones repository for fetching an item from any database table,
 * with a simple equality constraint
 */
class ItemRepository
{
    /**
     * Find an item in the given DB table, matching one or more constraints
     * (with strict equality), return its primary key (uid)
     *
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws InvalidRecordException
     * @throws \Doctrine\DBAL\Exception
     * @throws DeletedRecordException
     */
    public function find(string $table, array $constraints, string $additionalConstraint = ''): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        // Get any possible record, including deleted ones
        $queryBuilder->getRestrictions()->removeAll();
        $queryConstraints = [];
        foreach ($constraints as $key => $value) {
            $queryConstraints[] = $queryBuilder->expr()->eq(
                $key,
                is_string($value) ? $queryBuilder->createNamedParameter($value) : $value
            );
        }
        if (!empty($additionalConstraint)) {
            $queryConstraints[] = $additionalConstraint;
        }
        $result = $queryBuilder->select('uid', 'deleted')
            ->from($table)
            ->where(...$queryConstraints)
            ->executeQuery();
        $uid = 0;
        $isDeleted = false;
        while ($row = $result->fetchAssociative()) {
            if ($row['deleted'] === 1) {
                $isDeleted = true;
            } else {
                $uid = $row['uid'];
            }
        }
        // No non-deleted record was found
        if ($uid === 0) {
            // If a deleted record was found, throw specific exception
            if ($isDeleted) {
                throw new DeletedRecordException(
                    sprintf(
                        'Deleted record found in table "%s" matching "%s',
                        $table,
                        serialize($constraints)
                    ),
                    1742805062
                );
            }
            throw new InvalidRecordException(
                sprintf(
                    'No record found in table "%s" matching "%s',
                    $table,
                    serialize($constraints)
                ),
                1735288514
            );
        }
        return $uid;
    }
}
