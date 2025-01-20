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

use Cobweb\ExternalImport\Domain\Model\Dto\QueryParameters;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Repository for the log table.
 */
class LogRepository
{
    protected static string $table = 'tx_externalimport_domain_model_log';

    public function countAll(): int
    {
        $query = $this->getQueryBuilder();
        try {
            return $query->count('*')
                ->from(self::$table)
                ->executeQuery()
                ->fetchOne();
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Performs a search on the database based on given criteria, with ordering and pagination.
     *
     * @param QueryParameters $queryParameters
     * @return array
     * @throws \Doctrine\DBAL\Exception
     */
    public function findBySearch(QueryParameters $queryParameters): array
    {
        $query = $this->getQueryBuilder();
        $query->select('*')
            ->from(self::$table);
        if ($queryParameters->getSearch() !== '' && count($queryParameters->getSearchColumns()) > 0) {
            $query->where(
                $query->expr()->or(
                    ...$this->assembleSearchConditions(
                        $query,
                        $queryParameters->getSearch(),
                        $queryParameters->getSearchColumns()
                    )
                )
            );
        }
        // Set ordering
        if ($queryParameters->getOrder() !== '') {
            $query->orderBy(
                $queryParameters->getOrder(),
                $queryParameters->getDirection()
            );
        }
        // Set limit (pagination)
        if ($queryParameters->getLimit() > 0) {
            $query->setMaxResults($queryParameters->getLimit());
            $query->setFirstResult($queryParameters->getOffset());
        }
        return $query->executeQuery()->fetchAllAssociative();
    }

    /**
     * Performs a search on the database based on given criteria, with ordering and pagination.
     *
     * This method is similar to findBySearch, but returns the full possible record
     * count, i.e. it does not apply offset nor limit.
     *
     * @param QueryParameters $queryParameters
     * @return int
     * @throws \Doctrine\DBAL\Exception
     */
    public function countBySearch(QueryParameters $queryParameters): int
    {
        $query = $this->getQueryBuilder();
        $query->count('*')
            ->from(self::$table);
        if ($queryParameters->getSearch() !== '' && count($queryParameters->getSearchColumns()) > 0) {
            $query->where(
                $query->expr()->or(
                    ...$this->assembleSearchConditions(
                        $query,
                        $queryParameters->getSearch(),
                        $queryParameters->getSearchColumns()
                    )
                )
            );
        }
        return $query->executeQuery()->fetchOne();
    }

    /**
     * Assembles the search conditions.
     *
     * @param QueryBuilder $query
     * @param string $search String to search for
     * @param array $searchColumns List of columns to search in
     * @return array
     */
    protected function assembleSearchConditions(QueryBuilder $query, string $search, array $searchColumns): array
    {
        $searchConditions = [];
        $search = '%' . $query->escapeLikeWildcards($search) . '%';
        foreach ($searchColumns as $column) {
            $searchConditions[] = $query->expr()->like(
                $column,
                $query->createNamedParameter($search)
            );
        }
        return $searchConditions;
    }

    public function insert(array $logData): void
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder->insert(self::$table)
            ->values($logData)
            ->executeStatement();
    }

    protected function getQueryBuilder(): QueryBuilder
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::$table);
    }
}
