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

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class StoreDataStep extends AbstractStep
{
    /**
     * @var \Cobweb\ExternalImport\Utility\MappingUtility
     */
    protected $mappingUtility;

    public function injectMappingUtility(\Cobweb\ExternalImport\Utility\MappingUtility $mappingUtility)
    {
        $this->mappingUtility = $mappingUtility;
        $this->mappingUtility->setImporter($this->importer);
    }

    /**
     * Stores the data to the database using DataHandler.
     *
     * @return void
     */
    public function run()
    {
        $records = $this->getData()->getRecords();
        $this->importer->debug(
                'Data received for storage',
                0,
                $records
        );

        // Initialize some variables
        $fieldsExcludedFromInserts = array();
        $fieldsExcludedFromUpdates = array();

        // Get the list of existing uids for the table
        $this->importer->retrieveExistingUids();
        $existingUids = $this->importer->getExistingUids();

        // Check which columns are MM-relations and get mappings to foreign tables for each
        // NOTE: as it is now, it is assumed that the imported data is denormalised
        //
        // NOTE2:	as long as we're looping on all columns, we assemble the list
        //			of fields that are excluded from insert or update operations
        //
        // There's more to do than that:
        //
        // 1.	a sorting field may have been defined, but TCEmain assumes the MM-relations are in the right order
        //		and inserts its own number for the table's sorting field. So MM-relations must be sorted before executing TCEmain.
        // 2.a	it is possible to store additional fields in the MM-relations. This is not TYPO3-standard, so TCEmain will
        //		not be able to handle it. We thus need to store all that data now and rework the MM-relations when TCEmain is done.
        // 2.b	if a pair of records is related to each other several times (because the additional fields vary), this will be filtered out
        //		by TCEmain. So we must preserve also these additional relations.
        $mappings = array();
        $fullMappings = array();
        $ctrlConfiguration = $this->getConfiguration()->getCtrlConfiguration();
        $columnConfiguration = $this->getConfiguration()->getColumnConfiguration();
        $table = $this->importer->getTableName();
        foreach ($columnConfiguration as $columnName => $columnData) {
            // Check if some fields are excluded from some operations
            // and add them to the relevant list
            if (array_key_exists('disabledOperations', $columnData)) {
                if (GeneralUtility::inList($columnData['disabledOperations'], 'insert')) {
                    $fieldsExcludedFromInserts[] = $columnName;
                }
                if (GeneralUtility::inList($columnData['disabledOperations'], 'update')) {
                    $fieldsExcludedFromUpdates[] = $columnName;
                }
            }
            // Process MM-relations, if any
            if (array_key_exists('MM', $columnData)) {
                $mmData = $columnData['MM'];
                $sortingField = (isset($mmData['sorting'])) ? $mmData['sorting'] : false;
                $additionalFields = (isset($mmData['additionalFields'])) ? $mmData['additionalFields'] : array();
                $hasAdditionalFields = count($additionalFields) > 0;

                $mappings[$columnName] = array();
                if ($additionalFields || $mmData['multiple']) {
                    $fullMappings[$columnName] = array();
                }

                // Get foreign mapping for column
                $mappingInformation = $mmData['mapping'];
                $foreignMappings = $this->mappingUtility->getMapping($mappingInformation);

                // Go through each record and assemble pairs of primary and foreign keys
                foreach ($records as $theRecord) {
                    $externalUid = $theRecord[$ctrlConfiguration['referenceUid']];
                    // Make sure not to keep the value from the previous iteration
                    unset($foreignValue);

                    // Get foreign value
                    // First try the "soft" matching method to mapping table
                    if (!empty($mmData['mapping']['match_method'])) {
                        if ($mmData['mapping']['match_method'] === 'strpos' || $mmData['mapping']['match_method'] === 'stripos') {
                            // Try matching the value. If matching fails, unset it.
                            try {
                                $foreignValue = $this->mappingUtility->matchSingleField(
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
                        if (!isset($mappings[$columnName][$externalUid])) {
                            $mappings[$columnName][$externalUid] = array();
                            // Initialise only if necessary
                            if ($hasAdditionalFields || $mmData['multiple']) {
                                $fullMappings[$columnName][$externalUid] = array();
                            }
                        }

                        // If additional fields are defined, store those values in an intermediate array
                        $fields = array();
                        if ($hasAdditionalFields) {
                            foreach ($additionalFields as $localFieldName => $externalFieldName) {
                                $fields[$localFieldName] = $theRecord[$externalFieldName];
                            }
                        }

                        // If a sorting field is defined, use that value for indexing, otherwise just add the element at the end of the array
                        if ($sortingField) {
                            $sortingValue = $theRecord[$sortingField];
                            $mappings[$columnName][$externalUid][$sortingValue] = $foreignValue;
                            if ($hasAdditionalFields || $mmData['multiple']) {
                                $fullMappings[$columnName][$externalUid][$sortingValue] = array(
                                        'value' => $foreignValue,
                                        'additionalFields' => $fields
                                );
                            }
                        } else {
                            $mappings[$columnName][$externalUid][] = $foreignValue;
                            if ($hasAdditionalFields || $mmData['multiple']) {
                                $fullMappings[$columnName][$externalUid][] = array(
                                        'value' => $foreignValue,
                                        'additionalFields' => $fields
                                );
                            }
                        }
                    }
                }

                // If there was some special sorting to do, do it now
                if ($sortingField) {
                    foreach ($mappings as $innerColumnName => $columnMappings) {
                        foreach ($columnMappings as $uid => $values) {
                            ksort($values);
                            $mappings[$innerColumnName][$uid] = $values;

                            // Do the same for extended MM-relations, if necessary
                            if ($additionalFields || $mmData['multiple']) {
                                $fullValues = $fullMappings[$innerColumnName][$uid];
                                ksort($fullValues);
                                $fullMappings[$innerColumnName][$uid] = $fullValues;
                            }
                        }
                    }
                }
            }
        }
        $hasMMRelations = count($mappings);

        // Insert or update records depending on existing uids
        $updates = 0;
        $updatedUids = array();
        $handledUids = array();
        $tceData = array($table => array());
        $savedAdditionalFields = array();
        $storagePid = $this->getConfiguration()->getStoragePid();
        foreach ($records as $theRecord) {
            $localAdditionalFields = array();
            $externalUid = $theRecord[$ctrlConfiguration['referenceUid']];
            // Skip handling of already handled records (this can happen with denormalized structures)
            // NOTE: using isset() on index instead of in_array() offers far better performance
            if (isset($handledUids[$externalUid])) {
                continue;
            }
            $handledUids[$externalUid] = $externalUid;

            // Prepare MM-fields, if any
            if ($hasMMRelations) {
                foreach ($mappings as $columnName => $columnMappings) {
                    if (isset($columnMappings[$externalUid])) {
                        $theRecord[$columnName] = implode(',', $columnMappings[$externalUid]);

                        // Make sure not to keep the original value if no mapping was found
                    } else {
                        unset($theRecord[$columnName]);
                    }
                }
            }

            // Remove additional fields data, if any. They must not be saved to database
            // They are saved locally however, for later use
            if ($this->getConfiguration()->getCountAdditionalFields() > 0) {
                $configuredAdditionalFields = $this->getConfiguration()->getAdditionalFields();
                foreach ($configuredAdditionalFields as $fieldName) {
                    $localAdditionalFields[$fieldName] = $theRecord[$fieldName];
                    unset($theRecord[$fieldName]);
                }
            }

            $theID = '';
            // Reference uid is found, perform an update (if not disabled)
            if (isset($existingUids[$externalUid])) {
                if (!GeneralUtility::inList($ctrlConfiguration['disabledOperations'], 'update')) {
                    // First call a pre-processing hook
                    if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['external_import']['updatePreProcess'])) {
                        foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['external_import']['updatePreProcess'] as $className) {
                            try {
                                $preProcessor = GeneralUtility::makeInstance($className);
                                $theRecord = $preProcessor->processBeforeUpdate($theRecord, $this->importer);
                            } catch (\Exception $e) {
                                $this->importer->debug(
                                        sprintf(
                                                'Could not instantiate class %s for hook %s',
                                                $className,
                                                'updatePreProcess'
                                        ),
                                        1
                                );
                            }
                        }
                    }

                    // Remove the fields which must be excluded from updates
                    if (count($fieldsExcludedFromUpdates) > 0) {
                        foreach ($fieldsExcludedFromUpdates as $excludedField) {
                            unset($theRecord[$excludedField]);
                        }
                    }

                    $theID = $existingUids[$externalUid];
                    $tceData[$table][$theID] = $theRecord;
                    $updatedUids[] = $theID;
                    $updates++;
                }

                // Reference uid not found, perform an insert (if not disabled)
            } elseif (!GeneralUtility::inList($ctrlConfiguration['disabledOperations'], 'insert')) {

                // First call a pre-processing hook
                if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['external_import']['insertPreProcess'])) {
                    foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['external_import']['insertPreProcess'] as $className) {
                        try {
                            $preProcessor = GeneralUtility::makeInstance($className);
                            $theRecord = $preProcessor->processBeforeInsert($theRecord, $this->importer);
                        } catch (\Exception $e) {
                            $this->importer->debug(
                                    sprintf(
                                            'Could not instantiate class %s for hook %s',
                                            $className,
                                            'insertPreProcess'
                                    ),
                                    1
                            );
                        }
                    }
                }

                // Remove the fields which must be excluded from inserts
                if (count($fieldsExcludedFromInserts) > 0) {
                    foreach ($fieldsExcludedFromInserts as $excludedField) {
                        unset($theRecord[$excludedField]);
                    }
                }

                // Make sure the record has a pid, but avoid overwriting an existing one
                // (for example, when external data is imported into the pages table)
                if (!isset($theRecord['pid'])) {
                    $theRecord['pid'] = $storagePid;
                }
                // If a temporary key was already defined, use it, otherwise create a new one.
                // Temporary keys may exist if self-referential mapping was handled beforehand (see mapData())
                if ($this->importer->hasTemporaryKey($externalUid)) {
                    $theID = $this->importer->getTemporaryKeyForValue($externalUid);
                } else {
                    $theID = uniqid('NEW', true);
                }
                $tceData[$table][$theID] = $theRecord;
            }
            // Store local additional fields into general additional fields array
            // keyed to proper id's (if the record was processed)
            if (!empty($theID)) {
                $savedAdditionalFields[$theID] = $localAdditionalFields;
            }
        }
        $this->importer->debug(
                'TCEmain data',
                0,
                $tceData
        );
        // Create an instance of DataHandler and process the data
        /** @var $tce DataHandler */
        $tce = GeneralUtility::makeInstance(DataHandler::class);
        // Check if TCEmain logging should be turned on or off
        $extensionConfiguration = $this->importer->getExtensionConfiguration();
        $disableLogging = (empty($extensionConfiguration['disableLog'])) ? false : true;
        if (array_key_exists('disableLog', $ctrlConfiguration)) {
            $disableLogging = (empty($ctrlConfiguration['disableLog'])) ? false : true;
        }
        $tce->enableLogging = !$disableLogging;
        // If the table has a sorting field, reverse the data array,
        // otherwise the first record will come last (because TCEmain
        // itself inverts the incoming order)
        if (!empty($GLOBALS['TCA'][$table]['ctrl']['sortby'])) {
            $tce->reverseOrder = true;
        }
        // Load the data and process it
        $tce->start($tceData, array());
        $tce->process_datamap();
        $this->importer->debug(
                'New IDs',
                0,
                $tce->substNEWwithIDs
        );
        // Store the number of new IDs created. This is used in error reporting later
        $numberOfNewIDs = count($tce->substNEWwithIDs);

        // Post-processing hook after data was saved
        $savedData = array();
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['external_import']['datamapPostProcess'])) {
            foreach ($tceData as $tableRecords) {
                foreach ($tableRecords as $id => $record) {
                    // Added status to record
                    // If operation was insert, match placeholder to actual id
                    $uid = $id;
                    if (isset($tce->substNEWwithIDs[$id])) {
                        $uid = $tce->substNEWwithIDs[$id];
                        $record['tx_externalimport:status'] = 'insert';
                    } else {
                        $record['tx_externalimport:status'] = 'update';
                    }
                    // Restore additional fields, if any
                    if ($this->getConfiguration()->getCountAdditionalFields() > 0) {
                        foreach ($savedAdditionalFields[$id] as $fieldName => $fieldValue) {
                            $record[$fieldName] = $fieldValue;
                        }
                    }
                    $savedData[$uid] = $record;
                }
            }
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['external_import']['datamapPostProcess'] as $className) {
                try {
                    $postProcessor = GeneralUtility::makeInstance($className);
                    $postProcessor->datamapPostProcess($table, $savedData, $this->importer);
                } catch (\Exception $e) {
                    $this->importer->debug(
                            sprintf(
                                    'Could not instantiate class %s for hook %s',
                                    $className,
                                    'datamapPostProcess'
                            ),
                            1
                    );
                }
            }
        }
        // Clean up
        unset($tceData);
        unset($savedData);

        // Mark as deleted records with existing uids that were not in the import data anymore
        // (if automatic delete is activated)
        if (GeneralUtility::inList($ctrlConfiguration['disabledOperations'], 'delete')) {
            $deletes = 0;
        } else {
            $absentUids = array_diff($existingUids, $updatedUids);
            // Call a pre-processing hook
            if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['external_import']['deletePreProcess'])) {
                foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['external_import']['deletePreProcess'] as $className) {
                    try {
                        $preProcessor = GeneralUtility::makeInstance($className);
                        $absentUids = $preProcessor->processBeforeDelete($table, $absentUids, $this->importer);
                    } catch (\Exception $e) {
                        $this->importer->debug(
                                sprintf(
                                        'Could not instantiate class %s for hook %s',
                                        $className,
                                        'deletePreProcess'
                                ),
                                1
                        );
                    }
                }
            }
            $deletes = count($absentUids);
            if ($deletes > 0) {
                $tceCommands = array($table => array());
                foreach ($absentUids as $id) {
                    $tceCommands[$table][$id] = array('delete' => 1);
                }
                $this->importer->debug(
                        'TCEmain commands',
                        0,
                        $tceCommands
                );
                $tce->start(array(), $tceCommands);
                $tce->process_cmdmap();
                // Call a post-processing hook
                if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['external_import']['cmdmapPostProcess'])) {
                    foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['external_import']['cmdmapPostProcess'] as $className) {
                        try {
                            $postProcessor = GeneralUtility::makeInstance($className);
                            $absentUids = $postProcessor->cmdmapPostProcess($table, $absentUids, $this->importer);
                        } catch (\Exception $e) {
                            $this->importer->debug(
                                    sprintf(
                                            'Could not instantiate class %s for hook %s',
                                            $className,
                                            'cmdmapPostProcess'
                                    ),
                                    1
                            );
                        }
                    }
                }
            }
        }

        // Perform post-processing of MM-relations if necessary
        if (count($fullMappings) > 0) {
            $this->postProcessMmRelations($fullMappings);
        }

        // Check if there were any errors reported by TCEmain
        if (count($tce->errorLog) > 0) {
            // If yes, get these messages from the sys_log table (assuming they are the latest ones)
            $where = "tablename = '" . $table . "' AND error > '0'";
            $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                    '*',
                    'sys_log',
                    $where, '',
                    'tstamp DESC',
                    count($tce->errorLog)
            );
            if ($res) {
                while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
                    // Check if there's a label for the message
                    $labelCode = 'msg_' . $row['type'] . '_' . $row['action'] . '_' . $row['details_nr'];
                    $label = LocalizationUtility::translate(
                            'LLL:EXT:belog/mod/locallang.xml:' . $labelCode,
                            'belog'
                    );
                    // If not, use details field
                    if (empty($label)) {
                        $label = $row['details'];
                    }
                    // Substitute the first 5 items of extra data into the error message
                    $message = $label;
                    if (!empty($row['log_data'])) {
                        $data = unserialize($row['log_data']);
                        $message = sprintf(
                                $label,
                                htmlspecialchars($data[0]),
                                htmlspecialchars($data[1]),
                                htmlspecialchars($data[2]),
                                htmlspecialchars($data[3]),
                                htmlspecialchars($data[4])
                        );
                    }
                    $this->importer->addMessage(
                            $message,
                            AbstractMessage::ERROR
                    );
                    $this->importer->debug(
                            $message,
                            3
                    );
                }
                $GLOBALS['TYPO3_DB']->sql_free_result($res);
            }
            // Subtract the number of new IDs from the number of inserts,
            // to get a realistic number of new records
            $newKeysCount = count($this->importer->getTemporaryKeys());
            // TODO: this is a really silly calculation!
            $inserts = $newKeysCount + ($numberOfNewIDs - $newKeysCount);
            // Add a warning that numbers reported (below) may not be accurate
            $this->importer->addMessage(
                    LocalizationUtility::translate(
                            'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:things_happened',
                            'external_import'
                    ),
                    AbstractMessage::WARNING
            );
        } else {
            $inserts = $numberOfNewIDs;
        }
        unset($tce);

        // Set informational messages
        $this->importer->addMessage(
                LocalizationUtility::translate(
                        'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:records_inserted',
                        'external_import',
                        array($inserts)
                ),
                AbstractMessage::OK
        );
        $this->importer->addMessage(
                LocalizationUtility::translate(
                        'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:records_updated',
                        'external_import',
                        array($updates)
                ),
                AbstractMessage::OK
        );
        $this->importer->addMessage(
                LocalizationUtility::translate(
                        'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:records_deleted',
                        'external_import',
                        array($deletes)
                ),
                AbstractMessage::OK
        );
    }

    /**
     * Stores all MM relations.
     *
     * Existing relations are deleted first.
     *
     * @param array $fullMappings List of all mapped data
     * @return void
     */
    protected function postProcessMmRelations($fullMappings)
    {
        $this->importer->debug(
                'Handling full mappings',
                0,
                $fullMappings
        );

        // Refresh list of existing primary keys now that new records have been inserted
        $this->importer->retrieveExistingUids();
        $existingUids = $this->importer->getExistingUids();

        // Loop on all columns that require a remapping
        $tableTca = $GLOBALS['TCA'][$this->importer->getTableName()];
        foreach ($fullMappings as $columnName => $mappingData) {
            $columnTcaConfiguration = $tableTca['columns'][$columnName]['config'];
            $mmTable = $columnTcaConfiguration['MM'];
            // Assemble extra condition if MM_insert_fields or MM_match_fields are defined
            $additionalWhere = '';
            $mmAdditionalFields = array();
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
                $additionalWhere .= ' AND ' . $column . ' = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($value, $mmTable);
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
                $GLOBALS['TYPO3_DB']->exec_DELETEquery(
                        $mmTable,
                        $referenceField . ' = ' . (int)$uid . $additionalWhere
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
                    $GLOBALS['TYPO3_DB']->exec_INSERTquery(
                            $mmTable,
                            $fields
                    );
                }
            }
        }
    }
}