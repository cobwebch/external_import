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
     * @var array List of fields that must be read from distant stored, but will not be stored to DB
     */
    protected $additionalFields = array();

    /**
     * @var int Number of additional fields (cached to avoid counting too often)
     */
    protected $countAdditionalFields = 0;

    /**
     * @var array List of steps that the process will go through (depends on process type)
     */
    protected $steps = array();

    /**
     * @var \Cobweb\Svconnector\Service\ConnectorBase Reference to the connector object
     */
    protected $connector;

    /**
     * @var \Cobweb\ExternalImport\Utility\StepUtility
     */
    protected $stepUtility;

    public function injectStepUtility(\Cobweb\ExternalImport\Utility\StepUtility $stepUtility)
    {
        $this->stepUtility = $stepUtility;
    }

    /**
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * @param string $table
     */
    public function setTable($table)
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
    public function setIndex($index)
    {
        $this->index = $index;
    }

    /**
     * @return array
     */
    public function getCtrlConfiguration()
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
    public function setCtrlConfiguration(array $ctrlConfiguration, $defaultSteps = null)
    {
        $this->ctrlConfiguration = $ctrlConfiguration;
        // Define the process default steps, depending on process type or the predefined value
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
     * @return array
     */
    public function getColumnConfiguration()
    {
        return $this->columnConfiguration;
    }

    /**
     * @param array $columnConfiguration
     */
    public function setColumnConfiguration(array $columnConfiguration)
    {
        $this->columnConfiguration = $columnConfiguration;
    }

    /**
     * Returns the External import configuration for a single column.
     *
     * @param string $column Name of the column
     * @return array
     */
    public function getConfigurationForColumn($column)
    {
        if (array_key_exists($column, $this->columnConfiguration)) {
            return $this->columnConfiguration[$column];
        }
        return array();
    }

    /**
     * @return array
     */
    public function getAdditionalFields()
    {
        return $this->additionalFields;
    }

    /**
     * @param array $additionalFields
     */
    public function setAdditionalFields(array $additionalFields)
    {
        $this->additionalFields = $additionalFields;
    }

    /**
     * @return int
     */
    public function getCountAdditionalFields()
    {
        return $this->countAdditionalFields;
    }

    /**
     * @param int $countAdditionalFields
     */
    public function setCountAdditionalFields($countAdditionalFields)
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
    public function getSteps()
    {
        return $this->steps;
    }

    /**
     * @return \Cobweb\Svconnector\Service\ConnectorBase
     */
    public function getConnector()
    {
        return $this->connector;
    }

    /**
     * @param \Cobweb\Svconnector\Service\ConnectorBase $connector
     */
    public function setConnector(\Cobweb\Svconnector\Service\ConnectorBase $connector)
    {
        $this->connector = $connector;
    }
}