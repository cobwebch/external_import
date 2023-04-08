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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class for querying the database with some precise conditions
 * (see \Cobweb\ExternalImport\Step\StoreDataStep::reviewChildRecords())
 *
 * @package Cobweb\ExternalImport\Domain\Repository
 */
class ChildrenRepository
{
    /**
     * Returns all existing records in the given table for the given condition.
     *
     * @param string $table Table to query
     * @param array $conditions Conditions to apply (field-value pairs)
     * @return array
     */
    public function findAllExistingRecords(string $table, array $conditions): array
    {
        $existingRecords = [];
        $queryBuilder = $this->prepareQueryBuilder($table, $conditions);
        $result = $queryBuilder->execute();
        while ($record = $result->fetch()) {
            $existingRecords[] = $record['uid'];
        }
        return $existingRecords;
    }

    /**
     * Returns the first existing records in the given table for the given condition.
     *
     * NOTE: it is assumed that the given conditions lead to a single record being found.
     * We don't consider other records if that is not the case.
     *
     * @param string $table Table to query
     * @param array $conditions Conditions to apply (field-value pairs)
     * @return int
     * @throws \Cobweb\ExternalImport\Exception\NoSuchRecordException
     */
    public function findFirstExistingRecord(string $table, array $conditions): int
    {
        $queryBuilder = $this->prepareQueryBuilder($table, $conditions);
        $result = $queryBuilder->execute();
        $record = $result->fetchAssociative();
        if ($record) {
            return (int)$record['uid'];
        }
        throw new \Cobweb\ExternalImport\Exception\NoSuchRecordException(
            'Record not found with the given conditions',
            1602322832
        );
    }

    /**
     * Prepares the query builder for the given table and applies the given constraints.
     *
     * @param string $table Table to query
     * @param array $conditions Conditions to apply (field-value pairs)
     * @return QueryBuilder
     */
    protected function prepareQueryBuilder(string $table, array $conditions): QueryBuilder
    {
        $constraints = [];
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        foreach ($conditions as $field => $value) {
            $constraints[] = $queryBuilder->expr()->eq(
                $field,
                is_string($value) ? $queryBuilder->createNamedParameter($value) : $value
            );
        }
        $queryBuilder->select('uid')
            ->from($table);
        if (count($constraints) > 0) {
            $queryBuilder->where(...$constraints);
        }
        return $queryBuilder;
    }
}