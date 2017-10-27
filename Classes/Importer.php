<?php
namespace Cobweb\ExternalImport;

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

use Cobweb\ExternalImport\Domain\Model\Data;
use Cobweb\ExternalImport\Domain\Repository\ConfigurationRepository;
use Cobweb\ExternalImport\Utility\ReportingUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * This class drives the import process by moving from step to step, aborting when necessary and triggering the reporting.
 *
 * @author Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package TYPO3
 * @subpackage tx_externalimport
 */
class Importer
{
    const DEFAULT_PRIORITY = 1000;

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    protected $objectManager;

    /**
     * @var array Extension configuration
     */
    protected $extensionConfiguration = array();

    /**
     * @var array List of result messages
     */
    protected $messages = array();

    /**
     * @var ConfigurationRepository
     */
    protected $configurationRepository;

    /**
     * @var string Name of the table being synchronised
     */
    protected $table;

    /**
     * @var mixed Index of the synchronisation configuration in use
     */
    protected $index;

    /**
     * @var mixed Index for the columns, may be different from $this->index
     */
    protected $columnIndex;

    /**
     * @var \Cobweb\ExternalImport\Domain\Model\Configuration Full External Import configuration
     */
    protected $externalConfiguration;

    /**
     * @var ReportingUtility Utility for reporting after import
     */
    protected $reportingUtility;

    /**
     * @var int Externally enforced id of a page where the records should be stored (overrides "pid", used for testing)
     */
    protected $forcedStoragePid = null;

    /**
     * @var array List of primary keys of records that already exist in the database
     */
    protected $existingUids = array();

    /**
     * @var array List of temporary keys created on the fly for new records. Used in DataHandler data map.
     */
    protected $temporaryKeys = array();

    /**
     * @var array List of default steps for the synchronize data process
     */
    const SYNCHRONYZE_DATA_STEPS = array(
            Step\CheckPermissionsStep::class,
            Step\ValidateConfigurationStep::class,
            Step\ReadDataStep::class,
            Step\HandleDataStep::class,
            Step\ValidateDataStep::class,
            Step\TransformDataStep::class,
            Step\StoreDataStep::class,
            Step\ClearCacheStep::class
    );

    /**
     * @var array List of default steps for the import data process
     */
    const IMPORT_DATA_STEPS = array(
            Step\CheckPermissionsStep::class,
            Step\ValidateConfigurationStep::class,
            Step\HandleDataStep::class,
            Step\ValidateDataStep::class,
            Step\TransformDataStep::class,
            Step\StoreDataStep::class,
            Step\ClearCacheStep::class
    );

    public function __construct()
    {
        $this->extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['external_import']);

        // Force PHP limit execution time if set
        if (isset($this->extensionConfiguration['timelimit']) && ($this->extensionConfiguration['timelimit'] > -1)) {
            set_time_limit($this->extensionConfiguration['timelimit']);
            $this->debug(
                    LocalizationUtility::translate(
                            'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:timelimit',
                            'external_import'
                    ),
                    0,
                    $this->extensionConfiguration['timelimit']
            );
        }
        // Initialize message array
        $this->resetMessages();
    }

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

    public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManager $manager)
    {
        $this->objectManager = $manager;
    }

    public function injectConfigurationRepository(\Cobweb\ExternalImport\Domain\Repository\ConfigurationRepository $repository)
    {
        $this->configurationRepository = $repository;
    }

    public function injectReportingUtility(\Cobweb\ExternalImport\Utility\ReportingUtility $utility)
    {
        $this->reportingUtility = $utility;
        $this->reportingUtility->setImporter($this);
    }

    /**
     * Synchronises all the external tables, respecting the order of priority.
     *
     * @return array List of all messages
     * @deprecated Will be removed in the next version. Use synchronizeData() in own loop instead (see usage in \Cobweb\ExternalImport\Command\ImportCommand::execute() for example).
     */
    public function synchronizeAllTables()
    {
        GeneralUtility::logDeprecatedFunction();
        $externalTables = $this->configurationRepository->findOrderedConfigurations();

        $this->debug(
                LocalizationUtility::translate(
                        'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:sync_all',
                        'external_import'
                ),
                0,
                $externalTables
        );

        // Synchronise all tables
        $allMessages = array();
        foreach ($externalTables as $tables) {
            foreach ($tables as $tableData) {
                $this->messages = array(
                        FlashMessage::ERROR => array(),
                        FlashMessage::WARNING => array(),
                        FlashMessage::OK => array()
                ); // Reset error messages array
                $messages = $this->synchronizeData($tableData['table'], $tableData['index']);
                $key = $tableData['table'] . '/' . $tableData['index'];
                $allMessages[$key] = $messages;
            }
        }

        // Return compiled array of messages for all imports
        return $allMessages;
    }

    /**
     * Stores information about the synchronized table into member variables.
     *
     * @param string $table Name of the table to synchronise
     * @param mixed $index Index of the synchronisation configuration to use
     * @param array $defaultSteps List of default steps (if null will be guessed by the Configuration object)
     * @return void
     */
    protected function initialize($table, $index, $defaultSteps = null)
    {
        $this->table = $table;
        $this->index = $index;
        $this->externalConfiguration = $this->configurationRepository->findConfigurationObject(
                $table,
                $index,
                $defaultSteps
        );
        if ($this->forcedStoragePid !== null)
        {
            $this->externalConfiguration->setStoragePid($this->forcedStoragePid);
        }
        // Set the storage page as the related page for the devLog entries
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['debugData']['pid'] = $this->externalConfiguration->getStoragePid();
    }

    /**
     * Calls on the distant data source and synchronizes the data into the TYPO3 database.
     *
     * @param string $table Name of the table to synchronise
     * @param mixed $index Index of the synchronisation configuration to use
     * @return array List of error or success messages
     */
    public function synchronizeData($table, $index)
    {
        // Initialize message array
        $this->resetMessages();
        try {
            $this->initialize($table, $index);
            $ctrlConfiguration = $this->externalConfiguration->getCtrlConfiguration();
            // If the selected configuration has no connector, it cannot be synchronized
            if (empty($ctrlConfiguration['connector'])) {
                $this->addMessage(
                        LocalizationUtility::translate(
                                'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:no_connector',
                                'external_import'
                        )
                );
            } else {
                $data = $this->objectManager->get(Data::class);
                $steps = $this->externalConfiguration->getSteps();
                foreach ($steps as $stepClass) {
                    /** @var \Cobweb\ExternalImport\Step\AbstractStep $step */
                    $step = $this->objectManager->get($stepClass);
                    $step->setImporter($this);
                    $step->setConfiguration($this->externalConfiguration);
                    $step->setData($data);
                    $step->run();
                    if ($step->isAbortFlag()) {
                        // Report about aborting
                        $this->addMessage(
                                LocalizationUtility::translate(
                                        'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:importAborted',
                                        'external_import'
                                ),
                                FlashMessage::WARNING
                        );
                        break;
                    }
                    $data = $step->getData();
                }
            }
            // Call connector's post-processing with a rough error status
            if ($this->externalConfiguration->getConnector() !== null) {
                $errorStatus = false;
                if (count($this->messages[FlashMessage::ERROR]) > 0) {
                    $errorStatus = true;
                }
                $this->externalConfiguration->getConnector()->postProcessOperations(
                        $ctrlConfiguration['parameters'],
                        $errorStatus
                );
            }
        }
        catch (\Exception $e) {
            $this->addMessage(
                    LocalizationUtility::translate(
                            'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:noConfigurationFound',
                            'external_import',
                            array(
                                    $table,
                                    $index,
                                    $e->getMessage(),
                                    $e->getCode()
                            )
                    )
            );
        }
        // Log results
        $this->reportingUtility->writeToDevLog();
        $this->reportingUtility->writeToLog();

        return $this->messages;
    }

    /**
     * Receives raw data from some external source, transforms it and stores it into the TYPO3 database.
     *
     * @param string $table Name of the table to import into
     * @param integer $index Index of the synchronisation configuration to use
     * @param mixed $rawData Data in the format provided by the external source (XML string, PHP array, etc.)
     * @return array List of error or success messages
     */
    public function importData($table, $index, $rawData)
    {
        // Initialize message array
        $this->resetMessages();
        try {
            $this->initialize(
                    $table,
                    $index,
                    // Force steps for the "import data" process, as a synchronizable configuration could also be used
                    self::IMPORT_DATA_STEPS
            );
            // Initialize the Data object with the raw data
            $data = $this->objectManager->get(Data::class);
            $data->setRawData($rawData);
            // Loop on all the process steps
            $steps = $this->externalConfiguration->getSteps();
            foreach ($steps as $stepClass) {
                /** @var \Cobweb\ExternalImport\Step\AbstractStep $step */
                $step = $this->objectManager->get($stepClass);
                $step->setImporter($this);
                $step->setConfiguration($this->externalConfiguration);
                $step->setData($data);
                $step->run();
                if ($step->isAbortFlag()) {
                    // Report about aborting
                    $this->addMessage(
                            LocalizationUtility::translate(
                                    'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:importAborted',
                                    'external_import'
                            ),
                            FlashMessage::WARNING
                    );
                    break;
                }
                $data = $step->getData();
            }
        }
        catch (\Exception $e) {
            $this->addMessage(
                    LocalizationUtility::translate(
                            'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:noConfigurationFound',
                            'external_import',
                            array(
                                    $table,
                                    $index,
                                    $e->getMessage(),
                                    $e->getCode()
                            )
                    )
            );
        }
        // Log results
        $this->reportingUtility->writeToDevLog();
        $this->reportingUtility->writeToLog();
        return $this->messages;
    }

    /**
     * Prepares a list of all existing primary keys in the table being synchronized.
     *
     * The result is a hash table of all external primary keys matched to internal primary keys.
     *
     * @return void
     */
    public function retrieveExistingUids()
    {
        $this->existingUids = array();
        $ctrlConfiguration = $this->externalConfiguration->getCtrlConfiguration();
        $where = '1 = 1';
        if ($ctrlConfiguration['enforcePid']) {
            $where = 'pid = ' . (int)$this->externalConfiguration->getStoragePid();
        }
        if (!empty($ctrlConfiguration['whereClause'])) {
            $where .= ' AND ' . $ctrlConfiguration['whereClause'];
        }
        $where .= BackendUtility::deleteClause($this->table);
        $referenceUidField = $ctrlConfiguration['referenceUid'];
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                $referenceUidField . ',uid',
                $this->table,
                $where
        );
        if ($res) {
            while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
                // Don't consider records with empty references, as they can't be matched
                // to external data anyway (but a real zero is acceptable)
                if (!empty($row[$referenceUidField]) || $row[$referenceUidField] === '0' || $row[$referenceUidField] === 0) {
                    $this->existingUids[$row[$referenceUidField]] = $row['uid'];
                }
            }
            $GLOBALS['TYPO3_DB']->sql_free_result($res);
        }
    }

    // Getters and setters


    /**
     * Returns the name of the table being synchronised
     *
     * @return string Name of the table
     * @deprecated since 4.0.0, will be removed in the next major version - use getExternalConfiguration()->getTable() instead
     */
    public function getTableName()
    {
        GeneralUtility::logDeprecatedFunction();
        return $this->table;
    }

    /**
     * Sets the name of the table to be synchronized.
     *
     * This is used only in special cases, you should never need to call this.
     *
     * @param string $table Name of the table
     */
    public function setTableName($table)
    {
        $this->table = $table;
    }

    /**
     * Returns the index of the configuration used in the current synchronisation.
     *
     * @return mixed
     * @deprecated since 4.0.0, will be removed in the next major version - use getExternalConfiguration()->getIndex() instead
     */
    public function getIndex()
    {
        GeneralUtility::logDeprecatedFunction();
        return $this->index;
    }

    /**
     * Sets the index of the configuration to used for synchronization.
     *
     * This is used only in special cases, you should never need to call this.
     *
     * @param mixed $index Index to use
     */
    public function setIndex($index)
    {
        $this->index = $index;
    }

    /**
     * Returns the index of the configuration used for the columns.
     *
     * @return mixed
     * @deprecated since 4.0.0, this method is not used and will be dropped without replacement
     */
    public function getColumnIndex()
    {
        return $this->columnIndex;
    }

    /**
     * Returns the external configuration found in the ctrl section of the TCA
     * of the table being synchronised.
     *
     * @return \Cobweb\ExternalImport\Domain\Model\Configuration External configuration from the TCA ctrl section
     * @deprecated since 4.0.0, will be removed in the next major version - use getExternalConfiguration() instead
     */
    public function getExternalConfig()
    {
        GeneralUtility::logDeprecatedFunction();
        return $this->externalConfiguration;
    }

    /**
     * Returns the model object containing the whole External Import configuration.
     *
     * @return \Cobweb\ExternalImport\Domain\Model\Configuration
     */
    public function getExternalConfiguration()
    {
        return $this->externalConfiguration;
    }

    /**
     * Returns the instance of the configuration repository.
     *
     * @return ConfigurationRepository
     */
    public function getConfigurationRepository()
    {
        return $this->configurationRepository;
    }

    /**
     * Returns the extension's configuration.
     *
     * It is used to avoid reading it multiple times from the various components of this extension.
     *
     * @return array The unserialized extension's configuration
     */
    public function getExtensionConfiguration()
    {
        return $this->extensionConfiguration;
    }

    /**
     * Returns the list of primary keys of existing records in the database.
     *
     * This can be useful for steps or hooks called during the import process.
     *
     * @return array
     */
    public function getExistingUids()
    {
        return $this->existingUids;
    }

    /**
     * Returns the list of all temporary keys.
     *
     * @return array
     */
    public function getTemporaryKeys()
    {
        return $this->temporaryKeys;
    }

    /**
     * Checks whether a temporary key exists for the given value.
     *
     * @param int $value Value for which we want to find a key
     * @return bool
     */
    public function hasTemporaryKey($value)
    {
        return array_key_exists($value, $this->temporaryKeys);
    }

    /**
     * Gets the temporary key for the given value.
     *
     * @param int $value Value for which we want to find a key
     * @return string
     */
    public function getTemporaryKeyForValue($value)
    {
        if (array_key_exists($value, $this->temporaryKeys)) {
            return $this->temporaryKeys[$value];
        }
        return null;
    }

    /**
     * Adds a temporary key for the given value.
     *
     * @param int $value
     * @param string $key
     */
    public function addTemporaryKey($value, $key)
    {
        $this->temporaryKeys[$value] = $key;
    }

    /**
     * Writes debug messages to the devlog, depending on debug flag.
     *
     * @param $message
     * @param int $severity
     * @param null $data
     * @return void
     */
    public function debug($message, $severity = 0, $data = null)
    {
        if ($this->extensionConfiguration['debug'] || TYPO3_DLOG) {
            GeneralUtility::devLog(
                    $message,
                    'external_import',
                    $severity,
                    $data
            );
        }
    }

    /**
     * Adds a message to the message queue that will be returned
     * when the synchronization is complete.
     *
     * @param string $text The message itself
     * @param integer $status Status of the message. Expected is "success", "warning" or "error"
     * @return void
     */
    public function addMessage($text, $status = FlashMessage::ERROR)
    {
        if (!empty($text)) {
            $this->messages[$status][] = $text;
        }
    }

    /**
     * Returns the list of all messages.
     *
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * Empties the internal message queue.
     *
     * @return void
     */
    public function resetMessages()
    {
        $this->messages = array(
                FlashMessage::ERROR => array(),
                FlashMessage::WARNING => array(),
                FlashMessage::OK => array()
        );
    }
    // Reporting utilities


    /**
     * This method assembles a report for a given table/index
     *
     * @param string $table Name of the table
     * @param integer $index Number of the synchronisation configuration
     * @param array $messages List of messages for the given table
     * @return string Formatted text of the report
     */
    public function reportForTable($table, $index, $messages)
    {
        $report = sprintf($GLOBALS['LANG']->getLL('synchronizeTableX'), $table, $index) . "\n\n";
        foreach ($messages as $type => $messageList) {
            $report .= $GLOBALS['LANG']->getLL('label.' . $type) . "\n";
            if (count($messageList) == 0) {
                $report .= "\t" . $GLOBALS['LANG']->getLL('no.' . $type) . "\n";
            } else {
                foreach ($messageList as $aMessage) {
                    $report .= "\t- " . $aMessage . "\n";
                }
            }
        }
        $report .= "\n";
        return $report;
    }

    /**
     * Sends a reporting mail to the configured e-mail address
     *
     * @param string $subject Subject of the mail
     * @param string $body Text body of the mail
     * @return void
     */
    public function sendMail($subject, $body)
    {
        $result = 0;
        // Define sender mail and name
        $senderMail = '';
        $senderName = '';
        if (!empty($GLOBALS['BE_USER']->user['email'])) {
            $senderMail = $GLOBALS['BE_USER']->user['email'];
            if (empty($GLOBALS['BE_USER']->user['realName'])) {
                $senderName = $GLOBALS['BE_USER']->user['username'];
            } else {
                $senderName = $GLOBALS['BE_USER']->user['realName'];
            }
        } elseif (!empty($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'])) {
            $senderMail = $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'];
            if (empty($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'])) {
                $senderName = $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'];
            } else {
                $senderName = $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'];
            }
        }
        // If no mail could be found, avoid sending the mail
        // The message will be logged as an error
        if (empty($senderMail)) {
            $message = 'No sender mail defined. Please check the manual.';

            // Proceed with sending the mail
        } else {
            // Instantiate and initialize the mail object
            /** @var $mailObject MailMessage */
            $mailObject = GeneralUtility::makeInstance(MailMessage::class);
            try {
                $sender = array(
                        $senderMail => $senderName
                );
                $mailObject->setFrom($sender);
                $mailObject->setReplyTo($sender);
                $mailObject->setTo(array($this->extensionConfiguration['reportEmail']));
                $mailObject->setSubject($subject);
                $mailObject->setBody($body);
                // Send mail
                $result = $mailObject->send();
                $message = '';
            } catch (\Exception $e) {
                $message = $e->getMessage() . '[' . $e->getCode() . ']';
            }
        }

        // Report error in log, if any
        if ($result === 0) {
            $comment = 'Reporting mail could not be sent to ' . $this->extensionConfiguration['reportEmail'];
            if (!empty($message)) {
                $comment .= ' (' . $message . ')';
            }
            $GLOBALS['BE_USER']->writelog(
                    4,
                    0,
                    1,
                    'external_import',
                    $comment,
                    array()
            );
        }
    }

    /**
     * Forces the storage pid for imported records. Used for testing only!
     *
     * @param $pid
     */
    public function setForcedStoragePid($pid)
    {
        $this->forcedStoragePid = (int)$pid;
    }
}
