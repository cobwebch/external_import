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
 * Event for manipulating the data of a single record before it is inserted into the database.
 */
final class InsertRecordPreprocessEvent
{
    /**
     * @var Importer Back-reference to the calling Importer instance
     */
    protected Importer $importer;

    /**
     * @var array The record currently being prepared for insert
     */
    protected array $record = [];

    public function __construct(array $record, Importer $importer)
    {
        $this->record = $record;
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
    public function getRecord(): array
    {
        return $this->record;
    }

    /**
     * @param array $record
     */
    public function setRecord(array $record): void
    {
        $this->record = $record;
    }
}
