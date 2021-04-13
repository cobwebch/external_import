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

use TYPO3\CMS\Core\Messaging\AbstractMessage;

/**
 * Calls on the Connector at the end of the synchronize process.
 *
 * @package Cobweb\ExternalImport\Step
 */
class ConnectorCallbackStep extends AbstractStep
{

    /**
     * Evaluates a rough error status and calls back the connector.
     *
     * NOTE: this step does absolutely nothing with the data.
     *
     * @return void
     */
    public function run(): void
    {
        // Call connector's post-processing with a rough error status
        if ($this->importer->getExternalConfiguration()->getConnector() !== null) {
            $errorStatus = false;
            $messages = $this->importer->getMessages();
            if (count($messages[AbstractMessage::ERROR]) > 0) {
                $errorStatus = true;
            }
            $this->importer->getExternalConfiguration()->getConnector()->postProcessOperations(
                $this->importer->getExternalConfiguration()->getGeneralConfigurationProperty('parameters'),
                $errorStatus
            );
        }
    }
}