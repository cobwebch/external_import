<?php

declare(strict_types=1);

namespace Cobweb\ExternalImport\Domain\Model\Dto;

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

/**
 * DTO/wrapper for storing information about the sorting of child records, with some utility methods
 * to manipulate said information.
 */
class ChildrenSorting
{
    protected $sortingInformation = [];

    /**
     * Returns true if there's at least one element in the sorting information array for at least one table
     *
     * @return bool
     */
    public function hasSortingInformation(): bool
    {
        $count = 0;
        foreach ($this->sortingInformation as $items) {
            $count += count($items);
        }
        return $count > 0;
    }

    /**
     * Returns the sorting information
     *
     * @return array
     */
    public function getSortingInformation(): array
    {
        return $this->sortingInformation;
    }

    /**
     * Resets the sorting information
     *
     * @return void
     */
    public function resetSortingInformation(): void
    {
        $this->sortingInformation = [];
    }

    /**
     * Adds sorting information for a given child record
     *
     * @param string $table Name of the child table
     * @param mixed $id Id of the child record
     * @param string $target Name of the sorting field
     * @param mixed $value Sorting value
     * @return void
     */
    public function addSortingInformation(string $table, $id, string $target, $value): void
    {
        if (!isset($this->sortingInformation[$table])) {
            $this->sortingInformation[$table] = [];
        }
        $this->sortingInformation[$table][$id] = [
            $target => (int)$value
        ];
    }

    /**
     * Replaces the (temporary) id of the given child record by a new (final) id
     *
     * @param string $table Name of the child table
     * @param mixed $oldId Old id of the child record (could have been a temporary key)
     * @param int $id Final id of the child record
     * @return void
     */
    public function replaceId(string $table, $oldId, int $id): void
    {
        if (isset($this->sortingInformation[$table][$oldId])) {
            $this->sortingInformation[$table][$id] = $this->sortingInformation[$table][$oldId];
            unset($this->sortingInformation[$table][$oldId]);
        }
    }

    /**
     * Replaces temporary keys with final ids, after database storage
     *
     * @param array $replacements Hashmap of old to new ids (normally coming from TCE)
     * @return void
     */
    public function replaceAllNewIds(array $replacements): void
    {
        foreach ($this->sortingInformation as $table => $items) {
            foreach ($items as $key => $value) {
                if (is_string($key) && strpos($key, 'NEW') === 0) {
                    if (isset($replacements[$key])) {
                        $this->sortingInformation[$table][$replacements[$key]] = $value;
                        unset($this->sortingInformation[$table][$key]);
                    }
                }
            }
        }
    }
}