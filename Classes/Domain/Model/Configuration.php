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
    /**
     * @var string Name of the table to which the configuration applies
     */
    protected $table;

    /**
     * @var int|string Index identifying the configuration for the given table
     */
    protected $index;

    /**
     * @var array "ctrl" part of the External Import configuration
     */
    protected $ctrlConfiguration;

    /**
     * @var array External Import configuration for each column
     */
    protected $columnConfiguration;

    /**
     * @var int ID of storage page
     */
    protected $storagePid;

    /**
     * @var array List of fields that must be read from distant stored, but will not be stored to DB
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
     * @var ConnectorBase Reference to the connector object
     */
    protected $connector;

    /**
     * @var StepUtility
     */
    protected $stepUtility;

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
     * @return array|null
     */
    public function getCtrlConfiguration(): ?array
    {
        return $this->ctrlConfiguration;
    }

    /**
     * Sets the "ctrl" part of the configuration and performs extra processing
     * on some properties.
     *
     * @param array $ctrlConfiguration
     * @param array $defaultSteps List of default steps (if null will be guessed by the repository)
     * @return void
     */
    public function setCtrlConfiguration(array $ctrlConfiguration, $defaultSteps = null): void
    {
        $this->ctrlConfiguration = $ctrlConfiguration;
        // Define the process default steps, depending on process type or the predefined value
        // NOTE: normally default steps should always be defined
        if ($defaultSteps === null) {
            if (array_key_exists('connector', $ctrlConfiguration)) {
                $steps = Importer::SYNCHRONYZE_DATA_STEPS;
            } else {
                $steps = Importer::IMPORT_DATA_STEPS;
            }
        } else {
            $steps = $defaultSteps;
        }
        // Perform extra processing for custom steps
        if (array_key_exists('customSteps', $ctrlConfiguration)) {
            foreach ($ctrlConfiguration['customSteps'] as $customStepConfiguration) {
                $steps = $this->stepUtility->insertStep($steps, $customStepConfiguration);
            }
        }
        $this->steps = $steps;

        // Store the storage pid from the configuration
        // It is stored in a separate variable as it might be overridden
        $this->storagePid = $ctrlConfiguration['pid'];

        // Perform extra processing for additional fields
        if (array_key_exists('additionalFields', $ctrlConfiguration)) {
            $additionalFields = GeneralUtility::trimExplode(
                    ',',
                    $ctrlConfiguration['additionalFields'],
                    true
            );
            $this->setAdditionalFields($additionalFields);
            $this->setCountAdditionalFields(
                    count($additionalFields)
            );
        }
    }

    /**
     * Returns a specific property from the "ctrl" configuration.
     *
     * @param $key
     * @return mixed|null
     */
    public function getCtrlConfigurationProperty($key)
    {
        if (array_key_exists($key, $this->ctrlConfiguration)) {
            return $this->ctrlConfiguration[$key];
        }
        return null;
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
        $this->columnConfiguration = $columnConfiguration;
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