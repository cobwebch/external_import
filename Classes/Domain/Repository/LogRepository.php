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

use Cobweb\ExternalImport\Domain\Model\Dto\QueryParameters;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Repository for the log table.
 *
 * @author Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package TYPO3
 * @subpackage tx_externalimport
 */
class LogRepository extends Repository
{

    public function __toString()
    {
        return self::class;
    }

    public function initializeObject(): void
    {
        /** @var Typo3QuerySettings $querySettings */
        $querySettings = $this->objectManager->get(Typo3QuerySettings::class);
        $querySettings->setRespectStoragePage(false);
        $this->setDefaultQuerySettings($querySettings);
    }

    /**
     * Performs a search on the database based, with ordering and pagination.
     *
     * @param QueryParameters $queryParameters
     * @return array|\TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     */
    public function findBySearch(QueryParameters $queryParameters)
    {
        $query = $this->createQuery();
        if ($queryParameters->getSearch() !== '' && count($queryParameters->getSearchColumns()) > 0) {
            $query->matching(
                $query->logicalOr(
                        $this->assembleSearchConditions(
                                $query,
                                $queryParameters->getSearch(),
                                $queryParameters->getSearchColumns()
                        )
                )
            );
        }
        // Set ordering
        if ($queryParameters->getOrder() !== '') {
            $query->setOrderings(
                    [
                            $queryParameters->getOrder() => $queryParameters->getDirection()
                    ]
            );
        }
        // Set limit (pagination)
        if ($queryParameters->getLimit() > 0) {
            $query->setLimit($queryParameters->getLimit());
            $query->setOffset($queryParameters->getOffset());
        }
        return $query->execute();
    }

    /**
     * Performs a search on the database based, with ordering and pagination.
     *
     * This method is similar to findBySearch, but returns the full possible record
     * count, i.e. it does not apply offset nor limit.
     *
     * @param QueryParameters $queryParameters
     * @return int
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     */
    public function countBySearch(QueryParameters $queryParameters): int
    {
        $query = $this->createQuery();
        if ($queryParameters->getSearch() !== '' && count($queryParameters->getSearchColumns()) > 0) {
            $query->matching(
                $query->logicalOr(
                        $this->assembleSearchConditions(
                                $query,
                                $queryParameters->getSearch(),
                                $queryParameters->getSearchColumns()
                        )
                )
            );
        }
        return $query->execute()->count();
    }

    /**
     * Assembles the search conditions.
     *
     * @param QueryInterface $query
     * @param string $search String to search for
     * @param array $searchColumns List of columns to search in
     * @return array
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     */
    protected function assembleSearchConditions(QueryInterface $query, string $search, array $searchColumns): array
    {
        $searchConditions = [];
        $search = '%' . $search . '%';
        foreach ($searchColumns as $column) {
            // Filter on user name
            $searchConditions[] = $query->like(
                    $column,
                    $search
            );
        }
        return $searchConditions;
    }
}
