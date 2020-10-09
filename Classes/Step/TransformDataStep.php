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

use Cobweb\ExternalImport\Exception\CriticalFailureException;
use Cobweb\ExternalImport\Utility\MappingUtility;
use Cobweb\ExternalImport\ImporterAwareInterface;
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
    protected $mappingUtility;

    /**
     * @var array List of transformation properties
     */
    static public $transformationProperties = ['trim', 'mapping', 'value', 'rteEnabled', 'userFunc'];

    public function injectMappingUtility(MappingUtility $mappingUtility): void
    {
        $this->mappingUtility = $mappingUtility;
    }

    /**
     * Applies all transformation properties to the existing data set, like mapping to foreign tables,
     * forcing constant values, running user-defined functions, etc.
     *
     * @return void
     */
    public function run(): void
    {
        // First of all, set the Importer (it seems like this cannot be done in the inject method; it happens too early)
        $this->mappingUtility->setImporter($this->importer);

        $records = $this->getData()->getRecords();

        $columnConfiguration = $this->importer->getExternalConfiguration()->getColumnConfiguration();
        // Loop on all tables to find any defined transformations. This might be mappings and/or user functions
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
                                case 'userFunc':
                                    $records = $this->applyUserFunction(
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

        // Apply any existing pre-processing hook to the transformed data
        try {
            $records = $this->preprocessData($records);
        } catch (CriticalFailureException $e) {
            // If a critical failure occurred during hook execution, set the abort flag and return to controller
            $this->setAbortFlag(true);
            return;
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
    public function applyTrim($name, $configuration, array $records): array
    {
        if ((bool)$configuration) {
            foreach ($records as $index => $record) {
                $records[$index][$name] = trim($record[$name]);
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
    public function applyMapping($name, $configuration, array $records): array
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
    public function applyValue($name, $configuration, array $records): array
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
    public function applyRteEnabledFlag($name, $configuration, array $records): array
    {
        // TODO: check if this is still relevant/correct with TYPO3 v8
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
    public function applyUserFunction($name, $configuration, array $records): array
    {
            // Try to get the referenced class
            try {
                $userObject = GeneralUtility::makeInstance($configuration['class']);
                $methodName = $configuration['method'];
                $parameters = $configuration['params'] ?? [];
                foreach ($records as $index => $record) {
                    try {
                        if ($userObject instanceof ImporterAwareInterface) {
                            $userObject->setImporter($this->importer);
                        }
                        $records[$index][$name] = $userObject->$methodName($record, $name, $parameters);
                    } catch (CriticalFailureException $e) {
                        // This exception must not be caught here, but thrown further up
                        throw $e;
                    } catch (\Exception $e) {
                        $this->importer->debug(
                                LocalizationUtility::translate(
                                        'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:transformationFailed',
                                        'external_import'
                                ),
                                2,
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
        return $records;
    }

    /**
     * Applies any existing pre-processing to the data before it moves on to the next step.
     *
     * Note that this method does not do anything by itself. It just calls on a pre-processing hook.
     *
     * @param array $records Records containing the data
     * @return array
     * @throws CriticalFailureException
     */
    protected function preprocessData($records): array
    {
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['external_import']['preprocessRecordset'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['external_import']['preprocessRecordset'] as $className) {
                try {
                    $preProcessor = GeneralUtility::makeInstance($className);
                    $records = $preProcessor->preprocessRecordset($records, $this->importer);
                    // Compact the array again, in case some values were unset in the pre-processor
                    $records = array_values($records);
                } catch (CriticalFailureException $e) {
                    // This exception must not be caught here, but thrown further up
                    throw $e;
                } catch (\Exception $e) {
                    $this->importer->debug(
                            sprintf(
                                    'Could not instantiate class %s for hook %s',
                                    $className,
                                    'preprocessRecordset'
                            ),
                            1
                    );
                }
            }
        }
        return $records;
    }
}