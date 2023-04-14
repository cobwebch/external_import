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

class ProcessedConfiguration
{
    /**
     * @var array List of columns for which the "insert" operation is disabled
     */
    protected array $fieldsExcludedFromInserts = [];

    /**
     * @var array List of columns for which the "update" operation is disabled
     */
    protected array $fieldsExcludedFromUpdates = [];

    /**
     * @var ChildrenConfiguration[] Restructured information for every column with a "children" property
     */
    protected array $childColumns = [];

    /**
     * @return array
     */
    public function getFieldsExcludedFromInserts(): array
    {
        return $this->fieldsExcludedFromInserts;
    }

    /**
     * @param string $field
     */
    public function addFieldExcludedFromInserts(string $field): void
    {
        $this->fieldsExcludedFromInserts[] = $field;
    }

    /**
     * @param array $fieldsExcludedFromInserts
     */
    public function setFieldsExcludedFromInserts(array $fieldsExcludedFromInserts): void
    {
        $this->fieldsExcludedFromInserts = $fieldsExcludedFromInserts;
    }

    /**
     * @return array
     */
    public function getFieldsExcludedFromUpdates(): array
    {
        return $this->fieldsExcludedFromUpdates;
    }

    /**
     * @param string $field
     */
    public function addFieldExcludedFromUpdates(string $field): void
    {
        $this->fieldsExcludedFromUpdates[] = $field;
    }

    /**
     * @param array $fieldsExcludedFromUpdates
     */
    public function setFieldsExcludedFromUpdates(array $fieldsExcludedFromUpdates): void
    {
        $this->fieldsExcludedFromUpdates = $fieldsExcludedFromUpdates;
    }

    /**
     * @return array
     */
    public function getChildColumns(): array
    {
        return $this->childColumns;
    }

    /**
     * @param string $key
     * @param ChildrenConfiguration $configuration
     */
    public function addChildColumn(string $key, ChildrenConfiguration $configuration): void
    {
        $this->childColumns[$key] = $configuration;
    }

    /**
     * @param array $childColumns
     */
    public function setChildColumns(array $childColumns): void
    {
        $this->childColumns = $childColumns;
    }

    /**
     * Returns true if there is at least one child column configuration
     *
     * @return bool
     */
    public function hasChildColumns(): bool
    {
        return count($this->childColumns) > 0;
    }
}