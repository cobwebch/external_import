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
class Data
{
    /**
     * @var mixed The data to import, before it has been processed in any way
     */
    protected $rawData;

    /**
     * @var array Array available for storing any kind of data that will be passed from step to step
     */
    protected array $extraData = [];

    /**
     * @var array Array of data being imported
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
     * @return array
     */
    public function getExtraData(): array
    {
        return $this->extraData;
    }

    /**
     * @param array $extraData
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
     * @return array
     */
    public function getRecords(): array
    {
        return $this->records;
    }

    /**
     * @param array $records
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
