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

use Cobweb\ExternalImport\Domain\Model\Data;
use Cobweb\ExternalImport\Importer;

/**
 * Event for manipulating the data of a single record before it is inserted into the database.
 *
 * @phpstan-import-type Record from Data
 */
final class InsertRecordPreprocessEvent
{
    public function __construct(
        /**
         * @var Record The record currently being prepared for insert
         */
        private array $record,
        /**
         * @var Importer Back-reference to the calling Importer instance
         */
        private Importer $importer
    ) {}

    /**
     * @return Importer
     */
    public function getImporter(): Importer
    {
        return $this->importer;
    }

    /**
     * @return Record
     */
    public function getRecord(): array
    {
        return $this->record;
    }

    /**
     * @param Record $record
     */
    public function setRecord(array $record): void
    {
        $this->record = $record;
    }
}
