<?php
namespace Cobweb\ExternalImport\Validator;

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

use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * This class parses the "column" part of an External Import configuration
 * and reports errors and other glitches.
 *
 * NOTE: this is not a strict Extbase Validator.
 *
 * @package Cobweb\ExternalImport\Validator
 */
class ColumnConfigurationValidator extends AbstractConfigurationValidator
{
    /**
     * Validates the given configuration.
     *
     * @param string $table Name of the table to which the configuration applies
     * @param array $ctrlConfiguration "ctrl" configuration to check
     * @param array $columnConfiguration Column configuration to check (unused when checking a "ctrl" configuration)
     * @return bool
     */
    public function isValid($table, $ctrlConfiguration, $columnConfiguration = null)
    {
        // Validate properties used to choose the import value
        $this->validateDataSettingProperties($ctrlConfiguration, $columnConfiguration);

        // Return the global validation result
        return parent::isValid($table, $ctrlConfiguration, $columnConfiguration);
    }

    /**
     * Validates that the column configuration contains the appropriate properties for
     * choosing the value to import, depending on the data type (array or XML).
     *
     * @param array $ctrlConfiguration "ctrl" configuration to check
     * @param array $columnConfiguration Column configuration to check (unused when checking a "ctrl" configuration)
     */
    public function validateDataSettingProperties($ctrlConfiguration, $columnConfiguration)
    {
        // For data of type "array", either a "field" or a "value" property are needed
        if ($ctrlConfiguration['data'] === 'array') {
            if (!isset($columnConfiguration['field']) && !isset($columnConfiguration['value'])) {
                // NOTE: validation result is arbitrarily added to the "field" property
                $this->addResult(
                        'field',
                        LocalizationUtility::translate(
                                'LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:missingPropertiesForArrayData',
                                'external_import'
                        ),
                        FlashMessage::ERROR
                );
            }
        // For data of type "xml", any of "field", "value", "attribute" or "xpath" must be set
        } elseif ($ctrlConfiguration['data'] === 'xml') {
            if (!isset($columnConfiguration['field']) && !isset($columnConfiguration['value']) && !isset($columnConfiguration['attribute']) && !isset($columnConfiguration['xpath'])) {
                // NOTE: validation result is arbitrarily added to the "field" property
                $this->addResult(
                        'field',
                        LocalizationUtility::translate(
                                'LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:missingPropertiesForArrayData',
                                'external_import'
                        ),
                        FlashMessage::ERROR
                );
            }
        }
    }

}