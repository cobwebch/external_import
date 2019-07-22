<?php
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
 *
 * @package Cobweb\ExternalImport\Domain\Model
 */
class Data
{
    /**
     * @var mixed The data to import, before it has been processed in any way
     */
    protected $rawData;

    /**
     * @var array Array of data being imported
     */
    protected $records = [];

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
}