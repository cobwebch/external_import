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
use Cobweb\ExternalImport\Domain\Model\Configuration;
use Cobweb\ExternalImport\Exception\InvalidCustomStepConfiguration;
use Cobweb\ExternalImport\Importer;
use Cobweb\ExternalImport\Utility\StepUtility;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * This class parses the general part of an External Import configuration
 * and reports errors and other glitches.
 *
 * NOTE: this is not a strict Extbase Validator.
 *
 * @package Cobweb\ExternalImport\Validator
 */
class GeneralConfigurationValidator
{
    /**
     * @var string Name of the table for which the configuration is checked
     */
    protected $table;

    /**
     * @var ValidationResult
     */
    protected $results;

    /**
     * @var StepUtility
     */
    protected $stepUtility;

    public function injectValidationResult(ValidationResult $result): void
    {
        $this->results = $result;
    }

    public function injectStepUtility(StepUtility $stepUtility): void
    {
        $this->stepUtility = $stepUtility;
    }

    /**
     * Validates the given configuration.
     *
     * @param Configuration $configuration Configuration object to check
     * @return bool
     */
    public function isValid(Configuration $configuration): bool
    {
        $this->table = $configuration->getTable();
        $generalConfiguration = $configuration->getGeneralConfiguration();

        // Validate all properties on which conditions apply
        $this->validateDataProperty($generalConfiguration['data']);
        $this->validateConnectorProperty($generalConfiguration['connector']);
        $this->validateDataHandlerProperty($generalConfiguration['dataHandler']);
        $this->validateReferenceUidProperty($generalConfiguration['referenceUid']);
        $this->validatePidProperty($configuration->getStoragePid());
        $this->validateUseColumnIndexProperty(
                $generalConfiguration['useColumnIndex'],
                $configuration->getColumnConfiguration()
        );
        $this->validateCustomStepsProperty(
                $generalConfiguration['customSteps'],
                $generalConfiguration
        );
        $this->validateAdditionalFieldsProperty(
                $configuration->isObsoleteAdditionalFieldsConfiguration(),
                $configuration->getCountAdditionalFields()
        );

        // Validate properties for pull-only configurations
        if (!empty($generalConfiguration['connector'])) {
            $this->validatePriorityProperty($generalConfiguration['priority']);
            $this->validateConnectorConfigurationProperty(
                    $generalConfiguration['connector'],
                    $generalConfiguration['parameters']
            );
        }

        // Validate properties specific to the "xml"-type data
        if ($generalConfiguration['data'] === 'xml') {
            $this->validateNodeProperty(
                    $generalConfiguration['nodetype'],
                    $generalConfiguration['nodepath']
            );
        }
        // Return the global validation result
        // Consider that the configuration does not validate if there's at least one error or one warning
        return $this->results->countForSeverity(AbstractMessage::ERROR) + $this->results->countForSeverity(AbstractMessage::WARNING) === 0;
    }

    /**
     * Validates the "data" property.
     *
     * @param string|null $property Property value
     * @return void
     */
    public function validateDataProperty(?string $property): void
    {
        if (empty($property)) {
            $this->results->add(
                    'data',
                    LocalizationUtility::translate(
                            'LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:missingDataProperty'
                    ),
                    AbstractMessage::ERROR
            );
        } elseif ($property !== 'array' && $property !== 'xml') {
            $this->results->add(
                    'data',
                    LocalizationUtility::translate(
                            'LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:invalidDataProperty'
                    ),
                    AbstractMessage::ERROR
            );
        }
    }

    /**
     * Validates the "connector" property.
     *
     * NOTE: an empty connector is okay, it just means data is being pushed instead of pulled
     * (of course, this may be wrong, but we have no way to guess the user's intent ;-) ).
     *
     * @param string|null $property Property value
     * @return void
     */
    public function validateConnectorProperty(?string $property): void
    {
        if (!empty($property)) {
            $services = ExtensionManagementUtility::findService(
                    'connector',
                    $property
            );
            if ($services === false) {
                $this->results->add(
                        'connector',
                        LocalizationUtility::translate(
                                'LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:unavailableConnector'
                        ),
                        AbstractMessage::ERROR
                );
            }
        }
    }

    /**
     * Validates the "parameters" property.
     *
     * @param string $connector Type of connector
     * @param array $property Parameters for the connector
     * @return void
     * @see \Cobweb\ExternalImport\Validator\GeneralConfigurationValidator::validateConnectorProperty
     */
    public function validateConnectorConfigurationProperty(string $connector, $property): void
    {
        $connectorService = GeneralUtility::makeInstanceService(
                'connector',
                $connector
        );
        // Proceed only if connector was found
        // NOTE: we do not report if connector was not found, because this is the task of validateConnectorProperty()
        if ($connectorService !== false) {
            $results = $connectorService->checkConfiguration($property);
            foreach ($results as $severity => $messages) {
                foreach ($messages as $message) {
                    $this->results->add(
                            'parameters',
                            $message,
                            $severity
                    );
                }
            }
        }
    }

    /**
     * Validates the "dataHandler" property.
     *
     * @param string|null $property Property value
     * @return void
     */
    public function validateDataHandlerProperty(?string $property): void
    {
        if (!empty($property)) {
            if (class_exists($property)) {
                try {
                    $dataHandler = GeneralUtility::makeInstance($property);
                    if (!($dataHandler instanceof DataHandlerInterface)) {
                        $this->results->add(
                                'dataHandler',
                                LocalizationUtility::translate(
                                        'LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:dataHandlerInterfaceIssue'
                                ),
                                AbstractMessage::NOTICE
                        );
                    }
                }
                catch (\Exception $e) {
                    $this->results->add(
                            'dataHandler',
                            LocalizationUtility::translate(
                                    'LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:dataHandlerNoInstance'
                            ),
                            AbstractMessage::NOTICE
                    );
                }
            } else {
                $this->results->add(
                        'dataHandler',
                        LocalizationUtility::translate(
                                'LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:dataHandlerNotFound'
                        ),
                        AbstractMessage::NOTICE
                );
            }
        }
    }

    /**
     * Validates that there's either "nodetype" or "nodepath" property.
     *
     * @param string $nodetype Nodetype property value
     * @param string $nodepath Nodepath property value
     * @return void
     */
    public function validateNodeProperty($nodetype = '', $nodepath = ''): void
    {
        if (empty($nodetype) && empty($nodepath)) {
            $this->results->add(
                    'nodetype',
                    LocalizationUtility::translate(
                            'LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:missingNodeProperty',
                            'external_import'
                    ),
                    AbstractMessage::ERROR
            );
        }
    }

    /**
     * Validates the "referenceUid" property.
     *
     * @param string|null $property Property value
     * @return void
     */
    public function validateReferenceUidProperty(?string $property): void
    {
        if (empty($property)) {
            $this->results->add(
                    'referenceUid',
                    LocalizationUtility::translate(
                            'LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:missingReferenceUidProperty'
                    ),
                    AbstractMessage::ERROR
            );
        }
    }

    /**
     * Validates the "priority" property.
     *
     * @param mixed $property Property value
     * @return void
     */
    public function validatePriorityProperty($property): void
    {
        if (empty($property)) {
            $this->results->add(
                    'priority',
                    LocalizationUtility::translate(
                            'LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:defaultPriorityValue',
                            null,
                            [
                                    Importer::DEFAULT_PRIORITY
                            ]
                    ),
                    AbstractMessage::NOTICE
            );
        }
    }

    /**
     * Validates the "pid" property.
     *
     * @param mixed $property Property value
     * @return void
     */
    public function validatePidProperty($property): void
    {
        $property = (int)$property;
        // TCA property rootLevel defaults to 0
        $rootLevelFlag = $GLOBALS['TCA'][$this->table]['ctrl']['rootLevel'] ?? 0;
        // If the pid is 0, data will be stored on root page.
        if ($property === 0) {
            // Table is allowed on root page, just issue notice to make sure pid was not forgotten
            // NOTE: "rootLevel" is not supposed to be "true", but errors happen and we allow for these.
            if ($rootLevelFlag === -1 || $rootLevelFlag === 1 || $rootLevelFlag) {
                $this->results->add(
                        'pid',
                        LocalizationUtility::translate(
                                'LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:pidNotSetStoreRootPage',
                                'external_import'
                        ),
                        AbstractMessage::NOTICE
                );
            } else {
                // Records for current table are not allowed on root page
                $this->results->add(
                        'pid',
                        LocalizationUtility::translate(
                                'LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:pidNotSetStoreRootPageNotAllowed',
                                null,
                                [
                                        $this->table
                                ]
                        ),
                        AbstractMessage::ERROR
                );
            }
        } elseif ($property < 0) {
            // Negative pid is invalid
            $this->results->add(
                    'pid',
                    LocalizationUtility::translate(
                            'LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:negativePidProperty',
                            'external_import'
                    ),
                    AbstractMessage::ERROR
            );
        } else {
            // Pid is a positive integer, but records for current table can only be stored on root page
            if ($rootLevelFlag === 1) {
                $this->results->add(
                        'pid',
                        LocalizationUtility::translate(
                                'LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:invalidPidPropertyOnlyRoot',
                                null,
                                [
                                        $this->table
                                ]
                        ),
                        AbstractMessage::ERROR
                );
            }
        }
    }

    /**
     * Validates the "useColumnIndex" property.
     *
     * @param mixed $property Property value
     * @param array $columns List of column configurations
     * @return void
     */
    public function validateUseColumnIndexProperty($property, array $columns): void
    {
        // If useColumnIndex is defined, it needs to match an existing index for the same table
        // If there's no column configuration using that index, issue an error
        if ($property !== null && count($columns) === 0) {
            $this->results->add(
                    'useColumnIndex',
                    LocalizationUtility::translate(
                            'LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:wrongUseColumnIndexProperty',
                            null,
                            [
                                    $property,
                                    $this->table
                            ]
                    ),
                    AbstractMessage::ERROR
            );
        }
    }

    /**
     * Validates the "customSteps" property.
     *
     * @param array|null $property Property value
     * @param array $ctrlConfiguration Full "ctrl" configuration
     * @return void
     */
    public function validateCustomStepsProperty(?array $property, array $ctrlConfiguration): void
    {
        if ($property !== null && is_array($property) && count($property) > 0) {
            // Define the process default steps, depending on process type
            if (array_key_exists('connector', $ctrlConfiguration)) {
                $steps = Importer::SYNCHRONYZE_DATA_STEPS;
            } else {
                $steps = Importer::IMPORT_DATA_STEPS;
            }
            foreach ($property as $customStepConfiguration) {
                try {
                    $result = $this->stepUtility->validateCustomStepConfiguration($steps, $customStepConfiguration);
                }
                catch (InvalidCustomStepConfiguration $e) {
                    $this->results->add(
                            'customSteps',
                            LocalizationUtility::translate(
                                    'LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:invalidCustomStepsProperty',
                                    null,
                                    [
                                            $e->getMessage(),
                                            $e->getCode()
                                    ]
                            ),
                            AbstractMessage::NOTICE
                    );
                    break;
                }
            }
        }
    }

    /**
     * Validates the "additionalFields" property.
     *
     * Actually this just mentions an obsolete configuration and points to the Columns tab
     * for the new configuration.
     *
     * TODO: remove once backward-compatibility with comma-separated syntax is dropped
     *
     * @param bool $isObsolete
     * @param int $countAdditionalFields
     */
    public function validateAdditionalFieldsProperty(bool $isObsolete, int $countAdditionalFields): void
    {
        if ($isObsolete) {
            $this->results->add(
                    'additionalFields',
                    LocalizationUtility::translate(
                            'LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:obsoleteAdditionalFieldsProperty'
                    ),
                    AbstractMessage::NOTICE
            );
        } else {
            // If there are additional fields and the obsolete configuration is *not* used,
            // point the user to the "Columns" tab in the detail view to help them get used to
            // the new behavior.
            if ($countAdditionalFields > 0) {
                $this->results->add(
                        'additionalFields',
                        LocalizationUtility::translate(
                                'LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:additionalFieldsInColumTab'
                        ),
                        AbstractMessage::INFO
                );
            }
        }
    }

    /**
     * Returns all validation results.
     *
     * @return ValidationResult
     */
    public function getResults(): ValidationResult
    {
        return $this->results;
    }
}