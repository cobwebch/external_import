<?php
declare(strict_types=1);
namespace Cobweb\ExternalImport\Utility;

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


use Cobweb\ExternalImport\Importer;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Code extracted from the \Cobweb\ExternalImport\Step\StoreDataStep class, related to MM relations (old-style).
 * 
 * @package Cobweb\ExternalImport\Utility
 */
class ManyToManyUtility
{
    /**
     * @var Importer Back-reference to the current Importer instance
     */
    protected $importer;

    /**
     * @var array List of all relations for each MM-relations field of each record being imported
     */
    protected $mappings = [];

    /**
     * @var array List of all relations for each MM-relations field of each record being imported, with additional fields
     */
    protected $fullMappings = [];

    /**
     * Checks which columns are MM-relations and gets mappings to foreign tables for each.
     *
     * NOTE: as it is now, it is assumed that the imported data is denormalised
     *
     * There's more to do than that:
     *
     * 1.	a sorting field may have been defined, but the TCE assumes the MM-relations are in the right order
   	 * 	    and inserts its own number for the table's sorting field. So MM-relations must be sorted before acting on the TCE data.
     * 2.a	it is possible to store additional fields in the MM-relations. This is not TYPO3-standard, so the TCE will
   	 * 	    not be able to handle it. We thus need to store all that data now and rework the MM-relations when the TCE is done.
     * 2.b	if a pair of records is related to each other several times (because the additional fields vary), this will be filtered out
   	 * 	    by the TCE. So we must preserve also these additional relations.
     *
     * @param array $ctrlConfiguration "ctrl" part of the External Import configuration
     * @param array $columnConfiguration Column part of the External Import configuration
     * @param array $records The records being handled
     * @return void
     */
    public function handleMmRelations($ctrlConfiguration, $columnConfiguration, $records): void
    {
        $mappingUtility = GeneralUtility::makeInstance(MappingUtility::class);
        $mappingUtility->setImporter($this->importer);
        $this->mappings = [];
        $this->fullMappings = [];
        foreach ($columnConfiguration as $columnName => $columnData) {
            // Process MM-relations, if any
            if (array_key_exists('MM', $columnData)) {
                $mmData = $columnData['MM'];
                $sortingField = $mmData['sorting'] ?? false;
                $additionalFields = $mmData['additionalFields'] ?? [];
                $hasAdditionalFields = count($additionalFields) > 0;

                $this->mappings[$columnName] = [];
                if ($additionalFields || $mmData['multiple']) {
                    $this->fullMappings[$columnName] = [];
                }

                // Get foreign mapping for column
                $mappingInformation = $mmData['mapping'];
                $foreignMappings = $mappingUtility->getMapping($mappingInformation);

                // Go through each record and assemble pairs of primary and foreign keys
                foreach ($records as $theRecord) {
                    $externalUid = $theRecord[$ctrlConfiguration['referenceUid']];
                    // Make sure not to keep the value from the previous iteration
                    unset($foreignValue);

                    // Get foreign value
                    // First try the "soft" matching method to mapping table
                    if (!empty($mmData['mapping']['matchMethod'])) {
                        if ($mmData['mapping']['matchMethod'] === 'strpos' || $mmData['mapping']['matchMethod'] === 'stripos') {
                            // Try matching the value. If matching fails, unset it.
                            try {
                                $foreignValue = $mappingUtility->matchSingleField(
                                        $theRecord[$columnName],
                                        $mmData['mapping'],
                                        $foreignMappings
                                );
                            } catch (\Exception $e) {
                                // Nothing to do, foreign value must stay "unset"
                            }
                        }

                    // Then the "strict" matching method to mapping table
                    } elseif (isset($foreignMappings[$theRecord[$columnName]])) {
                        $foreignValue = $foreignMappings[$theRecord[$columnName]];
                    }

                    // If a value was found, use it
                    if (isset($foreignValue)) {
                        if (!isset($this->mappings[$columnName][$externalUid])) {
                            $this->mappings[$columnName][$externalUid] = [];
                            // Initialise only if necessary
                            if ($hasAdditionalFields || $mmData['multiple']) {
                                $this->fullMappings[$columnName][$externalUid] = [];
                            }
                        }

                        // If additional fields are defined, store those values in an intermediate array
                        $fields = [];
                        if ($hasAdditionalFields) {
                            foreach ($additionalFields as $localFieldName => $externalFieldName) {
                                $fields[$localFieldName] = $theRecord[$externalFieldName];
                            }
                        }

                        // If a sorting field is defined, use that value for indexing, otherwise just add the element at the end of the array
                        if ($sortingField) {
                            $sortingValue = $theRecord[$sortingField];
                            $this->mappings[$columnName][$externalUid][$sortingValue] = $foreignValue;
                            if ($hasAdditionalFields || $mmData['multiple']) {
                                $this->fullMappings[$columnName][$externalUid][$sortingValue] = [
                                        'value' => $foreignValue,
                                        'additionalFields' => $fields
                                ];
                            }
                        } else {
                            $this->mappings[$columnName][$externalUid][] = $foreignValue;
                            if ($hasAdditionalFields || $mmData['multiple']) {
                                $this->fullMappings[$columnName][$externalUid][] = [
                                        'value' => $foreignValue,
                                        'additionalFields' => $fields
                                ];
                            }
                        }
                    }
                }

                // If there was some special sorting to do, do it now
                if ($sortingField) {
                    foreach ($this->mappings as $innerColumnName => $columnMappings) {
                        foreach ($columnMappings as $uid => $values) {
                            ksort($values);
                            $this->mappings[$innerColumnName][$uid] = $values;

                            // Do the same for extended MM-relations, if necessary
                            if ($additionalFields || $mmData['multiple']) {
                                $fullValues = $this->fullMappings[$innerColumnName][$uid];
                                ksort($fullValues);
                                $this->fullMappings[$innerColumnName][$uid] = $fullValues;
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Stores all MM relations.
     *
     * Existing relations are deleted first.
     *
     * @return void
     * @throws \Cobweb\ExternalImport\Exception\MissingConfigurationException
     */
    public function postProcessMmRelations(): void
    {
        $this->importer->debug(
                'Handling full mappings',
                0,
                $this->fullMappings
        );

        // Refresh list of existing primary keys now that new records have been inserted
        $uidRepository = $this->importer->getUidRepository();
        $uidRepository->resetExistingUids();
        $existingUids = $uidRepository->getExistingUids();

        // Loop on all columns that require a remapping
        $tableTca = $GLOBALS['TCA'][$this->importer->getExternalConfiguration()->getTable()];
        foreach ($this->fullMappings as $columnName => $mappingData) {
            $columnTcaConfiguration = $tableTca['columns'][$columnName]['config'];
            $mmTable = $columnTcaConfiguration['MM'];
            // Prepare connection and query builder for the table
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($mmTable);
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($mmTable);
            // Assemble extra condition if MM_insert_fields or MM_match_fields are defined
            $additionalWheres = [];
            $additionalWhereTypes = [];
            $mmAdditionalFields = [];
            // Merge all insert and match fields together
            if (isset($columnTcaConfiguration['MM_insert_fields']) && is_array($columnTcaConfiguration['MM_insert_fields'])) {
                $mmAdditionalFields = $columnTcaConfiguration['MM_insert_fields'];
            }
            if (isset($columnTcaConfiguration['MM_match_fields']) && is_array($columnTcaConfiguration['MM_match_fields'])) {
                $mmAdditionalFields = array_merge(
                        $mmAdditionalFields,
                        $columnTcaConfiguration['MM_match_fields']
                );
            }
            // Assemble a condition with all these fields
            foreach ($mmAdditionalFields as $column => $value) {
                $additionalWheres[$column] = $value;
                $additionalWhereTypes[] = Connection::PARAM_STR;
            }
            // Check if column is an opposite field
            if (!empty($columnTcaConfiguration['MM_opposite_field'])) {
                $isOppositeField = true;
            } else {
                $isOppositeField = false;
            }
            foreach ($mappingData as $externalUid => $sortedData) {
                $uid = $existingUids[$externalUid];

                // Delete existing MM-relations for current uid, taking relation side into account
                if ($isOppositeField) {
                    $referenceField = 'uid_foreign';
                } else {
                    $referenceField = 'uid_local';
                }
                $conditions = $additionalWheres;
                $conditions[$referenceField] = (int)$uid;
                // TODO: check this. It is unused!
                $conditionTypes = $additionalWhereTypes;
                $conditionTypes[] = Connection::PARAM_INT;
                $connection->delete(
                        $mmTable,
                        $conditions
                );

                // Recreate all MM-relations with additional fields, if any
                $counter = 0;
                foreach ($sortedData as $mmData) {
                    $counter++;
                    // Define uid_local and uid_foreign depending on relation side
                    if ($isOppositeField) {
                        $uidLocal = $mmData['value'];
                        $uidForeign = $uid;
                    } else {
                        $uidLocal = $uid;
                        $uidForeign = $mmData['value'];
                    }

                    $fields = $mmData['additionalFields'];
                    $fields['uid_local'] = $uidLocal;
                    $fields['uid_foreign'] = $uidForeign;
                    $fields['sorting'] = $counter;
                    // Add insert and match fields to values for insert
                    foreach ($mmAdditionalFields as $column => $value) {
                        $fields[$column] = $value;
                    }
                    $queryBuilder->insert($mmTable)
                            ->values($fields)
                            ->execute();
                }
            }
        }
    }

    /**
     * Returns the mappings array.
     *
     * @return array
     */
    public function getMappings(): array
    {
        return $this->mappings;
    }

    /**
     * Returns the full mappings array.
     *
     * @return array
     */
    public function getFullMappings(): array
    {
        return $this->fullMappings;
    }

    /**
     * Sets a reference to the current Importer instance.
     *
     * @param Importer $importer
     */
    public function setImporter($importer): void
    {
        $this->importer = $importer;
    }
}