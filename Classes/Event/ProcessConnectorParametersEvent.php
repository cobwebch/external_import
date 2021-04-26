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

use Cobweb\ExternalImport\Domain\Model\Configuration;

/**
 * Event for manipulating connector parameters.
 *
 * @package Cobweb\ExternalImport\Event
 */
final class ProcessConnectorParametersEvent
{
    /**
     * @var array Connector parameters
     */
    protected $parameters = [];

    /**
     * @var Configuration Current External Import configuration
     */
    protected $externalConfiguration;

    public function __construct(array $parameters, Configuration $configuration)
    {
        $this->parameters = $parameters;
        $this->externalConfiguration = $configuration;
    }

    /**
     * Returns the parameters.
     *
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Sets the parameters.
     *
     * @param array $parameters
     */
    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }

    /**
     * Returns the External Import configuration.
     *
     * @return Configuration
     */
    public function getExternalConfiguration(): Configuration
    {
        return $this->externalConfiguration;
    }
}