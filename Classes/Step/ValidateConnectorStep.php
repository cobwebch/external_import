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

use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Validation of the External Import configuration specifically for synchronize processes.
 */
class ValidateConnectorStep extends AbstractStep
{
    /**
     * Validates that the External Import configuration for a "synchronize" operation has a connector.
     */
    public function run(): void
    {
        $generalConfiguration = $this->importer->getExternalConfiguration()->getGeneralConfiguration();
        if (empty($generalConfiguration['connector'])) {
            $this->importer->addMessage(
                LocalizationUtility::translate(
                    'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:no_connector'
                )
            );
            $this->abortFlag = true;
        }
    }
}
