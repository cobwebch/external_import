<?php

declare(strict_types=1);

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
use Cobweb\Svconnector\Registry\ConnectorRegistry;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class parses the general part of an External Import configuration
 * and reports errors and other glitches.
 *
 * NOTE: this is not a strict Extbase Validator.
 */
class GeneralConfigurationValidator
{
    /**
     * @var string Name of the table for which the configuration is checked
     */
    protected string $table;

    protected ValidationResult $results;
    protected StepUtility $stepUtility;
    protected ConnectorRegistry $connectorRegistry;

    public function __construct(ValidationResult $result, StepUtility $stepUtility, ConnectorRegistry $connectorRegistry)
    {
        $this->results = $result;
        $this->stepUtility = $stepUtility;
        $this->connectorRegistry = $connectorRegistry;
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
        $this->validateDataProperty(
            array_key_exists('data', $generalConfiguration) ? (string)$generalConfiguration['data'] : ''
        );
        $connector = array_key_exists('connector', $generalConfiguration) ? (string)$generalConfiguration['connector'] : '';
        $this->validateConnectorProperty($connector);
        $this->validateDataHandlerProperty(
            array_key_exists('dataHandler', $generalConfiguration) ? (string)$generalConfiguration['dataHandler'] : ''
        );
        $this->validateReferenceUidProperty(
            array_key_exists('referenceUid', $generalConfiguration) ? (string)$generalConfiguration['referenceUid'] : ''
        );
        $this->validatePidProperty($configuration->getStoragePid());
        $this->validateUseColumnIndexProperty(
            $generalConfiguration['useColumnIndex'] ?? null,
            $configuration->getColumnConfiguration()
        );
        $this->validateColumnsOrderProperty(
            $generalConfiguration['columnsOrder'] ?? '',
            $configuration->getColumnConfiguration()
        );
        $this->validateCustomStepsProperty(
            $generalConfiguration['customSteps'] ?? null,
            $generalConfiguration
        );
        $this->validateGroupsProperty(
            $generalConfiguration['groups'] ?? null,
            $generalConfiguration['group'] ?? null,
        );

        // Validate properties for pull-only configurations
        if (!empty($connector)) {
            $this->validatePriorityProperty(
                array_key_exists('priority', $generalConfiguration) ? (int)$generalConfiguration['priority'] : 0
            );
            $this->validateConnectorConfigurationProperty(
                $connector,
                $generalConfiguration['parameters'] ?? []
            );
        }

        // Validate properties specific to the "xml"-type data
        if (($generalConfiguration['data'] ?? '') === 'xml') {
            $this->validateNodeProperty(
                $generalConfiguration['nodetype'] ?? '',
                $generalConfiguration['nodepath'] ?? ''
            );
        }
        // Return the global validation result
        // Consider that the configuration does not validate if there's at least one error or one warning
        return $this->results->countForSeverity(ContextualFeedbackSeverity::ERROR) +
            $this->results->countForSeverity(ContextualFeedbackSeverity::WARNING) === 0;
    }

    /**
     * Validates the "data" property.
     *
     * @param string $property Property value
     */
    public function validateDataProperty(string $property): void
    {
        if (empty($property)) {
            $this->results->add(
                'data',
                $this->getLanguageService()->sL(
                    'LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:missingDataProperty'
                ),
                ContextualFeedbackSeverity::ERROR
            );
        } elseif ($property !== 'array' && $property !== 'xml') {
            $this->results->add(
                'data',
                $this->getLanguageService()->sL(
                    'LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:invalidDataProperty'
                ),
                ContextualFeedbackSeverity::ERROR
            );
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
    public function validateConnectorProperty(string $property): void
    {
        if (!empty($property)) {
            try {
                // NOTE: we do not check connector availability as this is a runtime issue. Here we just check the configuration.
                $this->connectorRegistry->getServiceForType(
                    $property
                );
            } catch (\Exception $e) {
                $this->results->add(
                    'connector',
                    $this->getLanguageService()->sL(
                        'LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:unavailableConnector'
                    ),
                    ContextualFeedbackSeverity::ERROR
                );
            }
        }
    }

    /**
     * Validates the "parameters" property.
     *
     * @param string $connector Type of connector
     * @param array $property Parameters for the connector
     * @see \Cobweb\ExternalImport\Validator\GeneralConfigurationValidator::validateConnectorProperty
     */
    public function validateConnectorConfigurationProperty(string $connector, array $property): void
    {
        try {
            $connectorService = $this->connectorRegistry->getServiceForType($connector, $property);
        } catch (\Exception $e) {
            // NOTE: we do not report if connector was not found, because this is the task of validateConnectorProperty()
            return;
        }
        $results = $connectorService->checkConfiguration();
        foreach ($results as $severity => $messages) {
            foreach ($messages as $message) {
                $this->results->add(
                    'parameters',
                    $message,
                    ContextualFeedbackSeverity::from($severity)
                );
            }
        }
    }

    /**
     * Validates the "dataHandler" property.
     *
     * @param string|null $property Property value
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
                            $this->getLanguageService()->sL(
                                'LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:dataHandlerInterfaceIssue'
                            ),
                            ContextualFeedbackSeverity::NOTICE
                        );
                    }
                } catch (\Exception $e) {
                    $this->results->add(
                        'dataHandler',
                        $this->getLanguageService()->sL(
                            'LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:dataHandlerNoInstance'
                        ),
                        ContextualFeedbackSeverity::NOTICE
                    );
                }
            } else {
                $this->results->add(
                    'dataHandler',
                    $this->getLanguageService()->sL(
                        'LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:dataHandlerNotFound'
                    ),
                    ContextualFeedbackSeverity::NOTICE
                );
            }
        }
    }

    /**
     * Validates that there's either "nodetype" or "nodepath" property.
     *
     * @param string $nodetype Nodetype property value
     * @param string $nodepath Nodepath property value
     */
    public function validateNodeProperty(string $nodetype = '', string $nodepath = ''): void
    {
        if (empty($nodetype) && empty($nodepath)) {
            $this->results->add(
                'nodetype',
                $this->getLanguageService()->sL(
                    'LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:missingNodeProperty',
                ),
                ContextualFeedbackSeverity::ERROR
            );
        }
    }

    /**
     * Validates the "referenceUid" property.
     *
     * @param string $property Property value
     */
    public function validateReferenceUidProperty(string $property): void
    {
        if (empty($property)) {
            $this->results->add(
                'referenceUid',
                $this->getLanguageService()->sL(
                    'LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:missingReferenceUidProperty'
                ),
                ContextualFeedbackSeverity::ERROR
            );
        }
    }

    /**
     * Validates the "priority" property.
     *
     * @param int $property Property value
     */
    public function validatePriorityProperty(int $property): void
    {
        if ($property === 0) {
            $this->results->add(
                'priority',
                sprintf(
                    $this->getLanguageService()->sL('LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:defaultPriorityValue'),
                    Importer::DEFAULT_PRIORITY,
                ),
                ContextualFeedbackSeverity::NOTICE
            );
        }
    }

    /**
     * Validates the "pid" property.
     *
     * @param mixed $property Property value
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
                    $this->getLanguageService()->sL(
                        'LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:pidNotSetStoreRootPage',
                    ),
                    ContextualFeedbackSeverity::NOTICE
                );
            } else {
                // Records for current table are not allowed on root page
                $this->results->add(
                    'pid',
                    sprintf(
                        $this->getLanguageService()->sL('LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:pidNotSetStoreRootPageNotAllowed'),
                        $this->table,
                    ),
                    ContextualFeedbackSeverity::ERROR
                );
            }
        } elseif ($property < 0) {
            // Negative pid is invalid
            $this->results->add(
                'pid',
                $this->getLanguageService()->sL(
                    'LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:negativePidProperty',
                ),
                ContextualFeedbackSeverity::ERROR
            );
            // Pid is a positive integer, but records for current table can only be stored on root page
        } elseif ($rootLevelFlag === 1) {
            $this->results->add(
                'pid',
                sprintf(
                    $this->getLanguageService()->sL('LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:invalidPidPropertyOnlyRoot'),
                    $this->table,
                ),
                ContextualFeedbackSeverity::ERROR
            );
        }
    }

    /**
     * Validates the "useColumnIndex" property.
     *
     * @param mixed $property Property value
     * @param array $columns List of column configurations
     */
    public function validateUseColumnIndexProperty($property, array $columns): void
    {
        // If useColumnIndex is defined, it needs to match an existing index for the same table
        // If there's no column configuration using that index, issue an error
        if ($property !== null && count($columns) === 0) {
            $this->results->add(
                'useColumnIndex',
                sprintf(
                    $this->getLanguageService()->sL('LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:wrongUseColumnIndexProperty'),
                    $property,
                    $this->table,
                ),
                ContextualFeedbackSeverity::ERROR
            );
        }
    }

    /**
     * Validates the "columnsOrder" property.
     *
     * @param mixed $property Property value
     * @param array $columns List of column configurations
     */
    public function validateColumnsOrderProperty(string $property, array $columns): void
    {
        $columnKeys = GeneralUtility::trimExplode(',', $property, true);
        // Check if some columns are duplicated in the configuration
        $filteredColumnKeys = array_unique($columnKeys);
        $difference = array_diff_assoc($columnKeys, $filteredColumnKeys);
        if (count($difference) > 0) {
            $this->results->add(
                'columnsOrder',
                sprintf(
                    $this->getLanguageService()->sL('LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:duplicateKeysInColumnsOrderProperty'),
                    implode(', ', $difference),
                ),
                ContextualFeedbackSeverity::NOTICE
            );
        }
        // Check if some columns do not exist in the configuration
        $invalidColumns = [];
        foreach ($columnKeys as $key) {
            if (!array_key_exists($key, $columns)) {
                $invalidColumns[] = $key;
            }
        }
        if (count($invalidColumns) > 0) {
            $this->results->add(
                'columnsOrder',
                sprintf(
                    $this->getLanguageService()->sL('LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:invalidColumnsInColumnsOrderProperty'),
                    implode(', ', $invalidColumns),
                ),
                ContextualFeedbackSeverity::NOTICE
            );
        }
    }

    /**
     * Validates the "customSteps" property.
     *
     * @param array|null $property Property value
     * @param array $ctrlConfiguration Full "ctrl" configuration
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
                    $this->stepUtility->validateCustomStepConfiguration($steps, $customStepConfiguration);
                    $steps = $this->stepUtility->insertStep($steps, $customStepConfiguration);
                } catch (InvalidCustomStepConfiguration $e) {
                    $this->results->add(
                        'customSteps',
                        sprintf(
                            $this->getLanguageService()->sL('LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:invalidCustomStepsProperty'),
                            $e->getMessage(),
                            $e->getCode(),
                        ),
                        ContextualFeedbackSeverity::NOTICE
                    );
                    break;
                }
            }
        }
    }

    /**
     * Validate the "groups" property.
     * Send deprecation notice about the old "group" property, until it is dropped
     *
     * @param array|null $property The "groups" property
     * @param string|null $oldProperty The "group" property
     */
    public function validateGroupsProperty(?array $property = null, ?string $oldProperty = null): void
    {
        // TODO: drop checking deprecated "group" property in the next major version
        if (!empty($oldProperty)) {
            $this->results->add(
                'groups',
                $this->getLanguageService()->sL('LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:deprecatedGroupProperty'),
                ContextualFeedbackSeverity::NOTICE
            );
        }
        if ($property !== null && !is_array($property)) {
            $this->results->add(
                'groups',
                $this->getLanguageService()->sL('LLL:EXT:external_import/Resources/Private/Language/Validator.xlf:invalidGroupsProperty'),
                ContextualFeedbackSeverity::ERROR
            );
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

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
