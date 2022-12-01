<?php

declare(strict_types=1);

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

namespace Cobweb\ExternalImport\Step;

use Cobweb\ExternalImport\Domain\Model\Configuration;
use Cobweb\ExternalImport\Domain\Model\Dto\ChildrenSorting;
use Cobweb\ExternalImport\Domain\Repository\ChildrenRepository;
use Cobweb\ExternalImport\Event\CmdmapPostprocessEvent;
use Cobweb\ExternalImport\Event\DatamapPostprocessEvent;
use Cobweb\ExternalImport\Event\DeleteRecordsPreprocessEvent;
use Cobweb\ExternalImport\Event\InsertRecordPreprocessEvent;
use Cobweb\ExternalImport\Event\UpdateRecordPreprocessEvent;
use Cobweb\ExternalImport\Exception\CriticalFailureException;
use Cobweb\ExternalImport\Utility\ChildrenSortingUtility;
use Cobweb\ExternalImport\Utility\CompatibilityUtility;
use Cobweb\ExternalImport\Utility\SlugUtility;
use Psr\EventDispatcher\EventDispatcherInterface;
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
     * @var array List of substructure fields for each column (if any)
     */
    protected $substructureFields = [];

    /**
     * @var array Temporary storage for the values that are not saved and that are restored after saving
     */
    protected $valuesExcludedFromSaving = [];

    /**
     * @var array Map of internal id (maybe "NEW***" for new records) to external id (reference uid in the external data)
     */
    protected $idToExternalIdMap = [];

    /**
     * @var array List of all columns having a "children" configuration (preloaded to avoid looping on the whole structure everytime)
     */
    protected $childColumns = [];

    /**
     * @var bool True if at least one column has a "children" configuration (preloaded to avoid looping on the whole structure everytime)
     */
    protected $hasChildColumns = false;

    /**
     * @var array List of records from the main table that need to be deleted
     */
    protected $mainRecordsToDelete = [];

    /**
     * @var array For each field with "children", for each parent record, reference values for the "delete" operation
     */
    protected $childrenReferenceValues = [];

    /**
     * @var array List of child records that need to be deleted
     */
    protected $childRecordsToDelete = [];

    /**
     * @var ChildrenSorting DTO object for storing children sorting data
     */
    protected ChildrenSorting $childrenSortingInformation;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher, ChildrenSorting $childrenSortingInformation)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->childrenSortingInformation = $childrenSortingInformation;
    }

    public function __toString()
    {
        return self::class;
    }

    /**
     * Stores the data to the database using DataHandler.
     *
     * @return void
     * @throws \Cobweb\ExternalImport\Exception\MissingConfigurationException
     */
    public function run(): void
    {
        $records = $this->getData()->getRecords();
        $this->importer->debug(
            'Data received for storage',
            0,
            $records
        );

        // Load the list of existing uids for the table
        $currentPids = $this->importer->getUidRepository()->getCurrentPids() ?? [];

        $generalConfiguration = $this->importer->getExternalConfiguration()->getGeneralConfiguration();
        $columnConfiguration = $this->importer->getExternalConfiguration()->getColumnConfiguration();
        $mainTable = $this->importer->getExternalConfiguration()->getTable();
        // Extract list of excluded fields
        $this->prepareStructuredInformation($columnConfiguration);

        // Initialize some variables
        $inserts = 0;
        $updates = 0;
        $moves = 0;
        $updatedUids = [];
        $tceData = [
            $mainTable => []
        ];
        $tceCommands = [
            $mainTable => []
        ];
        $storedRecords = [
            $mainTable => []
        ];

        // Prepare some data before the loop
        $storagePid = $this->importer->getExternalConfiguration()->getStoragePid();
        $updateSlugs = array_key_exists('updateSlugs', $generalConfiguration) ? (bool)$generalConfiguration['updateSlugs']: false;

        // Prepare the data to store
        $dateToStore = $this->prepareDataToStore();

        foreach ($dateToStore as $id => $theRecord) {
            $isExistingRecord = strpos((string)$id, 'NEW') === false;

            // TODO: this was used for legacy MM handling and current pids, but current pids could be changed to use $id, so this could be dropped at a later point
            $externalUid = $this->idToExternalIdMap[$id];

            // Gather the record's pid
            // Existing records have their own (unless they have been moved)
            if ($isExistingRecord) {
                $recordPid = $theRecord['pid'] ?? $currentPids[$externalUid];
                // New records have their own or fall back to the general storage pid
            } else {
                $recordPid = $theRecord['pid'] ?? $storagePid;
            }

            // Move child records into their proper position
            if ($this->hasChildColumns) {
                foreach ($this->childColumns as $columnName => $columnConfiguration) {
                    $childTable = $columnConfiguration['table'];
                    if (!isset($tceData[$childTable])) {
                        $tceData[$childTable] = [];
                        $storedRecords[$childTable] = [];
                    }
                    // Create TCE entries for each child record
                    $childrenList = [];
                    if (isset($theRecord['__children__'][$columnName][$childTable])) {
                        foreach ($theRecord['__children__'][$columnName][$childTable] as $childId => $childData) {
                            $childrenList[] = $childId;
                            // Child records need to be stored in the same page as their parent
                            $childData['pid'] = $recordPid;
                            $tceData[$childTable][$childId] = $childData;
                            $storedRecords[$childTable][] = array_merge(
                                ['uid' => $childId],
                                $childData
                            );
                        }
                    }
                    // The actual column value should be a comma-separated list of child ids
                    // In case of updates, it must not be set at all if the list is empty
                    if ($isExistingRecord && count($childrenList) === 0) {
                        unset($theRecord[$columnName]);
                    } else {
                        $theRecord[$columnName] = implode(',', $childrenList);
                    }
                }
                // Remove the temporary child records information
                unset($theRecord['__children__']);
            }

            // Register record for update
            if ($isExistingRecord) {
                // First call a pre-processing event
                try {
                    /** @var UpdateRecordPreprocessEvent $event */
                    $event = $this->eventDispatcher->dispatch(
                        new UpdateRecordPreprocessEvent(
                            (int)$id,
                            $theRecord,
                            $this->importer
                        )
                    );
                    $theRecord = $event->getRecord();
                } catch (CriticalFailureException $e) {
                    $this->abortFlag = true;
                    return;
                } catch (\Exception $e) {
                    $this->importer->debug(
                        sprintf(
                            'An error happened during event %s (error: %s, code: %d)',
                            UpdateRecordPreprocessEvent::class,
                            $e->getMessage(),
                            $e->getCode()
                        ),
                        1
                    );
                }
                // First call a pre-processing hook
                // Using a hook is deprecated
                // TODO: remove in the next major version
                $hooks = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['external_import']['updatePreProcess'] ?? null;
                if (is_array($hooks)) {
                    trigger_error('Hook "updatePreProcess" is deprecated. Use \Cobweb\ExternalImport\Event\UpdateRecordPreprocessEvent instead.', E_USER_DEPRECATED);
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
                                    'Could not use class %s for hook %s (error: %s, code: %d)',
                                    $className,
                                    'updatePreProcess',
                                    $e->getMessage(),
                                    $e->getCode()
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

                $tceData[$mainTable][$id] = $theRecord;
                // Check if some records have a changed "pid", in which case a "move" action is also needed
                if (array_key_exists('pid', $theRecord) && (int)$theRecord['pid'] !== $currentPids[$externalUid]) {
                    $tceCommands[$mainTable][$id] = [
                        'move' => (int)$theRecord['pid']
                    ];
                    $moves++;
                }
                $updatedUids[] = $id;
                $updates++;
                // Register record for insert
            } else {
                // First call a pre-processing event
                try {
                    /** @var InsertRecordPreprocessEvent $event */
                    $event = $this->eventDispatcher->dispatch(
                        new InsertRecordPreprocessEvent(
                            $theRecord,
                            $this->importer
                        )
                    );
                    $theRecord = $event->getRecord();
                } catch (CriticalFailureException $e) {
                    $this->abortFlag = true;
                    return;
                } catch (\Exception $e) {
                    $this->importer->debug(
                        sprintf(
                            'An error happened during event %s (error: %s, code: %d)',
                            InsertRecordPreprocessEvent::class,
                            $e->getMessage(),
                            $e->getCode()
                        ),
                        1
                    );
                }
                // First call a pre-processing hook
                // Using a hook is deprecated
                // TODO: remove in the next major version
                $hooks = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['external_import']['insertPreProcess'] ?? null;
                if (is_array($hooks)) {
                    trigger_error('Hook "insertPreProcess" is deprecated. Use \Cobweb\ExternalImport\Event\InsertRecordPreprocessEvent instead.', E_USER_DEPRECATED);
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
                                    'Could not use class %s for hook %s (error: %s, code: %d)',
                                    $className,
                                    'insertPreProcess',
                                    $e->getMessage(),
                                    $e->getCode()
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
                $tceData[$mainTable][$id] = $theRecord;
            }
            $storedRecords[$mainTable][] = array_merge(
                ['uid' => $id],
                $theRecord
            );
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
            $tceCommands
        );

        // Create an instance of DataHandler and process the data
        /** @var $tce DataHandler */
        $tce = GeneralUtility::makeInstance(DataHandler::class);
        // Check if TCEmain logging should be turned on or off
        $extensionConfiguration = $this->importer->getExtensionConfiguration();
        $disableLogging = empty($extensionConfiguration['disableLog']) ? false : true;
        if (array_key_exists('disableLog', $generalConfiguration)) {
            $disableLogging = empty($generalConfiguration['disableLog']) ? false : true;
        }
        $tce->enableLogging = !$disableLogging;
        // If the table has a sorting field, reverse the data array,
        // otherwise the first record will come last (because TCEmain
        // itself inverts the incoming order)
        if (!empty($GLOBALS['TCA'][$mainTable]['ctrl']['sortby'])) {
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
                    $this->updateSlugs($mainTable, $updatedUids);
                }

                // Substitute NEW temporary keys with actual IDs in the "stored records" array
                foreach ($storedRecords as $table => $listOfRecords) {
                    foreach ($listOfRecords as $index => $record) {
                        if (isset($tce->substNEWwithIDs[$record['uid']])) {
                            $storedRecords[$table][$index]['uid'] = $tce->substNEWwithIDs[$record['uid']];
                        }
                    }
                }
                // Do the same in the children sorting information
                $this->childrenSortingInformation->replaceAllNewIds($tce->substNEWwithIDs);

                // Prepare data for post-processing
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
                        // Restore excluded fields, if any
                        if (isset($this->valuesExcludedFromSaving[$id])) {
                            foreach ($this->valuesExcludedFromSaving[$id] as $fieldName => $fieldValue) {
                                $record[$fieldName] = $fieldValue;
                            }
                        }
                        $savedData[$uid] = $record;
                    }
                }
                // Post-processing event after data was saved
                try {
                    /** @var DatamapPostprocessEvent $event */
                    $this->eventDispatcher->dispatch(
                        new DatamapPostprocessEvent(
                            $savedData,
                            $this->importer
                        )
                    );
                } catch (CriticalFailureException $e) {
                    $this->abortFlag = true;
                    return;
                } catch (\Exception $e) {
                    $this->importer->debug(
                        sprintf(
                            'An error happened during event %s (error: %s, code: %d)',
                            DatamapPostprocessEvent::class,
                            $e->getMessage(),
                            $e->getCode()
                        ),
                        1
                    );
                }
                // Post-processing hook after data was saved
                // Using a hook is deprecated
                // TODO: remove in the next major version
                $hooks = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['external_import']['datamapPostProcess'] ?? null;
                if (is_array($hooks)) {
                    trigger_error('Hook "datamapPostProcess" is deprecated. Use \Cobweb\ExternalImport\Event\DatamapPostprocessEvent instead.', E_USER_DEPRECATED);
                    foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['external_import']['datamapPostProcess'] as $className) {
                        try {
                            $postProcessor = GeneralUtility::makeInstance($className);
                            $postProcessor->datamapPostProcess($mainTable, $savedData, $this->importer);
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

        $tceDeleteCommands = [];
        $deletes = 0;
        // Register all child records marked for deletion
        foreach ($this->childRecordsToDelete as $childTable => $childList) {
            $tceDeleteCommands = [
                $childTable => []
            ];
            foreach ($childList as $child) {
                $tceDeleteCommands[$childTable][$child] = [
                    'delete' => 1
                ];
                $deletes++;
            }
        }
        // Now for the main table, mark as deleted those records with existing uids that were not in the import data anymore
        // (if automatic delete is activated)
        $absentUids = [];
        $operations = $generalConfiguration['disabledOperations'] ?? '';
        if (!GeneralUtility::inList($operations, 'delete')) {
            $absentUids = $this->mainRecordsToDelete;
            // Call a pre-processing event
            try {
                /** @var DeleteRecordsPreprocessEvent $event */
                $event = $this->eventDispatcher->dispatch(
                    new DeleteRecordsPreprocessEvent(
                        $absentUids,
                        $this->importer
                    )
                );
                $absentUids = $event->getRecords();
            } catch (CriticalFailureException $e) {
                $this->abortFlag = true;
                return;
            } catch (\Exception $e) {
                $this->importer->debug(
                    sprintf(
                        'An error happened during event %s (error: %s, code: %d)',
                        DeleteRecordsPreprocessEvent::class,
                        $e->getMessage(),
                        $e->getCode()
                    ),
                    1
                );
            }
            // Call a pre-processing hook
            // Using a hook is deprecated
            // TODO: remove in the next major version
            $hooks = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['external_import']['deletePreProcess'] ?? null;
            if (is_array($hooks)) {
                trigger_error('Hook "deletePreProcess" is deprecated. Use \Cobweb\ExternalImport\Event\DeleteRecordsPreprocessEvent instead.', E_USER_DEPRECATED);
                foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['external_import']['deletePreProcess'] as $className) {
                    try {
                        $preProcessor = GeneralUtility::makeInstance($className);
                        $absentUids = $preProcessor->processBeforeDelete($mainTable, $absentUids, $this->importer);
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
            if (count($absentUids) > 0) {
                $tceDeleteCommands[$mainTable] = [];
                foreach ($absentUids as $id) {
                    $tceDeleteCommands[$mainTable][$id] = [
                        'delete' => 1
                    ];
                    $deletes++;
                }
            }
        }
        $this->importer->debug(
            'TCEmain commands',
            0,
            $tceDeleteCommands
        );
        // Actually delete the records, if not in preview mode
        if ($deletes > 0 && !$this->importer->isPreview()) {
            $tce->start([], $tceDeleteCommands);
            try {
                $tce->process_cmdmap();
                // Call a post-processing event
                try {
                    /** @var CmdmapPostprocessEvent $event */
                    $this->eventDispatcher->dispatch(
                        new CmdmapPostprocessEvent(
                            $absentUids,
                            $this->importer
                        )
                    );
                } catch (CriticalFailureException $e) {
                    $this->abortFlag = true;
                    return;
                } catch (\Exception $e) {
                    $this->importer->debug(
                        sprintf(
                            'An error happened during event %s (error: %s, code: %d)',
                            CmdmapPostprocessEvent::class,
                            $e->getMessage(),
                            $e->getCode()
                        ),
                        1
                    );
                }
                // Call a post-processing hook
                // Using a hook is deprecated
                // TODO: remove in the next major version
                $hooks = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['external_import']['cmdmapPostProcess'] ?? null;
                if (is_array($hooks)) {
                    trigger_error('Hook "cmdmapPostProcess" is deprecated. Use \Cobweb\ExternalImport\Event\CmdmapPostprocessEvent instead.', E_USER_DEPRECATED);
                    foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['external_import']['cmdmapPostProcess'] as $className) {
                        try {
                            $postProcessor = GeneralUtility::makeInstance($className);
                            $absentUids = $postProcessor->cmdmapPostProcess($mainTable, $absentUids, $this->importer);
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

        // Report any errors that might have been raised by the DataHandler
        $this->reportTceErrors($tce->errorLog);

        // Sort children, if needed and if not in preview mode
        if ($this->childrenSortingInformation->hasSortingInformation() && !$this->importer->isPreview()) {
            $sortingUtility = GeneralUtility::makeInstance(
                ChildrenSortingUtility::class,
                $this->importer
            );
            $sortingUtility->sortChildRecords($this->childrenSortingInformation);
        }

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
     * Assembles all the values to save from the external data, dropping the values that must not be stored into the
     * database and collapsing multiple values coming from denormalized data.
     *
     * Also creates the "child" sub-structure for IRRE relations.
     *
     * @return array
     * @throws \Cobweb\ExternalImport\Exception\MissingConfigurationException
     */
    public function prepareDataToStore(): array
    {
        $existingUids = $this->importer->getUidRepository()->getExistingUids() ?? [];
        $records = $this->getData()->getRecords();
        $generalConfiguration = $this->importer->getExternalConfiguration()->getGeneralConfiguration();
        $referenceUid = $generalConfiguration['referenceUid'];
        $columnConfiguration = $this->importer->getExternalConfiguration()->getColumnConfiguration();
        $table = $this->importer->getExternalConfiguration()->getTable();
        $operations = $generalConfiguration['disabledOperations'] ?? '';
        $isUpdateAllowed = !GeneralUtility::inList(
            $operations,
            'update'
        );
        $isInsertAllowed = !GeneralUtility::inList(
            $operations,
            'insert'
        );

        // Check if at least one column expects denormalized data
        $denormalizedColumns = [];
        $denormalizedSorting = [];
        foreach ($columnConfiguration as $name => $configuration) {
            if (array_key_exists('multipleRows', $configuration)) {
                $denormalizedColumns[] = $name;
            }
            if (array_key_exists('multipleSorting', $configuration)) {
                $denormalizedSorting[$name] = $configuration['multipleSorting'];
            }
        }
        $countDenormalizedColumns = count($denormalizedColumns);
        // Loop on all records to keep only data to store and assemble the list of multiple values (if any)
        $dataToStore = [];
        $multipleValues = [];
        $handledUids = [];
        foreach ($records as $record) {
            // If the external key (reference id) is not set, skip record
            if (!isset($record[$referenceUid])) {
                continue;
            }
            $externalId = $record[$referenceUid];
            // Get the existing uid or generate a key if no uid is found (e.g. it's a new record)
            if (isset($existingUids[$externalId])) {
                $id = $existingUids[$externalId];
                // Even if update is not allowed, mark the record as having been handled
                $handledUids[] = $id;
                // If update is not allowed, skip this record
                if (!$isUpdateAllowed) {
                    continue;
                }
            } else {
                // If insert is not allowed, skip this record
                if (!$isInsertAllowed) {
                    continue;
                }
                // If a temporary key was already defined, use it, otherwise create a new one.
                // Temporary keys may exist if self-referential mapping was handled beforehand (see mapData())
                // or in case of denormalized data (the same external id appears several times)
                if ($this->importer->getTemporaryKeyRepository()->hasTemporaryKey($externalId, $table)) {
                    $id = $this->importer->getTemporaryKeyRepository()->getTemporaryKeyForValue($externalId, $table);
                } else {
                    $id = $this->importer->getTemporaryKeyRepository()->generateTemporaryKey();
                    $this->importer->getTemporaryKeyRepository()->addTemporaryKey($externalId, $id, $table);
                }
            }
            // Store correspondence between internal id and external id
            $this->idToExternalIdMap[$id] = $externalId;
            // If the record has not been handled yet, store all the values that are not to be excluded
            if (!isset($dataToStore[$id])) {
                $dataToStore[$id] = [];
                $this->valuesExcludedFromSaving[$id] = [];
                foreach ($columnConfiguration as $name => $configuration) {
                    // The values that are excluded are temporarily stored for later restoration
                    if (array_key_exists(Configuration::DO_NOT_SAVE_KEY, $configuration)) {
                        $this->valuesExcludedFromSaving[$id][$name] = $record[$name];
                        // Make sure a value actually exists
                    } elseif (isset($record[$name])) {
                        $dataToStore[$id][$name] = $record[$name];
                    }
                }
            }
            // Handle multiple values, if any
            if ($countDenormalizedColumns > 0) {
                if (!isset($multipleValues[$id])) {
                    $multipleValues[$id] = [];
                }
                foreach ($denormalizedColumns as $name) {
                    if (!isset($multipleValues[$id][$name])) {
                        $multipleValues[$id][$name] = [];
                    }
                    // If the multiple values need to be sorted, store the value of the sorting field
                    // The value becomes an array, otherwise it remains a simple value (we gain performance
                    // later when actually using the multiple values)
                    if (isset($denormalizedSorting[$name])) {
                        $multipleEntry = [
                            'value' => $record[$name],
                            'sorting' => $record[$denormalizedSorting[$name]]
                        ];
                    } else {
                        $multipleEntry = $record[$name];
                    }
                    $multipleValues[$id][$name][] = $multipleEntry;
                }
            }
            // Assemble children records, if any
            if ($this->hasChildColumns) {
                if (!isset($dataToStore[$id]['__children__'])) {
                    $dataToStore[$id]['__children__'] = [];
                }
                foreach ($this->childColumns as $mainColumnName => $childColumnConfiguration) {
                    $childTable = $childColumnConfiguration['table'];
                    $childConfiguration = $childColumnConfiguration['columns'];
                    // Generate the child structure only if the record has a value for the given column,
                    // else incomplete data will ensue
                    if (isset($record[$mainColumnName])) {
                        if (!isset($dataToStore[$id]['__children__'][$mainColumnName])) {
                            $dataToStore[$id]['__children__'][$mainColumnName] = [];
                        }
                        if (!isset($dataToStore[$id]['__children__'][$mainColumnName][$childTable])) {
                            $dataToStore[$id]['__children__'][$mainColumnName][$childTable] = [];
                        }
                        $childStructure = $this->prepareChildStructure(
                            $childTable,
                            $childConfiguration,
                            $id,
                            $record,
                            $childColumnConfiguration['sorting'] ?? []
                        );
                        $dataToStore[$id]['__children__'][$mainColumnName][$childTable][key($childStructure)] = current(
                            $childStructure
                        );
                    }
                    // Gather data needed for deletion of no longer extant children later on (only for non-new records)
                    if (strpos((string)$id, 'NEW') === false) {
                        $this->assembleChildrenDeletionInformation(
                            $mainColumnName,
                            $id,
                            $record,
                            $childTable,
                            $childConfiguration
                        );
                    }
                }
            }
        }

        // If there are any multiple values, loop again on all records and implode them
        if ($countDenormalizedColumns > 0) {
            foreach ($dataToStore as $id => $data) {
                foreach ($denormalizedColumns as $name) {
                    // Using the first entry, check if the multiple values are an array
                    // If yes, perform sorting and extract the values
                    if (is_array($multipleValues[$id][$name][0])) {
                        usort(
                            $multipleValues[$id][$name],
                            function ($a, $b) {
                                return strnatcasecmp($a['sorting'], $b['sorting']);
                            }
                        );
                        $values = [];
                        foreach ($multipleValues[$id][$name] as $multipleValue) {
                            $values[] = $multipleValue['value'];
                        }
                        // Otherwise use the values as is
                    } else {
                        $values = $multipleValues[$id][$name];
                    }
                    // Extract the values and implode them
                    $dataToStore[$id][$name] = implode(',', array_unique($values));
                }
            }
        }

        // Store the existing records that were not handled for later deletion
        $this->mainRecordsToDelete = array_diff($existingUids, $handledUids);

        // Review all children records for updates and deletions
        return $this->reviewChildRecords($dataToStore);
    }

    /**
     * Prepares the data structure for an IRRE child record.
     *
     * @param string $childTable Name of targeted child table
     * @param array $childConfiguration Configuration for the child record fields
     * @param mixed $parentId Id of the parent record
     * @param array $parentData Data of the parent record
     * @param array $sortingInformation Child sorting information (if any)
     * @return array[]
     */
    public function prepareChildStructure(string $childTable, array $childConfiguration, $parentId, array $parentData, array $sortingInformation): array
    {
        // NOTE: all child records are assembled here as if they were new. They are filtered later on.
        $temporaryKey = $this->importer->getTemporaryKeyRepository()->generateTemporaryKey();
        $childStructure = [
            $temporaryKey => []
        ];
        foreach ($childConfiguration as $name => $configuration) {
            // If it is a value, use it as is
            if (isset($configuration['value'])) {
                $childStructure[$temporaryKey][$name] = $configuration['value'];
                // If it is a field, get the value from the field, if defined
                // (if it's the special value "__parent.id__", use the parent record's id)
            } elseif (isset($configuration['field'])) {
                if ($configuration['field'] === '__parent.id__') {
                    $childStructure[$temporaryKey][$name] = $parentId;
                } elseif (isset($parentData[$configuration['field']])) {
                    $childStructure[$temporaryKey][$name] = $parentData[$configuration['field']];
                }
            }
            // Store the sorting information (used at a later point)
            if (count($sortingInformation)) {
                $this->childrenSortingInformation->addSortingInformation(
                    $childTable,
                    $temporaryKey,
                    $sortingInformation['target'],
                    $parentData[$sortingInformation['source']] ?? 0
                );
            }
        }
        return $childStructure;
    }

    /**
     * Assembles a set of reference values for children deletion for each parent record.
     *
     * NOTE: this information exists at parent-level, i.e. there's one entry per parent record, not per child record.
     *
     * @param string $parentColumn
     * @param int $parentId
     * @param array $parentData
     * @param string $childTable
     * @param array $childConfiguration
     * @return void
     */
    public function assembleChildrenDeletionInformation(string $parentColumn, int $parentId, array $parentData, string $childTable, array $childConfiguration): void
    {
        // Ensure proper initialization of reference array
        if (!isset($this->childrenReferenceValues[$parentId])) {
            $this->childrenReferenceValues[$parentId] = [];
        }
        if (!isset($this->childrenReferenceValues[$parentId][$parentColumn])) {
            $this->childrenReferenceValues[$parentId][$parentColumn] = [];
        }
        $this->childrenReferenceValues[$parentId][$parentColumn][$childTable] = [];
        // Assemble the reference values
        foreach ($childConfiguration as $name => $configuration) {
            if (in_array($name, $this->childColumns[$parentColumn]['controlColumnsForDelete'], true)) {
                // If it is a value, use it as is
                if (isset($configuration['value'])) {
                    $this->childrenReferenceValues[$parentId][$parentColumn][$childTable][$name] = $configuration['value'];

                // If it is a field, get the value from the field, if defined
                // (if it's the special value "__parent.id__", use the parent record's id)
                } elseif (isset($configuration['field'])) {
                    if ($configuration['field'] === '__parent.id__') {
                        $this->childrenReferenceValues[$parentId][$parentColumn][$childTable][$name] = $parentId;
                    } elseif (isset($parentData[$configuration['field']])) {
                        $this->childrenReferenceValues[$parentId][$parentColumn][$childTable][$name] = $parentData[$configuration['field']];
                    }
                }
            }
        }
    }

    /**
     * Goes through all children records (if any) and checks which are already existing and which should
     * be deleted.
     *
     * @param array $dataToStore Structured data for storage
     * @return array New structured data for storage (NOTE: child records to be deleted are stored separately)
     */
    public function reviewChildRecords(array $dataToStore): array
    {
        $newDataToStore = [];
        $childrenRepository = GeneralUtility::makeInstance(ChildrenRepository::class);

        // Loop on all the data to store
        foreach ($dataToStore as $id => $record) {
            // Act only if the parent record is not new (if it is new, all its child records will be new too)
            // and if it has child records. Otherwise, keep as is.
            $newDataToStore[$id] = $record;
            if (strpos((string)$id, 'NEW') === false) {
                $childrenColumnsChecked = [];
                if (isset($record['__children__']) && count($record['__children__']) > 0) {
                    // Reset the children list
                    $newDataToStore[$id]['__children__'] = [];
                    foreach ($record['__children__'] as $column => $childrenListForTable) {
                        foreach ($childrenListForTable as $childTable => $children) {
                            $iterator = 0;
                            $updatedChildren = [];
                            $allExistingChildren = [];
                            foreach ($children as $childId => $childData) {
                                // If no columns were defined for checking existing records, don't bother and consider the record to be new
                                if (count($this->childColumns[$column]['controlColumnsForUpdate']) === 0) {
                                    $newDataToStore[$id]['__children__'][$column][$childTable][$childId] = $childData;
                                } else {
                                    // If deletion is not disabled, grab existing records
                                    // (this is done once, but we need the data from one child record)
                                    if ($iterator === 0 && !$this->childColumns[$column]['disabledOperations']['delete'] && count(
                                            $this->childColumns[$column]['controlColumnsForDelete']
                                        ) > 0) {
                                        $allExistingChildren = $childrenRepository->findAllExistingRecords(
                                            $childTable,
                                            $this->childrenReferenceValues[$id][$column][$childTable]
                                        );
                                        // Mark the column as having been checked
                                        $childrenColumnsChecked[] = $column;
                                    }
                                    // Check if the child record already exists
                                    $controlValues = [];
                                    $hasAllControlValues = true;
                                    foreach ($this->childColumns[$column]['controlColumnsForUpdate'] as $name) {
                                        if (isset($childData[$name])) {
                                            $controlValues[$name] = $childData[$name];
                                        } else {
                                            // If a control value is missing, we can't check this record
                                            $hasAllControlValues = false;
                                            break;
                                        }
                                    }
                                    // If all control values were found, proceed with existence check
                                    if ($hasAllControlValues) {
                                        try {
                                            $existingId = $childrenRepository->findFirstExistingRecord(
                                                $childTable,
                                                $controlValues
                                            );
                                            $updatedChildren[] = $existingId;
                                            // If update operation is allowed, keep the child record but replace its "NEW***" temporary key
                                            // so that it gets updated
                                            if (!$this->childColumns[$column]['disabledOperations']['update']) {
                                                $newDataToStore[$id]['__children__'][$column][$childTable][$existingId] = $childData;
                                                // If children need to be sorted later on, replace existing ID in the prepared information
                                                if (isset($this->childColumns[$column]['sorting'])) {
                                                    $this->childrenSortingInformation->replaceId(
                                                        $childTable,
                                                        $childId,
                                                        $existingId
                                                    );
                                                }
                                            }
                                        } catch (\Exception $e) {
                                            // The relation does not exist yet, keep record as is, if insert operation is allowed
                                            if (!$this->childColumns[$column]['disabledOperations']['insert']) {
                                                $newDataToStore[$id]['__children__'][$column][$childTable][$childId] = $childData;
                                            }
                                        }
                                    // If a control value was missing, let the record be considered to be new (if inserts are allowed)
                                    // This will probably create a database error at a later point, but the user
                                    // will get to see it in the logs
                                    } elseif (!$this->childColumns[$column]['disabledOperations']['insert']) {
                                        $newDataToStore[$id]['__children__'][$column][$childTable][$childId] = $childData;
                                    }
                                }
                                $iterator++;
                            }
                            // Mark existing records that were not updated for deletion (if allowed)
                            if (!$this->childColumns[$column]['disabledOperations']['delete']) {
                                $childrenToDelete = array_diff($allExistingChildren, $updatedChildren);
                                if (!isset($this->childRecordsToDelete[$childTable])) {
                                    $this->childRecordsToDelete[$childTable] = [];
                                }
                                foreach ($childrenToDelete as $item) {
                                    $this->childRecordsToDelete[$childTable][] = $item;
                                }
                            }
                        }
                    }

                }
                // Loop on all children columns which were not checked in order to delete children that don't exist any more
                // Children columns are not checked either when the record has no children at all, or may have children in
                // some columns, but not all
                // NOTE: the code is a bit overreaching for now, since multiple children configuration are not supported yet
                $childrenControlValues = $this->childrenReferenceValues[$id] ?? [];
                foreach ($childrenControlValues as $column => $childrenControlValuesForColumn) {
                    if (!$this->childColumns[$column]['disabledOperations']['delete'] && !in_array($column, $childrenColumnsChecked, true)) {
                        foreach ($childrenControlValuesForColumn as $childTable => $childrenControlValuesForTable) {
                            $allExistingChildren = $childrenRepository->findAllExistingRecords(
                                $childTable,
                                $childrenControlValuesForTable
                            );
                            foreach ($allExistingChildren as $item) {
                                $this->childRecordsToDelete[$childTable][] = $item;
                            }
                        }
                    }
                }
            }
        }
        return $newDataToStore;
    }

    /**
     * Parses the column configuration and prepares various lists of properties for better performance.
     *
     * @param array $columnConfiguration External Import configuration for the columns
     */
    public function prepareStructuredInformation(array $columnConfiguration): void
    {
        foreach ($columnConfiguration as $columnName => $columnData) {
            if (array_key_exists('disabledOperations', $columnData)) {
                if (GeneralUtility::inList($columnData['disabledOperations'], 'insert')) {
                    $this->fieldsExcludedFromInserts[] = $columnName;
                }
                if (GeneralUtility::inList($columnData['disabledOperations'], 'update')) {
                    $this->fieldsExcludedFromUpdates[] = $columnName;
                }
            }
            if (array_key_exists('children', $columnData)) {
                $childrenData = $columnData['children'];
                // Reformat some information for easier access later
                $childrenData['controlColumnsForUpdate'] = isset($childrenData['controlColumnsForUpdate']) ?
                    GeneralUtility::trimExplode(',', $childrenData['controlColumnsForUpdate']) :
                    [];
                $childrenData['controlColumnsForDelete'] = isset($childrenData['controlColumnsForDelete']) ?
                    GeneralUtility::trimExplode(',', $childrenData['controlColumnsForDelete']) :
                    [];
                $disabledOperations = [
                    'insert' => false,
                    'update' => false,
                    'delete' => false
                ];
                if (isset($childrenData['disabledOperations'])) {
                    $operations = GeneralUtility::trimExplode(',', $childrenData['disabledOperations']);
                    foreach ($operations as $operation) {
                        $disabledOperations[$operation] = true;
                    }
                }
                $childrenData['disabledOperations'] = $disabledOperations;
                if (array_key_exists('sorting', $columnData)) {
                    $childrenData['sorting'] = $columnData['sorting'];
                }
                // Store the updated information
                $this->childColumns[$columnName] = $childrenData;
            }
            $this->hasChildColumns = count($this->childColumns) > 0;
            if (array_key_exists('substructureFields', $columnData)) {
                $this->substructureFields[$columnName] = array_keys($columnData['substructureFields']);
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
    protected function reportTceErrors(array $errorLog): void
    {
        if (count($errorLog) > 0) {
            // If there are errors, get these messages from the sys_log table (assuming they are the latest ones)
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_log');
            $result = $queryBuilder->select('*')
                ->from('sys_log')
                ->where(
                    $queryBuilder->expr()->eq(
                        'tablename',
                        $queryBuilder->createNamedParameter(
                            $this->importer->getExternalConfiguration()->getTable()
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
            if ($result) {
                $iterator = CompatibilityUtility::resultIteratorFactory();
                while ($row = $iterator->next($result)) {
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
                            htmlspecialchars((string)($data[0] ?? '')),
                            htmlspecialchars((string)($data[1] ?? '')),
                            htmlspecialchars((string)($data[2] ?? '')),
                            htmlspecialchars((string)($data[3] ?? '')),
                            htmlspecialchars((string)($data[4] ?? ''))
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
            if (strpos((string)$fields['pid'], 'NEW') === false) {
                $levelPages[] = (strpos((string)$id, 'NEW') === 0) ? $id : (int)$id;
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
            $pid = (strpos((string)$fields['pid'], 'NEW') === 0) ? $fields['pid'] : (int)$fields['pid'];
            if (in_array($pid, $levelPages, true)) {
                $pagesForLevel[$id] = $fields;
                $nextLevelPages[] = (strpos((string)$id, 'NEW') === 0) ? $id : (int)$id;
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
    public function updateSlugs(string $table, array $uids): void
    {
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
            [
                $e->getTraceAsString()
            ]
        );
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