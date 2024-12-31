<?php

declare(strict_types=1);

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

use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;

/**
 * Utility class used to store validation results.
 */
class ValidationResult
{
    /**
     * @var array List of validation results
     */
    protected array $results = [];

    /**
     * Resets the list of results.
     */
    public function reset(): void
    {
        $this->results = [];
    }

    /**
     * Adds a result to the results array.
     *
     * @param string $property
     * @param string $message
     * @param ContextualFeedbackSeverity $severity
     */
    public function add(string $property, string $message, ContextualFeedbackSeverity $severity = ContextualFeedbackSeverity::WARNING): void
    {
        if (!isset($this->results[$property])) {
            $this->results[$property] = [];
        }
        $this->results[$property][] = [
            'severity' => $severity,
            'message' => $message,
        ];
    }

    /**
     * Returns all the validation results.
     *
     * @return array
     */
    public function getAll(): array
    {
        $this->sortResults();
        return $this->results;
    }

    /**
     * Returns the validation results for the given property.
     *
     * @param string $property Name of the property
     * @return array|null
     */
    public function getForProperty(string $property): ?array
    {
        $this->sortResults();
        return $this->results[$property] ?? null;
    }

    /**
     * Returns the number of validation results for the given property.
     *
     * @param string $property Name of the property
     * @return int
     */
    public function countForProperty(string $property): int
    {
        return count($this->results[$property] ?? []);
    }

    /**
     * Returns all results for a given severity level.
     *
     * @param ContextualFeedbackSeverity $severity Severity level
     * @return array
     */
    public function getForSeverity(ContextualFeedbackSeverity $severity): array
    {
        $listOfResults = [];
        foreach ($this->results as $property => $results) {
            $listOfResults[$property] = [];
            foreach ($results as $result) {
                if ($result['severity'] === $severity) {
                    $listOfResults[$property][] = $result['message'];
                }
            }
        }
        return $listOfResults;
    }

    /**
     * Returns the validation results for the given property and severity.
     *
     * NOTE: this is mostly used for testing, to better target results.
     *
     * @param string $property Name of the property
     * @param ContextualFeedbackSeverity $severity Severity level
     * @return array
     */
    public function getForPropertyAndSeverity(string $property, ContextualFeedbackSeverity $severity): array
    {
        $listOfResults = [];
        if (array_key_exists($property, $this->results)) {
            foreach ($this->results[$property] as $result) {
                if ($result['severity'] === $severity) {
                    $listOfResults[] = $result['message'];
                }
            }
        }
        return $listOfResults;
    }

    /**
     * Returns the number of results for a given severity level.
     *
     * @param ContextualFeedbackSeverity $severity Severity level
     * @return int
     */
    public function countForSeverity(ContextualFeedbackSeverity $severity): int
    {
        $count = 0;
        foreach ($this->results as $property => $results) {
            foreach ($results as $result) {
                if ($result['severity'] === $severity) {
                    $count++;
                }
            }
        }
        return $count;
    }

    /**
     * Sorts the result list by decreasing severity for each property.
     *
     * Should be used before returning any list of results for properties.
     */
    public function sortResults(): void
    {
        foreach ($this->results as $property => $results) {
            usort(
                $results,
                [
                    self::class,
                    'compareSeverity',
                ]
            );
            $this->results[$property] = $results;
        }
    }

    /**
     * Sorts two messages according to severity, so that more important severity comes first.
     *
     * NOTE: the higher the severity value the more important it is (see \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity).
     * So in terms of values, we want to sort by decreasing values of severity.
     *
     * @param array $a
     * @param array $b
     * @return int
     */
    public static function compareSeverity(array $a, array $b): int
    {
        if ($a['severity']->value > $b['severity']->value) {
            return -1;
        }
        if ($a['severity']->value < $b['severity']->value) {
            return 1;
        }
        return 0;
    }
}
