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
 * Event called after imported data is stored to the database (updates or inserts).
 */
final class DatamapPostprocessEvent
{
    public function __construct(
        /**
         * @var array Stored data, structured as TCE datamap with additional information
         */
        protected array $data,
        /**
         * @var array Stored data, *really* structured as TCE datamap, i.e. first array dimension is table name, then records with additional information
         */
        protected array $structuredData,
        /**
         * @var Importer Back-reference to the calling Importer instance
         */
        protected Importer $importer
    )
    {
    }

    public function getImporter(): Importer
    {
        return $this->importer;
    }

    /**
     * TODO: deprecate in next major version, remove in following major version
     */
    public function getData(): array
    {
        return $this->data;
    }

    public function getStructuredData(): array
    {
        return $this->structuredData;
    }
}
