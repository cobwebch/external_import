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

use Cobweb\ExternalImport\Validator\ColumnConfigurationValidator;
use Cobweb\ExternalImport\Validator\ControlConfigurationValidator;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Validation of the External Import configuration before starting the import process.
 *
 * @package Cobweb\ExternalImport\Step
 */
class ValidateConfigurationStep extends AbstractStep
{
    /**
     * Validates the External Import configuration.
     *
     * @return void
     */
    public function run()
    {
        $validator = GeneralUtility::makeInstance(ControlConfigurationValidator::class);
        // Check the general configuration. If ok, proceed with columns configuration
        $ctrlConfiguration = $this->configuration->getCtrlConfiguration();
        $table = $this->importer->getTableName();
        if ($validator->isValid($table, $ctrlConfiguration)) {
            $columnValidator = GeneralUtility::makeInstance(ColumnConfigurationValidator::class);
            $columnConfiguration = $this->configuration->getColumnConfiguration();
            // Loop on the table columns to check if their external configuration is valid
            foreach ($columnConfiguration as $columnName => $columnData) {
                    $isValid = $columnValidator->isValid(
                            $table,
                            $ctrlConfiguration,
                            $columnData
                    );
                    // If the column configuration is not valid, issue error message and return false
                    if (!$isValid) {
                        $this->importer->addMessage(
                                LocalizationUtility::translate(
                                        'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:configurationError',
                                        'external_import'
                                )
                        );
                        $this->abortFlag = true;
                        break;
                    }
            }

        // If general configuration is not valid, issue error message and return false
        } else {
            $this->importer->addMessage(
                    LocalizationUtility::translate(
                            'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:configurationError',
                            'external_import'
                    )
            );
            $this->abortFlag = true;
        }
    }
}