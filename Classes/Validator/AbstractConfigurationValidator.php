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

/**
 * Base validation class for External Import configurations.
 *
 * NOTE: this is not a strict Extbase Validator.
 *
 * @package Cobweb\ExternalImport\Validator
 */
abstract class AbstractConfigurationValidator
{
    /**
     * @var string Name of the table for which the configuration is checked
     */
    protected $table;

    /**
     * @var array List of validation messages, grouped by severity
     */
    protected $validationResults = array();

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
        $this->table = $table;
        // Consider that the configuration does not validate if there's at least one error or one warning
        $errorResults = $this->getResultsForSeverity(FlashMessage::ERROR);
        $warningResults = $this->getResultsForSeverity(FlashMessage::WARNING);
        return count($errorResults) + count($warningResults) === 0;
    }

    /**
     * Adds a validation result for a given property.
     *
     * @param string $property Name of the property
     * @param string $message Validation result message
     * @param int $severity Severity level (based on FlashMessages)
     * @return void
     */
    public function addResult($property, $message, $severity = FlashMessage::WARNING)
    {
        $this->validationResults[$property] = array(
            'severity' => $severity,
            'message' => $message
        );
    }

    /**
     * Returns all the validation results.
     *
     * @return array
     */
    public function getAllResults()
    {
        return $this->validationResults;
    }

    /**
     * Returns the validation result for the given property.
     *
     * @param string $property Name of the property
     * @return array|null
     */
    public function getResultForProperty($property)
    {
        return array_key_exists($property, $this->validationResults) ? $this->validationResults[$property] : null;
    }

    /**
     * Returns all validation results for a given severity level.
     *
     * @param int $severity Severity level
     * @return array
     */
    public function getResultsForSeverity($severity)
    {
        $results = array();
        foreach ($this->validationResults as $property => $validationResult) {
            if ($validationResult['severity'] === $severity) {
                $results[$property] = $validationResult['message'];
            }
        }
        return $results;
    }
}