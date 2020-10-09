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
use Cobweb\ExternalImport\Validator\GeneralConfigurationValidator;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Validation of the External Import configuration before starting the import process.
 *
 * @package Cobweb\ExternalImport\Step
 */
class ValidateConfigurationStep extends AbstractStep
{
    /**
     * @var GeneralConfigurationValidator
     */
    protected $generalValidator;

    /**
     * @var ColumnConfigurationValidator
     */
    protected $columnValidator;

    public function injectCtrlValidator(\Cobweb\ExternalImport\Validator\GeneralConfigurationValidator $validator): void
    {
        $this->generalValidator = $validator;
    }

    public function injectColumnValidator(\Cobweb\ExternalImport\Validator\ColumnConfigurationValidator $validator): void
    {
        $this->columnValidator = $validator;
    }

    /**
     * Validates the External Import configuration.
     *
     * @return void
     */
    public function run(): void
    {
        $generalConfiguration = $this->importer->getExternalConfiguration()->getGeneralConfiguration();
        // If there's no general configuration, issue error
        if (count($generalConfiguration) === 0) {
            $this->importer->addMessage(
                    sprintf(
                            LocalizationUtility::translate(
                                    'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:missingCtrlConfigurationError',
                                    'external_import'
                            ),
                            $this->importer->getExternalConfiguration()->getTable(),
                            $this->importer->getExternalConfiguration()->getIndex()
                    )
            );
            $this->abortFlag = true;
        } else {
            // Check the general configuration. If ok, proceed with columns configuration
            if ($this->generalValidator->isValid($this->importer->getExternalConfiguration())) {
                $columnConfiguration = $this->importer->getExternalConfiguration()->getColumnConfiguration();
                // If there's no column configuration at all, issue error
                if (count($columnConfiguration) === 0) {
                    $this->importer->addMessage(
                            sprintf(
                                    LocalizationUtility::translate(
                                            'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:missingColumnConfigurationError',
                                            'external_import'
                                    ),
                                    $this->importer->getExternalConfiguration()->getTable(),
                                    $this->importer->getExternalConfiguration()->getIndex()
                            )
                    );
                    $this->abortFlag = true;
                } else {
                    // Loop on the table columns to check if their external configuration is valid
                    foreach ($columnConfiguration as $columnName => $columnData) {
                        $isValid = $this->columnValidator->isValid(
                                $this->importer->getExternalConfiguration(),
                                $columnName
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
}