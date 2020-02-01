<?php
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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Tools for mapping imported data to existing database relations.
 *
 * @package Cobweb\ExternalImport\Utility
 */
class MappingUtility
{
    /**
     * @var \Cobweb\ExternalImport\Importer Back-reference to the current Importer instance
     */
    protected $importer;

    /**
     * Returns the object as a string.
     *
     * NOTE: this seems pretty useless but somehow is needed when a functional test fails. Don't ask me why.
     *
     * @return string
     */
    public function __toString()
    {
        return __CLASS__;
    }

    /**
     * Takes a record set and maps a given field to some existing database relation.
     *
     * @param array $records Records to handle
     * @param string $table Name of the table the records belong to
     * @param string $columnName Name of the column whose values must be mapped
     * @param array $mappingInformation Mapping configuration
     * @return array
     */
    public function mapData($records, $table, $columnName, $mappingInformation)
    {
        $mappings = $this->getMapping($mappingInformation);
        $numRecords = count($records);
        // If no particular matching method is defined, match exactly on the keys of the mapping table
        if (empty($mappingInformation['matchMethod'])) {
            // Determine if mapping is self-referential
            // Self-referential mappings cause a problem, because they may refer to a record that is not yet
            // in the database, but is part of the import. In this case we need to create a temporary ID for that
            // record and store it in order to reuse it when assembling the DataHandler data map (in the StoreDataStep).
            $isSelfReferential = false;
            if ($mappingInformation['table'] === $table) {
                $isSelfReferential = true;
            }

            for ($i = 0; $i < $numRecords; $i++) {
                $externalValue = $records[$i][$columnName];
                // If the external value is empty, don't even try to map it, but use default value, if any. Otherwise, proceed.
                if (empty($externalValue)) {
                    if (array_key_exists('default', $mappingInformation)) {
                        $records[$i][$columnName] = $mappingInformation['default'];
                    } else {
                        unset($records[$i][$columnName]);
                    }
                } else {
                    // The external field may contain multiple values
                    if (!empty($mappingInformation['multipleValuesSeparator'])) {
                        $singleExternalValues = GeneralUtility::trimExplode(
                                $mappingInformation['multipleValuesSeparator'],
                                $externalValue,
                                true
                        );

                        // The external field is expected to contain a single value
                    } else {
                        $singleExternalValues = [$externalValue];
                    }
                    // Loop on all values and try to map them
                    $mappedExternalValues = [];
                    foreach ($singleExternalValues as $singleValue) {

                        // Value is matched in the available mapping
                        if (isset($mappings[$singleValue])) {
                            $mappedExternalValues[] = $mappings[$singleValue];

                        // Value is not matched, maybe it matches a temporary key, if self-referential
                        } else {
                            // If the relation is self-referential, use a temporary key
                            if ($isSelfReferential) {
                                // Check if a temporary key was already created for that external key
                                if ($this->importer->hasTemporaryKey($singleValue)) {
                                    $temporaryKey = $this->importer->getTemporaryKeyForValue($singleValue);

                                // If not, create a new temporary key
                                } else {
                                    $temporaryKey = $this->importer->generateTemporaryKey();
                                    $this->importer->addTemporaryKey($singleValue, $temporaryKey);
                                }
                                // Use temporary key
                                $mappedExternalValues[] = $temporaryKey;
                            }
                        }
                    }
                    // Map the values found
                    if (count($mappedExternalValues) > 0) {
                        $records[$i][$columnName] = implode(',', $mappedExternalValues);
                    } else {
                        // If nothing was found, use the default value, if defined. Otherwise unset the record.
                        if (array_key_exists('default', $mappingInformation)) {
                            $records[$i][$columnName] = $mappingInformation['default'];
                        } else {
                            unset($records[$i][$columnName]);
                        }
                    }
                }
            }

        // If a particular mapping method is defined, use it on the keys of the mapping table
        // NOTE: self-referential relations are not checked in this case, as it does not seem to make sense
        // to have weak-matching external keys
        } else {
            if ($mappingInformation['matchMethod'] === 'strpos' || $mappingInformation['matchMethod'] === 'stripos') {
                for ($i = 0; $i < $numRecords; $i++) {
                    $externalValue = $records[$i][$columnName];
                    // The external field may contain multiple values
                    if (!empty($mappingInformation['multipleValuesSeparator'])) {
                        $singleExternalValues = GeneralUtility::trimExplode(
                                $mappingInformation['multipleValuesSeparator'],
                                $externalValue,
                                true
                        );

                    // The external field is expected to contain a single value
                    } else {
                        $singleExternalValues = [$externalValue];
                    }
                    // Loop on all values and try to map them
                    $mappedExternalValues = [];
                    foreach ($singleExternalValues as $singleValue) {
                        // Try matching the value. If matching fails, unset it.
                        try {
                            $mappedExternalValues[] = $this->matchSingleField(
                                    $singleValue,
                                    $mappingInformation,
                                    $mappings);
                        } catch (\Exception $e) {
                            // Ignore unmapped values
                        }
                    }
                    // Map the values found
                    if (count($mappedExternalValues) > 0) {
                        $records[$i][$columnName] = implode(',', $mappedExternalValues);
                    } else {
                        // If nothing was found, use the default value, if defined. Otherwise unset the record.
                        if (array_key_exists('default', $mappingInformation)) {
                            $records[$i][$columnName] = $mappingInformation['default'];
                        } else {
                            unset($records[$i][$columnName]);
                        }
                    }
                }
            }
        }
        return $records;
    }

    /**
     * Retrieves a single mapping.
     *
     * @param array $mappingData Data defining the mapping of fields
     * @return array Hash table for mapping
     */
    public function getMapping($mappingData)
    {
        $localMapping = array();

        // Check if there's a fixed value map
        if (isset($mappingData['valueMap'])) {
            // Use value map directly
            $localMapping = $mappingData['valueMap'];

            // No value map, get values from the database
        } else {
            // Assemble query and get data
            $valueField = 'uid';
            if (isset($mappingData['valueField'])) {
                $valueField = $mappingData['valueField'];
            }
            $referenceField = $mappingData['referenceField'];
            // Define where clause
            $whereClause = '1 = 1';
            if (!empty($mappingData['whereClause'])) {
                // If the where clause contains the ###PID_IN_USE### marker, replace it with current storage pid
                if (strpos($mappingData['whereClause'], '###PID_IN_USE###') !== false) {
                    $whereClause = str_replace(
                            '###PID_IN_USE###',
                            $this->importer->getExternalConfiguration()->getStoragePid(),
                            $mappingData['whereClause']
                    );
                } else {
                    $whereClause = $mappingData['whereClause'];
                }
            }
            // Query the table
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($mappingData['table']);
            $queryBuilder->getRestrictions()
                    ->removeAll()
                    ->add(
                            GeneralUtility::makeInstance(
                                    DeletedRestriction::class
                            )
                    );
            $res = $queryBuilder->select($referenceField, $valueField)
                    ->from($mappingData['table'])
                    ->where($whereClause)
                    ->execute();

            // Fill hash table
            if ($res) {
                while ($row = $res->fetch(\PDO::FETCH_ASSOC)) {
                    // Don't consider records with empty references, as they can't be matched
                    // to external data anyway (but a real zero is acceptable)
                    if (!empty($row[$referenceField]) || $row[$referenceField] === '0' || $row[$referenceField] === 0) {
                        $localMapping[$row[$referenceField]] = $row[$valueField];
                    }
                }
            }
        }
        return $localMapping;
    }

    /**
     * Tries to match a single value to a table of mappings.
     *
     * @param mixed $externalValue The value to match
     * @param array $mappingInformation Mapping configuration
     * @param array $mappingTable Value map
     * @throws \UnexpectedValueException
     * @return mixed The matched value
     */
    public function matchSingleField($externalValue, $mappingInformation, $mappingTable)
    {
        $returnValue = '';
        $function = $mappingInformation['matchMethod'];
        if (!empty($externalValue)) {
            $hasMatch = false;
            foreach ($mappingTable as $key => $value) {
                $hasMatch = (call_user_func($function, $key, $externalValue) !== false);
                if (!empty($mappingInformation['matchSymmetric'])) {
                    $hasMatch |= (call_user_func($function, $externalValue, $key) !== false);
                }
                if ($hasMatch) {
                    $returnValue = $value;
                    break;
                }
            }
            // If unmatched, throw exception
            if (!$hasMatch) {
                throw new \UnexpectedValueException('Unmatched value ' . $externalValue, 1294739120);
            }
        }
        return $returnValue;
    }

    /**
     * Sets a reference to the current Importer instance.
     *
     * @param \Cobweb\ExternalImport\Importer $importer
     */
    public function setImporter($importer)
    {
        $this->importer = $importer;
    }
}