<?php

declare(strict_types=1);

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

namespace Cobweb\ExternalImport\Event;

use Cobweb\ExternalImport\Domain\Model\Configuration;
use Cobweb\ExternalImport\Importer;

/**
 * Event for manipulating the External Import configuration before the run starts.
 *
 * Note that this means in particular that the modified configuration will go through the
 * ValidateConfigurationStep, so changes need to respect the enforced checks.
 */
class ChangeConfigurationBeforeRunEvent
{
    public function __construct(protected Importer $importer, protected Configuration $configuration) {}

    public function getImporter(): Importer
    {
        return $this->importer;
    }

    public function getConfiguration(): Configuration
    {
        return $this->configuration;
    }

    public function setGeneralConfiguration(array $generalConfiguration): void
    {
        $this->configuration->setGeneralConfiguration($generalConfiguration);
    }

    public function setColumnsConfiguration(array $columnsConfiguration): void
    {
        $this->configuration->setColumnConfiguration($columnsConfiguration);
    }

    public function setAdditionalFieldsConfiguration(array $additionalFieldsConfiguration): void
    {
        $this->configuration->setAdditionalFields($additionalFieldsConfiguration);
    }
}
