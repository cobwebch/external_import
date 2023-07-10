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

use Cobweb\ExternalImport\Exception\CriticalFailureException;
use Cobweb\ExternalImport\Exception\InvalidRecordException;
use Cobweb\ExternalImport\Importer;
use Cobweb\ExternalImport\Utility\MappingUtility;
use Cobweb\ExternalImport\ImporterAwareInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * This step takes the structured data and transforms the values it contains according to whatever
 * relevant properties were defined, bringing the data to a near final state, ready for saving
 * to the database.
 *
 * @package Cobweb\ExternalImport\Step
 */
class TransformDataStep extends AbstractStep
{
    /**
     * @var MappingUtility
     */
    protected MappingUtility $mappingUtility;

    /**
     * @var array List of transformation properties
     */
    public static array $transformationProperties = ['trim', 'mapping', 'value', 'rteEnabled', 'userFunction'];

    public function __construct(MappingUtility $mappingUtility)
    {
        $this->mappingUtility = $mappingUtility;
    }

    public function __toString(): string
    {
        return self::class;
    }

    /**
     * Sets the Importer instance (using the parent method) and passes to the mapping utility.
     *
     * @param Importer $importer
     */
    public function setImporter(Importer $importer): void
    {
        parent::setImporter($importer);
        $this->mappingUtility->setImporter($this->importer);
    }

    /**
     * Applies all transformation properties to the existing data set, like mapping to foreign tables,
     * forcing constant values, running user-defined functions, etc.
     *
     * @return void
     */
    public function run(): void
    {
        $records = $this->getData()->getRecords();

        $columnConfiguration = $this->importer->getExternalConfiguration()->getColumnConfiguration();
        // Loop on all columns to find any defined transformations. This might be mappings and/or user functions
        foreach ($columnConfiguration as $columnName => $columnData) {
            if (isset($columnData['transformations'])) {
                foreach ($columnData['transformations'] as $transformationConfiguration) {
                    foreach ($transformationConfiguration as $property => $configuration) {
                        try {
                            switch ($property) {
                                case 'trim':
                                    $records = $this->applyTrim(
                                        $columnName,
                                        $configuration,
                                        $records
                                    );
                                    break;
                                case 'mapping':
                                    $records = $this->applyMapping(
                                        $columnName,
                                        $configuration,
                                        $records
                                    );
                                    break;
                                case 'value':
                                    $records = $this->applyValue(
                                        $columnName,
                                        $configuration,
                                        $records
                                    );
                                    break;
                                case 'rteEnabled':
                                    $records = $this->applyRteEnabledFlag(
                                        $columnName,
                                        $configuration,
                                        $records
                                    );
                                    break;
                                case 'userFunction':
                                    $records = $this->applyUserFunction(
                                        $columnName,
                                        $configuration,
                                        $records
                                    );
                                    break;
                                case 'isEmpty':
                                    $records = $this->applyIsEmpty(
                                        $columnName,
                                        $configuration,
                                        $records
                                    );
                                    break;
                                default:
                                    // Unknown property, log error
                                    $this->importer->debug(
                                        sprintf(
                                            LocalizationUtility::translate(
                                                'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:unknownTransformationProperty',
                                                'external_import'
                                            ),
                                            $property
                                        ),
                                        2,
                                        $configuration
                                    );
                            }
                        } catch (CriticalFailureException $e) {
                            // If a critical failure occurred during a transformation, set the abort flag and return to controller
                            $this->setAbortFlag(true);
                            return;
                        }
                    }
                }
            }
        }

        // Set the records in the Data object (and also as preview, if activated)
        $this->getData()->setRecords($records);
        $this->setPreviewData($records);
    }

    /**
     * Applies the "trim" transformation to the current set of records.
     *
     * @param string $name Name of the column being transformed
     * @param mixed $configuration Transformation configuration
     * @param array $records Data to transform
     * @return array
     */
    public function applyTrim(string $name, $configuration, array $records): array
    {
        if ((bool)$configuration) {
            foreach ($records as $index => $record) {
                // Apply trim only if input is a string, otherwise leave unchanged
                if (is_string($record[$name] ?? null)) {
                    $records[$index][$name] = trim($record[$name]);
                }
            }
        }
        return $records;
    }

    /**
     * Applies the "mapping" transformation to the current set of records.
     *
     * @param string $name Name of the column being transformed
     * @param array $configuration Transformation configuration
     * @param array $records Data to transform
     * @return array
     */
    public function applyMapping(string $name, array $configuration, array $records): array
    {
        return $this->mappingUtility->mapData(
            $records,
            $this->importer->getExternalConfiguration()->getTable(),
            $name,
            $configuration
        );
    }

    /**
     * Applies the "value" transformation to the current set of records.
     *
     * @param string $name Name of the column being transformed
     * @param mixed $configuration Transformation configuration
     * @param array $records Data to transform
     * @return array
     */
    public function applyValue(string $name, $configuration, array $records): array
    {
        foreach ($records as $index => $record) {
            $records[$index][$name] = $configuration;
        }
        return $records;
    }

    /**
     * Adds RTE flag to the current set of records, to mark the given column as containing rich-text data.
     *
     * @param string $name Name of the column being transformed
     * @param mixed $configuration Transformation configuration
     * @param array $records Data to transform
     * @return array
     */
    public function applyRteEnabledFlag(string $name, $configuration, array $records): array
    {
        if ((bool)$configuration) {
            foreach ($records as $index => $record) {
                $records[$index]['_TRANSFORM_' . $name] = 'RTE';
            }
        }
        return $records;
    }

    /**
     * Applies a user function to the current set of records.
     *
     * @param string $name Name of the column being transformed
     * @param array $configuration Transformation configuration
     * @param array $records Data to transform
     * @return array
     * @throws CriticalFailureException
     */
    public function applyUserFunction(string $name, array $configuration, array $records): array
    {
        // Try to get the referenced class
        try {
            $userObject = GeneralUtility::makeInstance($configuration['class']);
            $methodName = $configuration['method'];
            $parameters = $configuration['parameters'] ?? [];
            foreach ($records as $index => $record) {
                try {
                    if ($userObject instanceof ImporterAwareInterface) {
                        $userObject->setImporter($this->importer);
                    }
                    $records[$index][$name] = $userObject->$methodName($record, $name, $parameters);
                } catch (CriticalFailureException $e) {
                    // This exception must not be caught here, but thrown further up
                    throw $e;
                } catch (InvalidRecordException $e) {
                    // This exception means that the record must be removed from the dataset entirely
                    unset($records[$index]);
                    $this->importer->debug(
                        LocalizationUtility::translate(
                            'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:invalidRecordRemoved',
                            'external_import',
                            [
                                $e->getMessage(),
                                $e->getCode()
                            ]
                        ),
                        3,
                        [
                            'user function' => $configuration,
                            'record' => $record
                        ]
                    );
                } catch (\Exception $e) {
                    // If the value could not be transformed, remove it from the imported dataset
                    unset($records[$index][$name]);
                    $this->importer->debug(
                        LocalizationUtility::translate(
                            'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:transformationFailedWithMessage',
                            'external_import',
                            [
                                $e->getMessage(),
                                $e->getCode()
                            ]
                        ),
                        3,
                        [
                            'user function' => $configuration,
                            'record' => $record
                        ]
                    );
                }
            }
        } catch (\Exception $e) {
            $this->importer->debug(
                sprintf(
                    LocalizationUtility::translate(
                        'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:invalid_userfunc',
                        'external_import'
                    ),
                    $configuration['class']
                ),
                2,
                $configuration
            );
        }
        // Compact the array in case some records were unset
        return array_values($records);
    }

    /**
     * Checks if the value for the given column is empty and acts accordingly.
     *
     * @param string $name Name of the column being transformed
     * @param array $configuration Transformation configuration
     * @param array $records Data to transform
     * @return array
     */
    public function applyIsEmpty(string $name, array $configuration, array $records): array
    {
        // Check if expression is defined or not
        $hasExpression = false;
        if (array_key_exists('expression', $configuration) && !empty($configuration['expression'])) {
            $hasExpression = true;
            $expressionLanguage = new ExpressionLanguage();
        }

        // Loop on all records
        foreach ($records as $index => $record) {
            // Use expression if defined, empty() otherwise to assess emptiness of value
            if ($hasExpression) {
                try {
                    $isEmpty = (bool)$expressionLanguage->evaluate(
                        $configuration['expression'],
                        $record
                    );
                // If an exception is thrown, consider that this is equivalent to the expression being evaluated to true,
                // because the main source of exceptions is when a value used in the expression is not present (hence "empty").
                // An exception could also happen because the expression's syntax is invalid. Unfortunately the Expression Language
                // does not distinguish between the two scenarios. The event is logged for further inspection.
                } catch (\Exception $e) {
                    $isEmpty = true;
                    $this->importer->debug(
                        sprintf(
                            LocalizationUtility::translate(
                                'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:expressionError',
                                'external_import'
                            ),
                            $configuration['expression'],
                            $e->getMessage(),
                            $e->getCode()
                        ),
                        1
                    );
                }
            } else {
                $isEmpty = empty($record[$name]);
            }
            // If the value could be considered empty, act according to configuration
            if ($isEmpty) {
                if (array_key_exists('invalidate', $configuration) && (bool)$configuration['invalidate']) {
                    unset($records[$index]);
                    // Log info about dropped record
                    $this->importer->debug(
                        LocalizationUtility::translate(
                            'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:emptyRecordRemoved',
                            'external_import',
                            [
                                $index,
                                $name
                            ]
                        ),
                        3,
                        [
                            'record' => $record
                        ]
                    );
                } elseif (array_key_exists('default', $configuration)) {
                    $records[$index][$name] = $configuration['default'];
                }
            }
        }

        // Compact the array in case some records were unset
        return array_values($records);
    }

    /**
     * Define the data as being downloadable
     *
     * @return bool
     */
    public function hasDownloadableData(): bool
    {
        return true;
    }
}