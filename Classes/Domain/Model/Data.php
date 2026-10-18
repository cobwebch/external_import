<?php

declare(strict_types=1);

namespace Cobweb\ExternalImport\Domain\Model;

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
 * Pseudo-domain model for the data being handled by the import process.
 */
/**
 * @phpstan-type Record array<string, mixed>
 * @phpstan-type RecordList list<Record>
 */
class Data
{
    /**
     * @var mixed The data to import, before it has been processed in any way
     */
    protected $rawData;

    /**
     * @var array<string, mixed> Array available for storing any kind of data that will be passed from step to step
     */
    protected array $extraData = [];

    /**
     * Array of data being imported
     *
     * NOTE: this is a list of records until the StoreDataStep, which replaces it with
     * the list of stored records for each table (the table name being the key)
     *
     * @var RecordList|array<string, RecordList>
     */
    protected array $records = [];

    // Indicates whether the records array is downloadable or not
    // It is up to each step to define this, the default being "not"
    protected bool $downloadable = false;

    /**
     * @return mixed
     */
    public function getRawData()
    {
        return $this->rawData;
    }

    /**
     * @param mixed $rawData
     */
    public function setRawData($rawData): void
    {
        $this->rawData = $rawData;
    }

    /**
     * @return array<string, mixed>
     */
    public function getExtraData(): array
    {
        return $this->extraData;
    }

    /**
     * @param array<string, mixed> $extraData
     */
    public function setExtraData(array $extraData): void
    {
        $this->extraData = $extraData;
    }

    /**
     * @param string $key
     * @param mixed $data
     */
    public function addExtraData(string $key, $data): void
    {
        $this->extraData[$key] = $data;
    }

    /**
     * @return RecordList|array<string, RecordList>
     */
    public function getRecords(): array
    {
        return $this->records;
    }

    /**
     * @param RecordList|array<string, RecordList> $records
     */
    public function setRecords(array $records): void
    {
        $this->records = $records;
    }

    /**
     * @return bool
     */
    public function isDownloadable(): bool
    {
        return $this->downloadable;
    }

    /**
     * @param bool $downloadable
     */
    public function setDownloadable(bool $downloadable): void
    {
        $this->downloadable = $downloadable;
    }
}
