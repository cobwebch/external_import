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

use TYPO3\CMS\Core\Utility\GeneralUtility;

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
     * @var \Cobweb\ExternalImport\Utility\MappingUtility
     */
    protected $mappingUtility;

    public function injectMappingUtility(\Cobweb\ExternalImport\Utility\MappingUtility $mappingUtility)
    {
        $this->mappingUtility = $mappingUtility;
    }

    /**
     * Applies all transformation properties to the existing data set, like mapping to foreign tables,
     * forcing constant values, running user-defined functions, etc.
     *
     * @return void
     */
    public function run()
    {
        // First of all, set the Importer (it seems like this cannot be done in the inject method; it happens too early)
        $this->mappingUtility->setImporter($this->importer);

        $records = $this->getData()->getRecords();
        $numRecords = count($records);

        $columnConfiguration = $this->getConfiguration()->getColumnConfiguration();
        // Loop on all tables to find any defined transformations. This might be mappings and/or user functions
        foreach ($columnConfiguration as $columnName => $columnData) {
            // If the column's content must be trimmed, apply trim to all records
            if (!empty($columnData['trim'])) {
                for ($i = 0; $i < $numRecords; $i++) {
                    $records[$i][$columnName] = trim($records[$i][$columnName]);
                }
            }

            // Get existing mappings and apply them to records
            if (isset($columnData['mapping'])) {
                $records = $this->mappingUtility->mapData(
                        $records,
                        $this->importer->getTableName(),
                        $columnName,
                        $columnData['mapping']
                );

                // Otherwise apply constant value, if defined
            } elseif (isset($columnData['value'])) {
                for ($i = 0; $i < $numRecords; $i++) {
                    $records[$i][$columnName] = $columnData['value'];
                }
            }

            // Add field for RTE transformation to each record, if column has RTE enabled
            // TODO: check if this is still relevant/correct with TYPO3 v8
            if (!empty($columnData['rteEnabled'])) {
                for ($i = 0; $i < $numRecords; $i++) {
                    $records[$i]['_TRANSFORM_' . $columnName] = 'RTE';
                }
            }

            // Apply defined user function
            if (isset($columnData['userFunc'])) {
                // Try to get the referenced class
                try {
                    $userObject = GeneralUtility::makeInstance($columnData['userFunc']['class']);
                    $methodName = $columnData['userFunc']['method'];
                    $parameters = isset($columnData['userFunc']['params']) ? $columnData['userFunc']['params'] : array();
                    for ($i = 0; $i < $numRecords; $i++) {
                        $records[$i][$columnName] = $userObject->$methodName($records[$i], $columnName, $parameters);
                    }
                }
                catch (\Exception $e) {
                    $this->importer->debug(
                            sprintf(
                                    $GLOBALS['LANG']->getLL('invalid_userfunc'),
                                    $columnData['userFunc']['class']
                            ),
                            2,
                            $columnData['userFunc']
                    );
                }
            }
        }

        // Apply any existing pre-processing hook to the transformed data
        $records = $this->preprocessData($records);

        $this->getData()->setRecords($records);
    }

    /**
     * Applies any existing pre-processing to the data before it moves on to the next step.
     *
     * Note that this method does not do anything by itself. It just calls on a pre-processing hook.
     *
     * @param array $records Records containing the data
     * @return array
     */
    protected function preprocessData($records)
    {
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['external_import']['preprocessRecordset'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['external_import']['preprocessRecordset'] as $className) {
                try {
                    $preProcessor = GeneralUtility::makeInstance($className);
                    $records = $preProcessor->preprocessRecordset($records, $this->importer);
                    // Compact the array again, in case some values were unset in the pre-processor
                    $records = array_values($records);
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