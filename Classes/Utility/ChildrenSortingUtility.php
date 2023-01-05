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

namespace Cobweb\ExternalImport\Utility;

use Cobweb\ExternalImport\Domain\Model\Dto\ChildrenSorting;
use Cobweb\ExternalImport\Importer;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Utility class for acting on the child records sorting information (i.e. updating the database
 * to match that information).
 */
class ChildrenSortingUtility
{
    /**
     * @var Importer
     */
    protected $importer;

    public function __construct(Importer $importer)
    {
        $this->importer = $importer;
    }

    /**
     * Executes the sorting of the child records based on the given sorting information
     *
     * @param ChildrenSorting $sortingInformation
     * @return void
     */
    public function sortChildRecords(ChildrenSorting $sortingInformation): void
    {
        $information = $sortingInformation->getSortingInformation();
        // Restructure information for each table, to have a list of sorting information for each sorting field
        foreach ($information as $table => $items) {
            $sortedChildren = [];
            foreach ($items as $uid => $item) {
                foreach ($item as $sortingField => $sortingValue) {
                    if (!isset($sortedChildren[$sortingField])) {
                        $sortedChildren[$sortingField] = [];
                    }
                    $sortedChildren[$sortingField][$uid] = $sortingValue;
                }
            }
            foreach ($sortedChildren as $sortingField => $children) {
                // Sort children by value
                asort($children);
                // Renumber all values as consecutive numbers
                $position = 1;
                $finalSorting = [];
                foreach ($children as $uid => $child) {
                    $finalSorting[$uid] = $position;
                    $position++;
                }
                // Actually sort the child records by applying to changes to the database
                $this->updateChildrenSortingField(
                    $table,
                    $sortingField,
                    $finalSorting
                );
            }
        }
    }

    /**
     * Updates the sorting information in the given table for the given records
     *
     * @param string $table Name of the affected table
     * @param string $field Name of the sorting field
     * @param array $sorting List of records to update (uid - value pairs)
     * @return void
     */
    protected function updateChildrenSortingField(string $table, string $field, array $sorting): void
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        foreach ($sorting as $uid => $value) {
            try {
                $queryBuilder->update($table)
                    ->set($field, $value, true, \PDO::PARAM_INT)
                    ->where(
                        $queryBuilder->expr()->eq('uid', $uid)
                    )
                    ->execute();
            } catch (\Exception $e) {
                $this->importer->debug(
                    sprintf(
                        'Could not update sorting information for record %d in table %s and field %s',
                        $uid,
                        $table,
                        $field
                    ),
                    3
                );
            }
        }
    }
}