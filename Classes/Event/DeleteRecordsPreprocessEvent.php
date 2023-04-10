<?php

declare(strict_types=1);

namespace Cobweb\ExternalImport\Event;

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

use Cobweb\ExternalImport\Importer;

/**
 * Event for manipulating the list of records that will be removed from the database.
 *
 * @package Cobweb\ExternalImport\Event
 */
final class DeleteRecordsPreprocessEvent
{
    /**
     * @var Importer Back-reference to the calling Importer instance
     */
    protected Importer $importer;

    /**
     * @var array List of records to be deleted (primary keys)
     */
    protected array $records = [];

    public function __construct(array $records, Importer $importer)
    {
        $this->records = $records;
        $this->importer = $importer;
    }

    /**
     * @return Importer
     */
    public function getImporter(): Importer
    {
        return $this->importer;
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