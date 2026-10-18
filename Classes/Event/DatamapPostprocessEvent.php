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
 * Event called after imported data is stored to the database (updates or inserts).
 *
 * @phpstan-import-type Record from Data
 */
final class DatamapPostprocessEvent
{
    public function __construct(
        /**
         * @var array<int|string, Record> Stored data, structured as TCE datamap with additional information
         */
        private array $data,
        /**
         * @var array<string, array<int|string, Record>> Stored data, *really* structured as TCE datamap, i.e. first array dimension is table name, then records with additional information
         */
        private array $structuredData,
        /**
         * @var Importer Back-reference to the calling Importer instance
         */
        private Importer $importer
    ) {}

    public function getImporter(): Importer
    {
        return $this->importer;
    }

    /**
     * @deprecated since 9.1, will be removed in 10.0. Use getStructuredData() instead.
     * @return array<int|string, Record>
     */
    public function getData(): array
    {
        trigger_error(
            'DatamapPostprocessEvent::getData() is deprecated, use getStructuredData() instead.',
            E_USER_DEPRECATED
        );
        return $this->data;
    }

    /**
     * @return array<string, array<int|string, Record>>
     */
    public function getStructuredData(): array
    {
        return $this->structuredData;
    }
}
