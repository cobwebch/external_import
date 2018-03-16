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
    public const DEFAULT_PRIORITY = 1000;

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
     * @var \Cobweb\ExternalImport\Domain\Model\Configuration Full External Import configuration
     */
    protected $externalConfiguration;

    /**
     * @var ReportingUtility Utility for reporting after import
     */
    protected $reportingUtility;

    /**
     * @var \Cobweb\ExternalImport\Domain\Repository\UidRepository
     */
    protected $uidRepository;

    /**
     * @var int Externally enforced id of a page where the records should be stored (overrides "pid", used for testing)
     */
    protected $forcedStoragePid;

    /**
     * @var array List of temporary keys created on the fly for new records. Used in DataHandler data map.
     */
    protected $temporaryKeys = array();

    /**
     * @var array List of default steps for the synchronize data process
     */
    public const SYNCHRONYZE_DATA_STEPS = array(
            Step\CheckPermissionsStep::class,
            Step\ValidateConfigurationStep::class,
            Step\ValidateConnectorStep::class,
            Step\ReadDataStep::class,
            Step\HandleDataStep::class,
            Step\ValidateDataStep::class,
            Step\TransformDataStep::class,
            Step\StoreDataStep::class,
            Step\ClearCacheStep::class,
            Step\ConnectorCallbackStep::class
    );

    /**
     * @var array List of default steps for the import data process
     */
    public const IMPORT_DATA_STEPS = array(
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

    public function injectUidRepository(\Cobweb\ExternalImport\Domain\Repository\UidRepository $uidRepository)
    {
        $this->uidRepository = $uidRepository;
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
    public function synchronize($table, $index)
    {
        // Initialize message array
        $this->resetMessages();
        try {
            $this->initialize(
                    $table,
                    $index,
                    self::SYNCHRONYZE_DATA_STEPS
            );
            // Initialize existing uids list
            $this->uidRepository->setConfiguration($this->externalConfiguration);
            $this->uidRepository->resetExistingUids();

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
    public function import($table, $index, $rawData)
    {
        // Initialize message array
        $this->resetMessages();
        try {
            $this->initialize(
                    $table,
                    $index,
                    self::IMPORT_DATA_STEPS
            );
            // Initialize existing uids list
            $this->uidRepository->setConfiguration($this->externalConfiguration);
            $this->uidRepository->resetExistingUids();
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

    // Getters and setters

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
            if (count($messageList) === 0) {
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
     * Forces the storage pid for imported records.
     *
     * This is meant essentially for testing, but can also be useful when using Importer::import().
     *
     * @param $pid
     */
    public function setForcedStoragePid($pid)
    {
        $this->forcedStoragePid = (int)$pid;
    }
}
