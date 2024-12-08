<?php

declare(strict_types=1);

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

use Cobweb\ExternalImport\Domain\Model\Data;
use Cobweb\ExternalImport\ImporterAwareInterface;
use Cobweb\ExternalImport\ImporterAwareTrait;

/**
 * Abstract class from which all External Import process steps **must** inherit.
 */
abstract class AbstractStep implements ImporterAwareInterface
{
    use ImporterAwareTrait;

    /**
     * @var Data|null Object encapsulating the data being imported/processed
     */
    protected ?Data $data = null;

    /**
     * @var bool The import process is aborted if this flag is set to true during the current step
     */
    protected bool $abortFlag = false;

    /**
     * @var bool If set to true, the step is executed even if the process has been aborted
     */
    protected bool $executeDespiteAbort = false;

    /**
     * @var array List of parameters specific to the step. NOTE: this is for custom steps. Standard steps never have any parameter.
     */
    protected array $parameters = [];

    /**
     * Performs the actual tasks of the step.
     */
    abstract public function run(): void;

    /**
     * Sets the preview data for the Importer class.
     */
    public function setPreviewData(mixed $data): void
    {
        if ($this->importer->isPreview()) {
            $this->importer->setPreviewData($data);
        }
    }

    public function getData(): ?Data
    {
        return $this->data;
    }

    public function setData(Data $data): void
    {
        $this->data = $data;
    }

    public function isAbortFlag(): bool
    {
        return $this->abortFlag;
    }

    public function setAbortFlag(bool $abortFlag): void
    {
        $this->abortFlag = $abortFlag;
    }

    public function isExecuteDespiteAbort(): bool
    {
        return $this->executeDespiteAbort;
    }

    public function setExecuteDespiteAbort(bool $executeDespiteAbort): void
    {
        $this->executeDespiteAbort = $executeDespiteAbort;
    }

    /**
     * Sets the list of parameters for the (custom) step.
     */
    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }

    /**
     * Returns the list of parameters.
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Returns a specific parameter.
     *
     * @param mixed $key Key/index of the parameter
     */
    public function getParameter(mixed $key): mixed
    {
        return $this->parameters[$key] ?? null;
    }

    /**
     * Defines whether the data to import - as processed by the step - is in a format
     * that makes sense to download or not.
     *
     * The download is a feature from the preview mode in the BE module.
     *
     * Override to return true in specific steps.
     */
    public function hasDownloadableData(): bool
    {
        return false;
    }
}
