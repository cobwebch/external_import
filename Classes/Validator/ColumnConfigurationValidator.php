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

use Cobweb\ExternalImport\Domain\Model\Configuration;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * This class parses the "column" part of an External Import configuration
 * and reports errors and other glitches.
 *
 * NOTE: this is not a strict Extbase Validator.
 *
 * @package Cobweb\ExternalImport\Validator
 */
class ColumnConfigurationValidator
{
    /**
     * @var ValidationResult
     */
    protected $results;

    public function injectValidationResult(ValidationResult $result)
    {
        $this->results = $result;
    }

    /**
     * Validates the given configuration.
     *
     * @param Configuration $configuration Configuration object to check
     * @param string $column Name of the column to check
     * @return bool
     */
    public function isValid(Configuration $configuration, $column)
    {
        // Validate properties used to choose the import value
        $this->validateDataSettingProperties(
                $configuration->getCtrlConfiguration(),
                $configuration->getConfigurationForColumn($column)
        );
        // Return the global validation result
        // Consider that the configuration does not validate if there's at least one error or one warning
        $errorResults = $this->results->getForSeverity(AbstractMessage::ERROR);
        $warningResults = $this->results->getForSeverity(AbstractMessage::WARNING);
        return count($errorResults) + count($warningResults) === 0;
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
        if ($ctrlConfiguration['data'] === 'array') {
            // For data of type "array", either a "field" or a "value" property are needed
            if (!isset($columnConfiguration['field']) && !isset($columnConfiguration['value'])) {
                // NOTE: validation result is arbitrarily added to the "field" property
                $this->results->add(
                        'field',
                        LocalizationUtility::translate(
                                'LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:missingPropertiesForArrayData',
                                'external_import'
                        ),
                        AbstractMessage::ERROR
                );
            // "value" property should not be set if another value-setting property is also defined
            } elseif (isset($columnConfiguration['field'], $columnConfiguration['value'])) {
                // NOTE: validation result is arbitrarily added to the "field" property
                $this->results->add(
                        'field',
                        LocalizationUtility::translate(
                                'LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:conflictingPropertiesForArrayData',
                                'external_import'
                        ),
                        AbstractMessage::WARNING
                );
            }
        } elseif ($ctrlConfiguration['data'] === 'xml') {
            // It is okay to have no configuration for a column. Just make sure this is really what the user wanted.
            if (!isset($columnConfiguration['field']) && !isset($columnConfiguration['value']) && !isset($columnConfiguration['attribute']) && !isset($columnConfiguration['xpath'])) {
                // NOTE: validation result is arbitrarily added to the "field" property
                $this->results->add(
                        'field',
                        LocalizationUtility::translate(
                                'LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:missingPropertiesForXmlData',
                                'external_import'
                        ),
                        AbstractMessage::NOTICE
                );
            // "value" property should not be set if another value-setting property is also defined
            } elseif (
                isset($columnConfiguration['value'])
                && (isset($columnConfiguration['field']) || isset($columnConfiguration['attribute']) || isset($columnConfiguration['xpath']))
            ) {
                // NOTE: validation result is arbitrarily added to the "field" property
                $this->results->add(
                        'field',
                        LocalizationUtility::translate(
                                'LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:conflictingPropertiesForXmlData',
                                'external_import'
                        ),
                        AbstractMessage::WARNING
                );
            }
        }
    }

    /**
     * Returns all validation results.
     *
     * @return array
     */
    public function getResults()
    {
        return $this->results->getAll();
    }

}