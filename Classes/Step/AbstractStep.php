<?php
namespace Cobweb\ExternalImport\Step;

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

use Cobweb\ExternalImport\Domain\Model\Configuration;
use Cobweb\ExternalImport\Domain\Model\Data;
use Cobweb\ExternalImport\Importer;

/**
 * Abstract class from which all External Import process steps **must** inherit.
 *
 * @package Cobweb\ExternalImport\Step
 */
abstract class AbstractStep
{
    /**
     * @var Data Object encapsulating the data being imported/processed
     */
    protected $data;

    /**
     * @var Configuration Current External Import configuration
     */
    protected $configuration;

    /**
     * @var bool The import process is aborted if this flag is set to true during the current step
     */
    protected $abortFlag = false;

    /**
     * @var Importer Back-reference to the Importer
     */
    protected $importer;

    /**
     * Performs the actual tasks of the step.
     *
     * @return void
     */
    abstract public function run();

    /**
     * Sets the preview data for the Importer class.
     *
     * @param mixed $data
     * @return void
     */
    public function setPreviewData($data)
    {
        if ($this->importer->isPreview()) {
            $this->importer->setPreviewData($data);
        }
    }

    /**
     * @return Data
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param Data $data
     */
    public function setData(Data $data)
    {
        $this->data = $data;
    }

    /**
     * @return Configuration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param Configuration $configuration
     */
    public function setConfiguration(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @return bool
     */
    public function isAbortFlag()
    {
        return $this->abortFlag;
    }

    /**
     * @param bool $abortFlag
     */
    public function setAbortFlag($abortFlag)
    {
        $this->abortFlag = $abortFlag;
    }

    /**
     * @return Importer
     */
    public function getImporter()
    {
        return $this->importer;
    }

    /**
     * @param Importer $importer
     */
    public function setImporter(Importer $importer)
    {
        $this->importer = $importer;
    }
}