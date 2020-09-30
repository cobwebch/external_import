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

use Cobweb\ExternalImport\Domain\Repository\UidRepository;
use Cobweb\ExternalImport\Exception\CriticalFailureException;
use Cobweb\ExternalImport\Utility\ManyToManyUtility;
use Cobweb\ExternalImport\Utility\SlugUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class StoreDataStep extends AbstractStep
{
    /**
     * @var array
     */
    protected $fieldsExcludedFromInserts = [];

    /**
     * @var array
     */
    protected $fieldsExcludedFromUpdates = [];

    /**
     * @var array List of fields found in the "substructureFields" that should not be saved.
     */
    protected $substructureFields = [];

    /**
     * @var UidRepository
     */
    protected $uidRepository;

    /**
     * Stores the data to the database using DataHandler.
     *
     * @return void
     * @throws \Cobweb\ExternalImport\Exception\MissingConfigurationException
     */
    public function run(): void
    {
        $manyToManyUtility = GeneralUtility::makeInstance(ManyToManyUtility::class);
        $manyToManyUtility->setImporter($this->importer);
        $records = $this->getData()->getRecords();
        $storedRecords = [];
        $this->importer->debug(
                'Data received for storage',
                0,
                $records
        );

        // Get the list of existing uids for the table
        $this->uidRepository = $this->importer->getUidRepository();
        $this->uidRepository->setConfiguration($this->getConfiguration());
        $existingUids = $this->uidRepository->getExistingUids();
        $currentPids = $this->uidRepository->getCurrentPids();
        // Make sure this list is an array (it may be null)
        $existingUids = $existingUids ?? [];
        $currentPids = $currentPids ?? [];

        $ctrlConfiguration = $this->getConfiguration()->getGeneralConfiguration();
        $columnConfiguration = $this->getConfiguration()->getColumnConfiguration();
        $table = $this->importer->getExternalConfiguration()->getTable();
        // Extract list of excluded fields
        $this->prepareStructuredInformation($columnConfiguration);
        // Handle many-to-many relations
        $manyToManyUtility->handleMmRelations($ctrlConfiguration, $columnConfiguration, $records);
        $mappings = $manyToManyUtility->getMappings();
        $hasMMRelations = count($mappings);

        // Insert or update records depending on existing uids
        $inserts = 0;
        $updates = 0;
        $moves = 0;
        $updatedUids = [];
        $handledUids = [];
        $tceData = [
                $table => []
        ];
        $tceCommands = [
                $table => []
        ];
        $savedAdditionalFields = [];
        // Prepare some data before the loop
        $storagePid = $this->getConfiguration()->getStoragePid();
        $configuredAdditionalFields = $this->getConfiguration()->getAdditionalFields();
        $countConfiguredAdditionalFields = $this->getConfiguration()->getCountAdditionalFields();
        $isUpdateAllowed = !GeneralUtility::inList($ctrlConfiguration['disabledOperations'], 'update');
        $isInsertAllowed = !GeneralUtility::inList($ctrlConfiguration['disabledOperations'], 'insert');
        $updateSlugs = (bool)$this->getConfiguration()->getGeneralConfigurationProperty('updateSlugs');
        foreach ($records as $theRecord) {
            $localAdditionalFields = [];
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
            if ($countConfiguredAdditionalFields > 0) {
                foreach ($configuredAdditionalFields as $fieldName) {
                    $localAdditionalFields[$fieldName] = $theRecord[$fieldName];
                    unset($theRecord[$fieldName]);
                }
            }
            // Also remove fields coming from substructures.
            // These, however, are not saved for later use.
            // Question: should this saving of additional fields be actually deprecated? It does not seem very useful...
            foreach ($this->substructureFields as $field) {
                unset($theRecord[$field]);
            }

            $theID = '';
            // Reference uid is found, perform an update (if not disabled)
            if (isset($existingUids[$externalUid])) {
                if ($isUpdateAllowed) {
                    // First call a pre-processing hook
                    if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['external_import']['updatePreProcess'])) {
                        foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['external_import']['updatePreProcess'] as $className) {
                            try {
                                $preProcessor = GeneralUtility::makeInstance($className);
                                $theRecord = $preProcessor->processBeforeUpdate($theRecord, $this->importer);
                            } catch (CriticalFailureException $e) {
                                $this->abortFlag = true;
                                return;
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
                    if (count($this->fieldsExcludedFromUpdates) > 0) {
                        foreach ($this->fieldsExcludedFromUpdates as $excludedField) {
                            unset($theRecord[$excludedField]);
                        }
                    }

                    $theID = $existingUids[$externalUid];
                    $tceData[$table][$theID] = $theRecord;
                    // Check if some records have a changed "pid", in which case a "move" action is also needed
                    if (array_key_exists('pid', $theRecord) && (int)$theRecord['pid'] !== $currentPids[$externalUid]) {
                        $tceCommands[$table][$theID] = [
                                'move' => (int)$theRecord['pid']
                        ];
                        $moves++;
                    }
                    $updatedUids[] = $theID;
                    $updates++;
                }

                // Reference uid not found, perform an insert (if not disabled)
            } elseif ($isInsertAllowed) {

                // First call a pre-processing hook
                if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['external_import']['insertPreProcess'])) {
                    foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['external_import']['insertPreProcess'] as $className) {
                        try {
                            $preProcessor = GeneralUtility::makeInstance($className);
                            $theRecord = $preProcessor->processBeforeInsert($theRecord, $this->importer);
                        } catch (CriticalFailureException $e) {
                            $this->abortFlag = true;
                            return;
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
                if (count($this->fieldsExcludedFromInserts) > 0) {
                    foreach ($this->fieldsExcludedFromInserts as $excludedField) {
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
                    $theID = $this->importer->generateTemporaryKey();
                }
                $tceData[$table][$theID] = $theRecord;
            }
            $storedRecords[] = array_merge(
                    ['uid' => $theID],
                    $theRecord
            );
            // Store local additional fields into general additional fields array
            // keyed to proper id's (if the record was processed)
            if (!empty($theID)) {
                $savedAdditionalFields[$theID] = $localAdditionalFields;
            }
        }
        // If the target table is pages, perform some special sorting to ensure that parent pages
        // are created before their children
        if (array_key_exists('pages', $tceData)) {
            $tceData['pages'] = $this->sortPagesData($tceData['pages']);
        }
        $this->importer->debug(
                'TCEmain data',
                0,
                $tceData
        );
        $this->importer->debug(
                'TCEmain commands',
                0,
                (count($tceCommands[$table]) > 0) ? $tceCommands : null
        );

        // Create an instance of DataHandler and process the data
        /** @var $tce DataHandler */
        $tce = GeneralUtility::makeInstance(DataHandler::class);
        // Check if TCEmain logging should be turned on or off
        $extensionConfiguration = $this->importer->getExtensionConfiguration();
        $disableLogging = empty($extensionConfiguration['disableLog']) ? false : true;
        if (array_key_exists('disableLog', $ctrlConfiguration)) {
            $disableLogging = empty($ctrlConfiguration['disableLog']) ? false : true;
        }
        $tce->enableLogging = !$disableLogging;
        // If the table has a sorting field, reverse the data array,
        // otherwise the first record will come last (because TCEmain
        // itself inverts the incoming order)
        if (!empty($GLOBALS['TCA'][$table]['ctrl']['sortby'])) {
            $tce->reverseOrder = true;
        }
        $savedData = [];

        // Load the data and process it, if not in preview mode
        if (!$this->importer->isPreview()) {
            try {
                $tce->start($tceData, $tceCommands);
                $tce->process_datamap();
                $tce->process_cmdmap();
                $this->importer->debug(
                        'New IDs',
                        0,
                        $tce->substNEWwithIDs
                );
                $inserts = count($tce->substNEWwithIDs);
                // Update the slug fields, if activated
                if ($updateSlugs && count($updatedUids) > 0) {
                    $this->updateSlugs($table, $updatedUids);
                }

                // Substitute NEW temporary keys with actual IDs in the "stored records" array
                foreach ($storedRecords as $index => $record) {
                    if (isset($tce->substNEWwithIDs[$record['uid']]) && strpos($record['uid'], 'NEW') === 0) {
                        $storedRecords[$index]['uid'] = $tce->substNEWwithIDs[$record['uid']];
                    }
                }

                // Post-processing hook after data was saved
                if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['external_import']['datamapPostProcess'])) {
                    foreach ($tceData as $tableRecords) {
                        foreach ($tableRecords as $id => $record) {
                            // Add status to record
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
                        } catch (CriticalFailureException $e) {
                            $this->abortFlag = true;
                            return;
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
            } catch (\Exception $e) {
                // Abort the process and report about the error
                $this->handleTceException($e);
                return;
            }
        }

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
                    } catch (CriticalFailureException $e) {
                        $this->abortFlag = true;
                        return;
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
                $tceDeleteCommands = [
                        $table => []
                ];
                foreach ($absentUids as $id) {
                    $tceDeleteCommands[$table][$id] = [
                            'delete' => 1
                    ];
                }
                $this->importer->debug(
                        'TCEmain commands',
                        0,
                        $tceDeleteCommands
                );
                // Actually delete the records, if not in preview mode
                if (!$this->importer->isPreview()) {
                    $tce->start([], $tceDeleteCommands);
                    try {
                        $tce->process_cmdmap();
                        // Call a post-processing hook
                        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['external_import']['cmdmapPostProcess'])) {
                            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['external_import']['cmdmapPostProcess'] as $className) {
                                try {
                                    $postProcessor = GeneralUtility::makeInstance($className);
                                    $absentUids = $postProcessor->cmdmapPostProcess($table, $absentUids, $this->importer);
                                } catch (CriticalFailureException $e) {
                                    $this->abortFlag = true;
                                    return;
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
                    } catch (\Exception $e) {
                        // Abort the process and report about the error
                        $this->handleTceException($e);
                        return;
                    }
                }
            }
        }

        // Perform post-processing of MM-relations if necessary and if not in preview mode
        if (!$this->importer->isPreview() && count($manyToManyUtility->getFullMappings()) > 0) {
            $manyToManyUtility->postProcessMmRelations();
        }

        // Report any errors that might have been raised by the DataHandler
        $this->reportTceErrors($tce->errorLog);

        // Set informational messages (not in preview mode)
        if (!$this->importer->isPreview()) {
            $this->importer->addMessage(
                    LocalizationUtility::translate(
                            'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:records_inserted',
                            'external_import',
                            [$inserts]
                    ),
                    AbstractMessage::OK
            );
            $this->importer->addMessage(
                    LocalizationUtility::translate(
                            'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:records_updated',
                            'external_import',
                            [$updates]
                    ),
                    AbstractMessage::OK
            );
            $this->importer->addMessage(
                    LocalizationUtility::translate(
                            'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:records_deleted',
                            'external_import',
                            [$deletes]
                    ),
                    AbstractMessage::OK
            );
            $this->importer->addMessage(
                    LocalizationUtility::translate(
                            'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:records_moved',
                            'external_import',
                            [$moves]
                    ),
                    AbstractMessage::OK
            );
            // Store the number of operations in the reporting utility
            $this->importer->getReportingUtility()->setValueForStep(
                    self::class,
                    'inserts',
                    $inserts
            );
            $this->importer->getReportingUtility()->setValueForStep(
                    self::class,
                    'updates',
                    $updates
            );
            $this->importer->getReportingUtility()->setValueForStep(
                    self::class,
                    'deletes',
                    $deletes
            );
            $this->importer->getReportingUtility()->setValueForStep(
                    self::class,
                    'moves',
                    $moves
            );
        }

        // Set the "stored records" array as the new records of the Data object
        $this->data->setRecords($storedRecords);
        // Use the TCE data and commands as preview data
        // NOTE: both sets of commands are presented separately and not merged, because array_merge_recursive()
        // renumbers numerical indices, which is wrong (numerical indices in TCE structures correspond to uids)
        $this->importer->setPreviewData(
                [
                        'data' => $tceData,
                        'commands-delete' => $tceDeleteCommands ?? [],
                        'commands-move' => $tceCommands
                ]
        );

        // Free some memory
        unset($tce, $tceData, $savedData, $tceCommands, $tceDeleteCommands);
    }

    /**
     * Parses the column configuration and prepares various lists of properties for better performance.
     *
     * @param array $columnConfiguration External Import configuration for the columns
     */
    public function prepareStructuredInformation($columnConfiguration): void
    {
        foreach ($columnConfiguration as $columnName => $columnData) {
            // Assemble the list of fields defined with the "substructureFields" property
            // These fields must be removed from the incoming data before it is saved to the database
            if (isset($columnData['substructureFields'])) {
                foreach ($columnData['substructureFields'] as $fieldName => $fieldConfiguration) {
                    // Ignore fields which match the column name. These must stay and be saved.
                    if ($fieldName !== $columnName) {
                        $this->substructureFields[] = $fieldName;
                    }
                }
            }
            if (array_key_exists('disabledOperations', $columnData)) {
                if (GeneralUtility::inList($columnData['disabledOperations'], 'insert')) {
                    $this->fieldsExcludedFromInserts[] = $columnName;
                }
                if (GeneralUtility::inList($columnData['disabledOperations'], 'update')) {
                    $this->fieldsExcludedFromUpdates[] = $columnName;
                }
            }
        }
    }

    /**
     * Reports about errors that happened during DataHandler operations.
     *
     * NOTE: this is rather approximate, as there's no way to know for sure
     * that we are retrieving the right messages, not to decipher their meaning.
     *
     * @param array $errorLog
     * @return void
     */
    protected function reportTceErrors($errorLog): void
    {
        if (count($errorLog) > 0) {
            // If there are errors, get these messages from the sys_log table (assuming they are the latest ones)
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_log');
            $res = $queryBuilder->select('*')
                    ->from('sys_log')
                    ->where(
                            $queryBuilder->expr()->eq(
                                    'tablename',
                                    $queryBuilder->createNamedParameter(
                                            $this->getConfiguration()->getTable()
                                    )
                            )
                    )
                    ->andWhere(
                            $queryBuilder->expr()->gt(
                                    'error',
                                    0
                            )
                    )
                    ->orderBy(
                            'tstamp',
                            'DESC'
                    )
                    ->setMaxResults(
                            count($errorLog)
                    )
                    ->execute();
            if ($res) {
                while ($row = $res->fetch(\PDO::FETCH_ASSOC)) {
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
                        $data = unserialize($row['log_data'], ['allowed_classes' => false]);
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
                            $message
                    );
                    $this->importer->debug(
                            $message,
                            3
                    );
                }
            }
            // Add a warning that number of operations reported may not be accurate
            $this->importer->addMessage(
                    LocalizationUtility::translate(
                            'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:things_happened',
                            'external_import'
                    ),
                    AbstractMessage::WARNING
            );
        }
    }

    /**
     * Sorts the TCE data for pages, so that parent pages come before child pages.
     *
     * If this is not done, insertion of new pages into the database will fail as pids may be unresolved.
     * Also, this needs to be done "in reverse", as DataHandler we later request DataHandler to reverse order
     * for tables with a sorting field.
     *
     * @param array $data
     * @return array
     */
    public function sortPagesData(array $data): array
    {
        $originalData = $data;
        $levelPages = [];
        $sortedData = [];
        // Extract pages which don't have a "NEW" pid
        foreach ($data as $id => $fields) {
            if (strpos($fields['pid'], 'NEW') === false) {
                $levelPages[] = (strpos($id, 'NEW') === 0) ? $id : (int)$id;
                $sortedData[$id] = $fields;
                unset($data[$id]);
            }
        }
        // If all pages have a non-NEW pid, no special sorting is needed. Exit early and return the original data set.
        if (count($data) === 0) {
            return $originalData;
        }

        // Recursively sort pages
        while (count($levelPages) > 0) {
            $levelPages = $this->extractLevelPages($levelPages, $data, $sortedData);
        }
        return $sortedData;
    }

    /**
     * Checks which pages belong to the current level ("level" being a series of pids), sorts
     * pages according to that and returns list of pages for next tree level.
     *
     * @param array $levelPages
     * @param array $data
     * @param array $sortedData
     * @return array
     */
    public function extractLevelPages(array $levelPages, array &$data, array &$sortedData): array
    {
        $nextLevelPages = [];
        $pagesForLevel = [];
        foreach ($data as $id => $fields) {
            $pid = (strpos($fields['pid'], 'NEW') === 0) ? $fields['pid'] : (int)$fields['pid'];
            if (in_array($pid, $levelPages, true)) {
                $pagesForLevel[$id] = $fields;
                $nextLevelPages[] = (strpos($id, 'NEW') === 0) ? $id : (int)$id;
                unset($data[$id]);
            }
        }
        // Put level pages "above" already sorted pages, to take into account a reversed usage later
        ArrayUtility::mergeRecursiveWithOverrule(
                $pagesForLevel,
                $sortedData
        );
        $sortedData = $pagesForLevel;
        return $nextLevelPages;
    }

    /**
     * Updates the slug fields for the given records of the given table.
     *
     * @param string $table Name of the affected table
     * @param array $uids List of primary keys of records to update
     */
    public function updateSlugs($table, $uids): void {
        $slugUtility = GeneralUtility::makeInstance(SlugUtility::class, $this->importer);
        $slugUtility->updateAll($table, $uids);
    }

    /**
     * Handles exceptions that happen when using the DataHandler to execute data or command structures.
     *
     * @param \Exception $e
     * @return void
     */
    protected function handleTceException(\Exception $e): void
    {
        // Set the abort flag to interrupt the process
        $this->abortFlag = true;
        // Add an error message
        $this->importer->addMessage(
                LocalizationUtility::translate(
                        'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:exceptionOccurredDuringSave',
                        'external_import',
                        [
                                $e->getMessage(),
                                $e->getCode(),
                                $e->getFile(),
                                $e->getLine()
                        ]
                )
        );
        // Send the call trail to the debut output
        $this->importer->debug(
                'Stack trace',
                3,
                $e->getTraceAsString()
        );
    }

    /**
     * Returns the list of fields having defined with the "substructureFields" property.
     *
     * @return array
     */
    public function getSubstructureFields(): array
    {
        return $this->substructureFields;
    }

    /**
     * Returns the list of fields excluded from the insert operation.
     *
     * @return array
     */
    public function getFieldsExcludedFromInserts(): array
    {
        return $this->fieldsExcludedFromInserts;
    }

    /**
     * Returns the list of fields excluded from the update operation.
     *
     * @return array
     */
    public function getFieldsExcludedFromUpdates(): array
    {
        return $this->fieldsExcludedFromUpdates;
    }
}