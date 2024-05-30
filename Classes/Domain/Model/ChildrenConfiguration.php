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

namespace Cobweb\ExternalImport\Domain\Model;

/**
 * Model class for the children configuration of a given column
 */
class ChildrenConfiguration
{
    /**
     * @var array Unprocessed children configuration
     */
    protected array $baseData = [];

    /**
     * @var array List of columns used to check for entries to update
     */
    protected array $controlColumnsForUpdate = [];

    /**
     * @var array List of columns used to check for entries to delete
     */
    protected array $controlColumnsForDelete = [];

    /**
     * @var bool Flags for each DB operation related to child records
     */
    protected bool $insertAllowed = true;
    protected bool $updateAllowed = true;
    protected bool $deleteAllowed = true;

    protected array $sorting = [];

    /**
     * @return array
     */
    public function getBaseData(): array
    {
        return $this->baseData;
    }

    /**
     * @param array $baseData
     */
    public function setBaseData(array $baseData): void
    {
        $this->baseData = $baseData;
    }

    /**
     * Returns the value of the given property
     *
     * @param string $property
     * @return mixed|null
     */
    public function getBaseDataProperty(string $property)
    {
        return $this->baseData[$property] ?? null;
    }

    /**
     * @return array
     */
    public function getControlColumnsForUpdate(): array
    {
        return $this->controlColumnsForUpdate;
    }

    /**
     * @param array $controlColumnsForUpdate
     */
    public function setControlColumnsForUpdate(array $controlColumnsForUpdate): void
    {
        $this->controlColumnsForUpdate = $controlColumnsForUpdate;
    }

    /**
     * @return array
     */
    public function getControlColumnsForDelete(): array
    {
        return $this->controlColumnsForDelete;
    }

    /**
     * @param array $controlColumnsForDelete
     */
    public function setControlColumnsForDelete(array $controlColumnsForDelete): void
    {
        $this->controlColumnsForDelete = $controlColumnsForDelete;
    }

    /**
     * Set the related operation flag (convenience method for switching between operation
     * names and setter methods).
     *
     * @param string $operation
     * @param bool $flag
     */
    public function setAllowedOperation(string $operation, bool $flag): void
    {
        switch ($operation) {
            case 'insert':
                $this->setInsertAllowed($flag);
                break;
            case 'update':
                $this->setUpdateAllowed($flag);
                break;
            case 'delete':
                $this->setDeleteAllowed($flag);
        }
    }

    /**
     * @return bool
     */
    public function isInsertAllowed(): bool
    {
        return $this->insertAllowed;
    }

    /**
     * @param bool $insertAllowed
     */
    public function setInsertAllowed(bool $insertAllowed): void
    {
        $this->insertAllowed = $insertAllowed;
    }

    /**
     * @return bool
     */
    public function isUpdateAllowed(): bool
    {
        return $this->updateAllowed;
    }

    /**
     * @param bool $updateAllowed
     */
    public function setUpdateAllowed(bool $updateAllowed): void
    {
        $this->updateAllowed = $updateAllowed;
    }

    /**
     * @return bool
     */
    public function isDeleteAllowed(): bool
    {
        return $this->deleteAllowed;
    }

    /**
     * @param bool $deleteAllowed
     */
    public function setDeleteAllowed(bool $deleteAllowed): void
    {
        $this->deleteAllowed = $deleteAllowed;
    }

    /**
     * @return array
     */
    public function getSorting(): array
    {
        return $this->sorting;
    }

    /**
     * @param array $sorting
     */
    public function setSorting(array $sorting): void
    {
        $this->sorting = $sorting;
    }
}
