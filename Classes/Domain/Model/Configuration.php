<?php
namespace Cobweb\ExternalImport\Domain\Model;

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
use Cobweb\ExternalImport\Utility\StepUtility;
use Cobweb\Svconnector\Service\ConnectorBase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Pseudo-domain model for a single External Import configuration.
 *
 * @package Cobweb\ExternalImport\Domain\Model
 */
class Configuration
{
    public const DO_NOT_SAVE_KEY = '_txexternalimport_doNotSave';

    /**
     * @var string Name of the table to which the configuration applies
     */
    protected $table;

    /**
     * @var int|string Index identifying the configuration for the given table
     */
    protected $index;

    /**
     * @var array General part of the External Import configuration
     */
    protected $generalConfiguration;

    /**
     * @var array External Import configuration for each column
     */
    protected $columnConfiguration;

    /**
     * @var bool Whether the general configuration is defined in an obsolete way ("ctrl" section) or not
     *
     * TODO: remove once backward-compatibility with "ctrl" section is dropped
     */
    protected $obsoleteGeneralConfiguration = false;

    /**
     * @var bool Whether the additional fields configuration is defined in an obsolete way (comma-separated list) or not
     *
     * TODO: remove once backward-compatibility with comma-separated syntax is dropped
     */
    protected $obsoleteAdditionalFieldsConfiguration = false;

    /**
     * @var int ID of storage page
     */
    protected $storagePid;

    /**
     * @var array List of fields that must be read from distant source, but will not be stored to DB
     */
    protected $additionalFields = [];

    /**
     * @var int Number of additional fields (cached to avoid counting too often)
     */
    protected $countAdditionalFields = 0;

    /**
     * @var array List of steps that the process will go through (depends on process type)
     */
    protected $steps = [];

    /**
     * @var array List of default steps for the process
     */
    protected $defaultSteps = [];

    /**
     * @var array List of all custom steps (valid or not)
     */
    protected $customSteps = [];

    /**
     * @var array List of parameters associated with custom steps (if any)
     */
    protected $stepParameters = [];

    /**
     * @var ConnectorBase Reference to the connector object
     */
    protected $connector;

    /**
     * @var StepUtility
     */
    protected $stepUtility;

    public function __toString()
    {
        return self::class;
    }

    public function injectStepUtility(StepUtility $stepUtility): void
    {
        $this->stepUtility = $stepUtility;
    }

    /**
     * @return string|null
     */
    public function getTable(): ?string
    {
        return $this->table;
    }

    /**
     * @param string $table
     */
    public function setTable($table): void
    {
        $this->table = $table;
    }

    /**
     * @return int|string
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * @param int|string $index
     */
    public function setIndex($index): void
    {
        $this->index = $index;
    }

    /**
     * Returns the general external configuration
     *
     * @return array|null
     */
    public function getGeneralConfiguration(): ?array
    {
        return $this->generalConfiguration;
    }

    /**
     * Sets the general configuration and performs extra processing
     * on some properties.
     *
     * @param array $generalConfiguration
     * @param array $defaultSteps List of default steps (if null will be guessed by the repository)
     * @return void
     */
    public function setGeneralConfiguration(array $generalConfiguration, $defaultSteps = null): void
    {
        $this->generalConfiguration = $generalConfiguration;
        // Define the process default steps, depending on process type or the predefined value
        // NOTE: normally default steps should always be defined
        if ($defaultSteps === null) {
            if (array_key_exists('connector', $generalConfiguration)) {
                $steps = Importer::SYNCHRONYZE_DATA_STEPS;
            } else {
                $steps = Importer::IMPORT_DATA_STEPS;
            }
        } else {
            $steps = $defaultSteps;
        }
        $this->defaultSteps = $steps;
        // Perform extra processing for custom steps
        if (array_key_exists('customSteps', $generalConfiguration)) {
            foreach ($generalConfiguration['customSteps'] as $customStepConfiguration) {
                $steps = $this->stepUtility->insertStep($steps, $customStepConfiguration);
                $this->customSteps[] = $customStepConfiguration['class'];
                if (array_key_exists('parameters', $customStepConfiguration)) {
                    $this->setParametersForStep(
                            $customStepConfiguration['parameters'],
                            $customStepConfiguration['class']
                    );
                }
            }
        }
        $this->steps = $steps;

        // Store the storage pid from the configuration
        // It is stored in a separate variable as it might be overridden
        $this->storagePid = $generalConfiguration['pid'];

        // Handle old-style configuration (comma-separated list)
        // TODO: remove once backward-compatibility is dropped
        if (array_key_exists('additionalFields', $generalConfiguration)) {
            $this->setObsoleteAdditionalFieldsConfiguration(true);
            $fields = GeneralUtility::trimExplode(
                    ',',
                    $generalConfiguration['additionalFields'],
                    true
            );
            $additionalFields = [];
            foreach ($fields as $field) {
                $additionalFields[$field] = [
                        'field' => $field
                ];
            }
            $this->setAdditionalFields($additionalFields);
        }
    }

    /**
     * Returns a specific property from the "ctrl" configuration.
     *
     * @param $key
     * @return mixed|null
     */
    public function getGeneralConfigurationProperty($key)
    {
        if (array_key_exists($key, $this->generalConfiguration)) {
            return $this->generalConfiguration[$key];
        }
        return null;
    }

    /**
     * TODO: remove once backward-compatibility is dropped
     *
     * @return array|null
     * @deprecated use \Cobweb\ExternalImport\Domain\Model\Configuration::getGeneralConfiguration() instead
     */
    public function getCtrlConfiguration(): ?array
    {
        trigger_error(
            'Using \Cobweb\ExternalImport\Domain\Model\Configuration::getCtrlConfiguration() is deprecated. Use \Cobweb\ExternalImport\Domain\Model\Configuration::getGeneralConfiguration() instead.',
            E_USER_DEPRECATED
        );
        return $this->getGeneralConfiguration();
    }

    /**
     * Sets the "ctrl" configuration and performs extra processing
     * on some properties.
     *
     * TODO: remove once backward-compatibility is dropped
     *
     * @param array $ctrlConfiguration
     * @param array $defaultSteps List of default steps (if null will be guessed by the repository)
     * @return void
     * @deprecated use \Cobweb\ExternalImport\Domain\Model\Configuration::setGeneralConfiguration() instead
     */
    public function setCtrlConfiguration(array $ctrlConfiguration, $defaultSteps = null): void
    {
        trigger_error(
            'Using \Cobweb\ExternalImport\Domain\Model\Configuration::setCtrlConfiguration() is deprecated. Use \Cobweb\ExternalImport\Domain\Model\Configuration::setGeneralConfiguration() instead.',
            E_USER_DEPRECATED
        );
        $this->setGeneralConfiguration($ctrlConfiguration, $defaultSteps);
    }

    /**
     * Returns a specific property from the "ctrl" configuration.
     *
     * TODO: remove once backward-compatibility is dropped
     *
     * @param $key
     * @return mixed|null
     * @deprecated use \Cobweb\ExternalImport\Domain\Model\Configuration::getGeneralConfigurationProperty() instead
     */
    public function getCtrlConfigurationProperty($key)
    {
        trigger_error(
            'Using \Cobweb\ExternalImport\Domain\Model\Configuration::getCtrlConfigurationProperty() is deprecated. Use \Cobweb\ExternalImport\Domain\Model\Configuration::getGeneralConfigurationProperty() instead.',
            E_USER_DEPRECATED
        );
        return $this->getGeneralConfigurationProperty($key);
    }

    /**
     * @return array|null
     */
    public function getColumnConfiguration(): ?array
    {
        return $this->columnConfiguration;
    }

    /**
     * Sets the column configurations and processes some data.
     *
     * @param array $columnConfiguration
     */
    public function setColumnConfiguration(array $columnConfiguration): void
    {
        // Merge with additional fields
        if (count($this->additionalFields) > 0) {
            $columnConfiguration = array_merge($columnConfiguration, $this->additionalFields);
        }
        $this->columnConfiguration = $columnConfiguration;
        // TODO: remove once backwards-compatibility is dropped
        $this->updateDeprecatedTransformationProperties();
        $this->sortTransformationProperties();
    }

    /**
     * Returns the External import configuration for a single column.
     *
     * @param string $column Name of the column
     * @return array
     */
    public function getConfigurationForColumn($column): array
    {
        if (array_key_exists($column, $this->columnConfiguration)) {
            return $this->columnConfiguration[$column];
        }
        return [];
    }

    /**
     * Returns the list of columns that must not be saved to the database.
     *
     * @return array
     */
    public function getColumnsExcludedFromSaving(): array
    {
        $columns = [];
        foreach ($this->columnConfiguration as $name => $configuration) {
            if (array_key_exists(self::DO_NOT_SAVE_KEY, $configuration)) {
                $columns[] = $name;
            }
        }
        return $columns;
    }

    /**
     * Sets the column as excluded from saving to the database or not.
     *
     * NOTE: by default all columns are saved and all additional fields are excluded from saving
     *
     * @param string $column Name of the column
     * @param bool $flag
     * @throws \Cobweb\ExternalImport\Exception\NoSuchColumnException
     */
    public function setExcludedFromSavingFlagForColumn($column, $flag): void
    {
        if (array_key_exists($column, $this->columnConfiguration)) {
            $this->columnConfiguration[$column][self::DO_NOT_SAVE_KEY] = $flag;
        } else {
            throw new \Cobweb\ExternalImport\Exception\NoSuchColumnException(
                    sprintf(
                        'The requested column (%s) does not exist.',
                        $column
                    ),
                    1601633669
            );
        }
    }

    /**
     * TODO: remove once backward-compatibility with "ctrl" section is dropped
     *
     * @return bool
     */
    public function isObsoleteGeneralConfiguration(): bool
    {
        return $this->obsoleteGeneralConfiguration;
    }

    /**
     * TODO: remove once backward-compatibility with "ctrl" section is dropped
     *
     * @param bool $obsoleteGeneralConfiguration
     */
    public function setObsoleteGeneralConfiguration(bool $obsoleteGeneralConfiguration): void
    {
        $this->obsoleteGeneralConfiguration = $obsoleteGeneralConfiguration;
    }

    /**
     * TODO: remove once backward-compatibility with comma-separated syntax is dropped
     *
     * @return bool
     */
    public function isObsoleteAdditionalFieldsConfiguration(): bool
    {
        return $this->obsoleteAdditionalFieldsConfiguration;
    }

    /**
     * TODO: remove once backward-compatibility with comma-separated syntax is dropped
     *
     * @param bool $obsoleteAdditionalFieldsConfiguration
     */
    public function setObsoleteAdditionalFieldsConfiguration(bool $obsoleteAdditionalFieldsConfiguration): void
    {
        $this->obsoleteAdditionalFieldsConfiguration = $obsoleteAdditionalFieldsConfiguration;
    }

    /**
     * @return int|null
     */
    public function getStoragePid(): ?int
    {
        return $this->storagePid;
    }

    /**
     * @param int $storagePid
     */
    public function setStoragePid($storagePid): void
    {
        $this->storagePid = $storagePid;
    }

    /**
     * @return array
     */
    public function getAdditionalFields(): array
    {
        return $this->additionalFields;
    }

    /**
     * @param array $additionalFields
     */
    public function setAdditionalFields(array $additionalFields): void
    {
        foreach ($additionalFields as $fieldName => $fieldConfiguration) {
            $additionalFields[$fieldName][self::DO_NOT_SAVE_KEY] = true;
        }
        $this->additionalFields = $additionalFields;
        $this->countAdditionalFields = count($additionalFields);
    }

    /**
     * @return int
     */
    public function getCountAdditionalFields(): int
    {
        return $this->countAdditionalFields;
    }

    /**
     * @param int $countAdditionalFields
     */
    public function setCountAdditionalFields($countAdditionalFields): void
    {
        $this->countAdditionalFields = $countAdditionalFields;
    }

    /**
     * Returns the list of process steps.
     *
     * NOTE: an equivalent setter does not exist as steps should never be defined from the outside.
     *
     * @return array
     */
    public function getSteps(): array
    {
        return $this->steps;
    }

    /**
     * Returns the list of process steps, marked as default steps or valid custom steps.
     *
     * This is used in the configuration detail view in the backend module, for visual feedback.
     *
     * @return array
     */
    public function getDifferentiatedSteps(): array
    {
        $differentatiedSteps = [];
        // Loop on all registered, valid steps
        // Each is either default or custom
        foreach ($this->steps as $step) {
            if (in_array($step, $this->defaultSteps, true)) {
                $differentatiedSteps[$step] = 'default';
            } else {
                $differentatiedSteps[$step] = 'custom';
            }
        }
        return $differentatiedSteps;
    }

    /**
     * Returns the list of invalid custom steps.
     *
     * This is used in the configuration detail view in the backend module, for visual feedback.
     *
     * @return array
     */
    public function getInvalidSteps(): array
    {
        // Custom steps that are not among the registered steps are invalid
        return array_diff($this->customSteps, $this->steps);
    }

    /**
     * Checks if there are parameters for the given step.
     *
     * @param string $step Name of a step class
     * @return bool
     */
    public function hasParametersForStep(string $step): bool
    {
        return isset($this->stepParameters[$step]);
    }

    /**
     * Returns the parameters for the given step.
     *
     * @param string $step Name of a step class
     * @return array
     */
    public function getParametersForStep(string $step): array
    {
        return $this->stepParameters[$step] ?? [];
    }

    /**
     * Sets the list of parameters for the given step.
     *
     * @param array $parameters List of parameters
     * @param string $step Name of a step class
     */
    public function setParametersForStep(array $parameters, string $step): void
    {
        $this->stepParameters[$step] = $parameters;
    }

    /**
     * @return ConnectorBase
     */
    public function getConnector(): \Cobweb\Svconnector\Service\ConnectorBase
    {
        return $this->connector;
    }

    /**
     * @param ConnectorBase $connector
     */
    public function setConnector(ConnectorBase $connector): void
    {
        $this->connector = $connector;
    }

    /**
     * Takes care of migrating old "userFunc" and "params" configuration into
     * "userFunction" and "parameters" configuration.
     *
     * NOTE: we don't unset the old properties because we want them to be caught during validation
     * for outputting deprecation notices.
     */
    protected function updateDeprecatedTransformationProperties(): void
    {
        $updatedConfiguration = [];
        foreach ($this->columnConfiguration as $name => $configuration) {
            $updatedConfiguration[$name] = $configuration;
            if (isset($configuration['transformations'])) {
                $transformationConfiguration = [];
                foreach ($configuration['transformations'] as $index => $property) {
                    $transformationConfiguration[$index] = $property;
                    foreach ($property as $key => $propertyConfiguration) {
                        if ($key === 'userFunc') {
                            if (isset($propertyConfiguration['params'])) {
                                $propertyConfiguration['parameters'] = $propertyConfiguration['params'];
                            }
                            $transformationConfiguration[$index]['userFunction'] = $propertyConfiguration;
                        }
                    }
                }
                $updatedConfiguration[$name]['transformations'] = $transformationConfiguration;
            }
        }
        $this->columnConfiguration = $updatedConfiguration;
    }

    /**
     * Makes sure that the transformation properties are sorted.
     *
     * @return void
     */
    protected function sortTransformationProperties(): void
    {
        foreach ($this->columnConfiguration as $name => $configuration) {
            if (isset($configuration['transformations'])) {
                $transformations = $configuration['transformations'];
                ksort($transformations);
                $this->columnConfiguration[$name]['transformations'] = $transformations;
            }
        }
    }
}