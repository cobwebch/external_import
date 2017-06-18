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

use Cobweb\ExternalImport\DataHandlerInterface;
use Cobweb\ExternalImport\Domain\Repository\ConfigurationRepository;
use Cobweb\ExternalImport\Exception\ConfigurationNotFoundException;
use Cobweb\ExternalImport\Importer;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * This class parses the "ctrl" part of an External Import configuration
 * and reports errors and other glitches.
 *
 * NOTE: this is not a strict Extbase Validator.
 *
 * @package Cobweb\ExternalImport\Validator
 */
class ControlConfigurationValidator extends AbstractConfigurationValidator
{
    /**
     * Validates the given configuration.
     *
     * @param string $table Name of the table to which the configuration applies
     * @param array $ctrlConfiguration "ctrl" configuration to check
     * @param array $columnConfiguration Column configuration to check (unused when checking a "ctrl" configuration)
     * @return bool
     */
    public function isValid($table, $ctrlConfiguration, $columnConfiguration = null)
    {
        $this->table = $table;

        // Add notices about renamed properties
        $this->checkForRenamedProperties($ctrlConfiguration);

        // Validate all properties on which conditions apply
        $this->validateDataProperty($ctrlConfiguration['data']);
        $this->validateConnectorProperty($ctrlConfiguration['connector']);
        $this->validateDataHandlerProperty($ctrlConfiguration['dataHandler']);
        $this->validateReferenceUidProperty($ctrlConfiguration['referenceUid']);
        $this->validatePidProperty($ctrlConfiguration['pid']);
        $this->validateUseColumnIndexProperty($ctrlConfiguration['useColumnIndex']);

        // Validate properties for pull-only configurations
        if (!empty($ctrlConfiguration['connector'])) {
            $this->validatePriorityProperty($ctrlConfiguration['priority']);
        }

        // Validate properties specific to the "xml"-type data
        if ($ctrlConfiguration['data'] === 'xml') {
            $this->validateNodetypeProperty($ctrlConfiguration['nodetype']);
        }
        // Return the global validation result
        return parent::isValid($table, $ctrlConfiguration, $columnConfiguration);
    }

    /**
     * Checks for usage of old properties and issues notice for each one found.
     *
     * @param array $configuration The configuration to check
     */
    public function checkForRenamedProperties($configuration)
    {
        foreach (ConfigurationRepository::$renamedControlProperties as $oldName => $newName) {
            // If the old property name is used, issue a notice about renaming
            // NOTE: this is done *before* the rest of the validation. If the property value is wrong,
            // the validation error will override the renaming notice, since there can be only one
            // validation result per property.
            if (array_key_exists($oldName, $configuration)) {
                $this->addResult(
                        $newName,
                        LocalizationUtility::translate(
                                'LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:renamedProperty',
                                'external_import',
                                array(
                                        $oldName,
                                        $newName
                                )
                        ),
                        FlashMessage::NOTICE
                );
            }
        }
    }

    /**
     * Validates the "data" property.
     *
     * @param string $property Property value
     */
    public function validateDataProperty($property)
    {
        if (empty($property)) {
            $this->addResult(
                    'data',
                    LocalizationUtility::translate(
                            'LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:missingDataProperty',
                            'external_import'
                    ),
                    FlashMessage::ERROR
            );
        } else {
            if ($property !== 'array' && $property !== 'xml') {
                $this->addResult(
                        'data',
                        LocalizationUtility::translate(
                                'LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:invalidDataProperty',
                                'external_import'
                        ),
                        FlashMessage::ERROR
                );
            }
        }
    }

    /**
     * Validates the "connector" property.
     *
     * NOTE: an empty connector is okay, it just means data is being pushed instead of pulled
     * (of course, this may be wrong, but we have no way to guess the user's intent ;-) ).
     *
     * @param string $property Property value
     */
    public function validateConnectorProperty($property)
    {
        if (!empty($property)) {
            $services = ExtensionManagementUtility::findService(
                    'connector',
                    $property
            );
            if ($services === false) {
                $this->addResult(
                        'connector',
                        LocalizationUtility::translate(
                                'LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:unavailableConnector',
                                'external_import'
                        ),
                        FlashMessage::ERROR
                );
            }
        }
    }

    /**
     * Validates the "dataHandler" property.
     *
     * @param string $property Property value
     */
    public function validateDataHandlerProperty($property)
    {
        if (!empty($property)) {
            if (class_exists($property)) {
                try {
                    $dataHandler = GeneralUtility::makeInstance($property);
                    if (!($dataHandler instanceof DataHandlerInterface)) {
                        $this->addResult(
                                'dataHandler',
                                LocalizationUtility::translate(
                                        'LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:dataHandlerInterfaceIssue',
                                        'external_import'
                                ),
                                FlashMessage::NOTICE
                        );
                    }
                }
                catch (\Exception $e) {
                    $this->addResult(
                            'dataHandler',
                            LocalizationUtility::translate(
                                    'LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:dataHandlerNoInstance',
                                    'external_import'
                            ),
                            FlashMessage::NOTICE
                    );
                }
            } else {
                $this->addResult(
                        'dataHandler',
                        LocalizationUtility::translate(
                                'LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:dataHandlerNotFound',
                                'external_import'
                        ),
                        FlashMessage::NOTICE
                );
            }
        }
    }

    /**
     * Validates the "nodetype" property.
     *
     * @param string $property Property value
     */
    public function validateNodetypeProperty($property)
    {
        if (empty($property)) {
            $this->addResult(
                    'nodetype',
                    LocalizationUtility::translate(
                            'LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:missingNodetypeProperty',
                            'external_import'
                    ),
                    FlashMessage::ERROR
            );
        }
    }

    /**
     * Validates the "referenceUid" property.
     *
     * @param string $property Property value
     */
    public function validateReferenceUidProperty($property)
    {
        if (empty($property)) {
            $this->addResult(
                    'referenceUid',
                    LocalizationUtility::translate(
                            'LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:missingReferenceUidProperty',
                            'external_import'
                    ),
                    FlashMessage::ERROR
            );
        }
    }

    /**
     * Validates the "priority" property.
     *
     * @param string $property Property value
     */
    public function validatePriorityProperty($property)
    {
        if (empty($property)) {
            $this->addResult(
                    'priority',
                    LocalizationUtility::translate(
                            'LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:defaultPriorityValue',
                            'external_import',
                            array(
                                    Importer::DEFAULT_PRIORITY
                            )
                    ),
                    FlashMessage::NOTICE
            );
        }
    }

    /**
     * Validates the "pid" property.
     *
     * @param string $property Property value
     */
    public function validatePidProperty($property)
    {
        // TCA property rootLevel defaults to 0
        $rootLevelFlag = isset($GLOBALS['TCA'][$this->table]['ctrl']['rootLevel']) ? $GLOBALS['TCA'][$this->table]['ctrl']['rootLevel'] : 0;
        // If the pid is 0, data will be stored on root page.
        if ($property === 0) {
            // Table is allowed on root page, just issue notice to make sure pid was not forgotten
            if ($rootLevelFlag === -1 || $rootLevelFlag === 1) {
                $this->addResult(
                        'pid',
                        LocalizationUtility::translate(
                                'LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:pidNotSetStoreRootPage',
                                'external_import'
                        ),
                        FlashMessage::NOTICE
                );
            } else {
                // Records for current table are not allowed on root page
                $this->addResult(
                        'pid',
                        LocalizationUtility::translate(
                                'LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:pidNotSetStoreRootPageNotAllowed',
                                'external_import',
                                array(
                                        $this->table
                                )
                        ),
                        FlashMessage::ERROR
                );
            }
        } elseif ($property < 0) {
            // Negative pid is invalid
            $this->addResult(
                    'pid',
                    LocalizationUtility::translate(
                            'LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:negativePidProperty',
                            'external_import'
                    ),
                    FlashMessage::ERROR
            );
        } else {
            // Pid is a positive integer, but records for current table can only be stored on root page
            if ($rootLevelFlag === 1) {
                $this->addResult(
                        'pid',
                        LocalizationUtility::translate(
                                'LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:invalidPidPropertyOnlyRoot',
                                'external_import',
                                array(
                                        $this->table
                                )
                        ),
                        FlashMessage::ERROR
                );
            }
        }
    }

    /**
     * Validates the "useColumnIndex" property.
     *
     * @param string $property Property value
     */
    public function validateUseColumnIndexProperty($property)
    {
        // If useColumnIndex is defined, it needs to match an existing index for the same table
        // If there's no column configuration using that index, issue an error
        if ($property !== null) {
            $configurationRepository = GeneralUtility::makeInstance(ConfigurationRepository::class);
            try {
                $configurationRepository->findColumnsByTableAndIndex(
                        $this->table,
                        $property
                );
            }
            catch (ConfigurationNotFoundException $e) {
                $this->addResult(
                        'useColumnIndex',
                        LocalizationUtility::translate(
                                'LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:wrongUseColumnIndexProperty',
                                'external_import',
                                array(
                                        $property,
                                        $this->table
                                )
                        ),
                        FlashMessage::ERROR
                );
            }
        }
    }
}