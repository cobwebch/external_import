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
 * Event for manipulating the data of a single record before it is updated in the database.
 *
 * @package Cobweb\ExternalImport\Event
 */
final class UpdateRecordPreprocessEvent
{
    /**
     * @var Importer Back-reference to the calling Importer instance
     */
    protected $importer;

    /**
     * @var array The record currently being prepared for update
     */
    protected $record = [];

    /**
     * @var int The primary key of the record
     */
    protected $uid;

    public function __construct(int $uid, array $record, Importer $importer)
    {
        $this->uid = $uid;
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
     * @return int
     */
    public function getUid(): int
    {
        return $this->uid;
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