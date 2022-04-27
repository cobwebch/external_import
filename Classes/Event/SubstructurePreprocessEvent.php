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
 * Event for manipulating a single substructure before it is accessed with the keys defined with the "substructureFields" property.
 *
 * @package Cobweb\ExternalImport\Event
 */
final class SubstructurePreprocessEvent
{
    /**
     * @var Importer Back-reference to the calling Importer instance
     */
    protected $importer;

    /**
     * @var array The substructure to handle
     */
    protected $substructure = [];

    /**
     * @var array The current substructure configuration
     */
    protected $substructureConfiguration = [];

    /**
     * @var string The name of the column being handled
     */
    protected $column = '';

    public function __construct(array $substructure, array $substructureConfiguration, string $column, Importer $importer)
    {
        $this->substructure = $substructure;
        $this->substructureConfiguration = $substructureConfiguration;
        $this->column = $column;
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
    public function getSubstructureConfiguration(): array
    {
        return $this->substructureConfiguration;
    }

    /**
     * @return string
     */
    public function getColumn(): string
    {
        return $this->column;
    }

    /**
     * @return array
     */
    public function getSubstructure(): array
    {
        return $this->substructure;
    }

    /**
     * @param array $substructure
     */
    public function setSubstructure(array $substructure): void
    {
        $this->substructure = $substructure;
    }

}