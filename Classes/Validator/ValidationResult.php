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

use TYPO3\CMS\Core\Messaging\AbstractMessage;

/**
 * Utility class used to store validation results.
 *
 * @package Cobweb\ExternalImport\Validator
 */
class ValidationResult
{
    protected $results = array();

    /**
     * Adds a result to the results array.
     *
     * @param string $property
     * @param string $message
     * @param int $severity
     * @return void
     */
    public function add($property, $message, $severity = AbstractMessage::WARNING): void
    {
        $this->results[$property] = array(
            'severity' => $severity,
            'message' => $message
        );
    }

    /**
     * Returns all the validation results.
     *
     * @return array
     */
    public function getAll(): array
    {
        return $this->results;
    }

    /**
     * Returns the validation result for the given property.
     *
     * @param string $property Name of the property
     * @return array|null
     */
    public function getForProperty($property): ?array
    {
        return $this->results[$property] ?? null;
    }

    /**
     * Returns all results for a given severity level.
     *
     * @param int $severity Severity level
     * @return array
     */
    public function getForSeverity($severity): array
    {
        $results = array();
        foreach ($this->results as $property => $result) {
            if ($result['severity'] === $severity) {
                $results[$property] = $result['message'];
            }
        }
        return $results;
    }
}