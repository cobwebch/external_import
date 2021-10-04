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
 *
 * @package Cobweb\ExternalImport\Event
 */
final class DatamapPostprocessEvent
{
    /**
     * @var Importer Back-reference to the calling Importer instance
     */
    protected $importer;

    /**
     * @var array Stored data, structured as TCE datamap with additional information
     */
    protected $data = [];

    public function __construct(array $data, Importer $importer)
    {
        $this->data = $data;
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
    public function getData(): array
    {
        return $this->data;
    }
}