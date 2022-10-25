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
     * @var array|\DOMNodeList The substructure to handle
     */
    protected $substructure;

    /**
     * @var array The current substructure configuration
     */
    protected $substructureConfiguration = [];

    /**
     * @var string The name of the column being handled
     */
    protected $column = '';

    /**
     * @var string The type of data being handled ("array" or "xml")
     */
    protected $dataType = '';

    public function __construct($substructure, array $substructureConfiguration, string $column, string $dataType, Importer $importer)
    {
        $this->substructure = $substructure;
        $this->substructureConfiguration = $substructureConfiguration;
        $this->column = $column;
        $this->dataType = $dataType;
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
     * @return string
     */
    public function getDataType(): string
    {
        return $this->dataType;
    }

    /**
     * @return array|\DOMNodeList
     */
    public function getSubstructure()
    {
        return $this->substructure;
    }

    /**
     * @param array|\DOMNodeList $substructure
     */
    public function setSubstructure($substructure): void
    {
        $this->substructure = $substructure;
    }

}