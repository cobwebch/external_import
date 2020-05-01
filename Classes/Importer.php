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

use Cobweb\ExternalImport\Context\AbstractCallContext;
use Cobweb\ExternalImport\Domain\Model\Data;
use Cobweb\ExternalImport\Domain\Repository\ConfigurationRepository;
use Cobweb\ExternalImport\Exception\InvalidPreviewStepException;
use Cobweb\ExternalImport\Utility\CompatibilityUtility;
use Cobweb\ExternalImport\Utility\ReportingUtility;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
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
    protected $extensionConfiguration = [];

    /**
     * @var array List of result messages
     */
    protected $messages = [];

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
     * @var \TYPO3\CMS\Core\Log\Logger
     */
    protected $logger;

    /**
     * @var int Externally enforced id of a page where the records should be stored (overrides "pid", used for testing)
     */
    protected $forcedStoragePid;

    /**
     * @var array List of temporary keys created on the fly for new records. Used in DataHandler data map.
     */
    protected $temporaryKeys = [];

    /**
     * @var int Incremental number to be used for temporary keys during test mode (used for unit testing)
     */
    static protected $forcedTemporaryKeySerial = 0;

    /**
     * @var string Context in which the import run is executed
     */
    protected $context = 'manual';

    /**
     * @var AbstractCallContext
     */
    protected $callContext = null;

    /**
     * @var bool Whether debugging is turned on or off
     */
    protected $debug = false;

    /**
     * @var bool Whether the output should be verbose or not (currently only affects calls made via the command line)
     */
    protected $verbose = false;

    /**
     * @var string Name of the Step class at which the process should stop when running in preview mode
     */
    protected $previewStep = '';

    /**
     * @var string|array Data to be returned as preview data
     */
    protected $previewData;

    /**
     * @var bool Set to true to trigger testing mode (used only for unit testing)
     */
    protected $testMode = false;

    /**
     * @var int Start time of the current run
     */
    protected $startTime = 0;

    /**
     * @var int End time of the current run
     */
    protected $endTime = 0;

    /**
     * @var array List of default steps for the synchronize data process
     */
    const SYNCHRONYZE_DATA_STEPS = [
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
    ];

    /**
     * @var array List of default steps for the import data process
     */
    const IMPORT_DATA_STEPS = [
            Step\CheckPermissionsStep::class,
            Step\ValidateConfigurationStep::class,
            Step\HandleDataStep::class,
            Step\ValidateDataStep::class,
            Step\TransformDataStep::class,
            Step\StoreDataStep::class,
            Step\ClearCacheStep::class
    ];

    public function __construct()
    {
        if (isset($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['external_import'])) {
            $this->extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['external_import'], ['allowed_classes' => false]);
        }
        $this->setDebug((bool)$this->extensionConfiguration['debug']);

        // Force PHP limit execution time if set
        if (isset($this->extensionConfiguration['timelimit']) && ($this->extensionConfiguration['timelimit'] > -1)) {
            set_time_limit($this->extensionConfiguration['timelimit']);
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
        // Initialize existing uids list
        $this->uidRepository->setConfiguration($this->externalConfiguration);
        $this->uidRepository->resetExistingUids();
        /** @var $logger \TYPO3\CMS\Core\Log\Logger */
        $this->logger = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Log\LogManager::class)->getLogger(__CLASS__);    }

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

            $data = $this->objectManager->get(Data::class);
            $this->runSteps($data);
        }
        catch (InvalidPreviewStepException $e) {
            $this->addMessage(
                    LocalizationUtility::translate(
                            'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:wrongPreviewStep',
                            'external_import',
                            [
                                    $e->getMessage()
                            ]
                    ),
                    AbstractMessage::WARNING
            );
        }
        catch (\Exception $e) {
            $this->addMessage(
                    LocalizationUtility::translate(
                            'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:noConfigurationFound',
                            'external_import',
                            [
                                    $table,
                                    $index,
                                    $e->getMessage(),
                                    $e->getCode()
                            ]
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
        // Set context to API no matter what
        $this->setContext('api');
        try {
            $this->initialize(
                    $table,
                    $index,
                    self::IMPORT_DATA_STEPS
            );

            // Initialize the Data object with the raw data
            $data = $this->objectManager->get(Data::class);
            $data->setRawData($rawData);
            $this->runSteps($data);
        }
        catch (InvalidPreviewStepException $e) {
            $this->addMessage(
                    LocalizationUtility::translate(
                            'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:wrongPreviewStep',
                            'external_import',
                            [
                                    $e->getMessage()
                            ]
                    )
            );
        }
        catch (\Exception $e) {
            $this->addMessage(
                    LocalizationUtility::translate(
                            'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:noConfigurationFound',
                            'external_import',
                            [
                                    $table,
                                    $index,
                                    $e->getMessage(),
                                    $e->getCode()
                            ]
                    )
            );
        }
        // Log results
        $this->reportingUtility->writeToDevLog();
        $this->reportingUtility->writeToLog();
        return $this->messages;
    }

    /**
     * Runs the process through the defined steps.
     *
     * @param Data $data Initialized data object
     * @return void
     * @throws Exception\InvalidPreviewStepException
     */
    public function runSteps(Data $data)
    {
        $this->setStartTime(time());
        // Get the process steps
        $steps = $this->externalConfiguration->getSteps();
        // If preview is defined, but step is not part of the process, issue exception
        // NOTE: this cannot be checked during a setPreviewStep() call as the configuration is not yet loaded
        if ($this->isPreview() && !in_array($this->getPreviewStep(), $steps, true)) {
            throw new InvalidPreviewStepException(
                    $this->getPreviewStep(),
                    1532072718
            );
        }
        // Loop on all the process steps
        foreach ($steps as $stepClass) {
            $this->resetPreviewData();
            /** @var \Cobweb\ExternalImport\Step\AbstractStep $step */
            $step = $this->objectManager->get($stepClass);
            $step->setImporter($this);
            $step->setConfiguration($this->externalConfiguration);
            $step->setData($data);
            $step->run();
            // Abort process if step required it
            if ($step->isAbortFlag()) {
                // Report about aborting
                $this->addMessage(
                        LocalizationUtility::translate(
                                'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:importAborted',
                                'external_import'
                        ),
                        AbstractMessage::WARNING
                );
                break;
            }
            // If preview is on and the step is matched, exit process
            // NOTE: this happens after abort, as aborting means there's an underlying problem, which should be reported no matter what
            if ($this->isPreview() && $this->getPreviewStep() === $stepClass) {
                break;
            }
            $data = $step->getData();
            // We set the end time after each step, so that we still capture a certain duration even if one step crashes unexpectedly
            $this->setEndTime(time());
        }
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
     * Generates a random key and returns it.
     *
     * The keys are used for new records in the TCE structures used for storing new records.
     * A random key is recommended. Controlled keys are generated in test mode in order
     * to have predictable results for functional testing.
     *
     * @return string
     */
    public function generateTemporaryKey()
    {
        if ($this->isTestMode()) {
            self::$forcedTemporaryKeySerial++;
            return 'NEW' . self::$forcedTemporaryKeySerial;
        }
        return uniqid('NEW', true);
    }

    /**
     * Writes debug messages, depending on debug flag.
     *
     * The output varies depending on TYPO3 version and call context.
     *
     * @param string $message The debug message
     * @param int $severity The severity of the issue
     * @param null $data Data associated with the debugging information
     * @return void
     */
    public function debug($message, $severity = 0, $data = null)
    {
        if ($this->isDebug()) {
            if (CompatibilityUtility::isV8()) {
                GeneralUtility::devLog(
                        $message,
                        'external_import',
                        $severity,
                        $data
                );
            } else {
                $data = is_array($data) ? $data : [$data];
                // Match devlog severities: 0 is info, 1 is notice, 2 is warning, 3 is fatal error, -1 is "OK" message
                switch ($severity) {
                    case 0:
                        $this->logger->info(
                                $message,
                                $data
                        );
                        break;
                    case 1:
                        $this->logger->notice(
                                $message,
                                $data
                        );
                        break;
                    case 2:
                        $this->logger->warning(
                                $message,
                                $data
                        );
                        break;
                    case 3:
                        $this->logger->error(
                                $message,
                                $data
                        );
                        break;
                    default:
                        $this->logger->debug(
                                $message,
                                $data
                        );
                }
            }
            // Push the debug data to the call context for special display, if needed (e.g. the command-line controller)
            if ($this->callContext !== null) {
                $this->callContext->outputDebug(
                        $message,
                        $severity,
                        $data
                );
            }
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
    public function addMessage($text, $status = AbstractMessage::ERROR)
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
        $this->messages = [
                AbstractMessage::ERROR => [],
                AbstractMessage::WARNING => [],
                AbstractMessage::OK => []
        ];
    }

    /**
     * Returns the current instance of the reporting utility.
     *
     * @return ReportingUtility
     */
    public function getReportingUtility()
    {
        return $this->reportingUtility;
    }

    /**
     * Returns the uid repository.
     *
     * @return Domain\Repository\UidRepository
     */
    public function getUidRepository()
    {
        return $this->uidRepository;
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

    /**
     * Returns the current execution context.
     *
     * @return string
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Sets the execution context.
     *
     * @param $context
     */
    public function setContext($context)
    {
        $this->context = $context;
    }

    /**
     * @return AbstractCallContext
     */
    public function getCallContext()
    {
        return $this->callContext;
    }

    /**
     * @param AbstractCallContext $callContext
     */
    public function setCallContext(AbstractCallContext $callContext)
    {
        $this->callContext = $callContext;
    }

    /**
     * Returns true if debugging is turned on.
     *
     * @return bool
     */
    public function isDebug()
    {
        return $this->debug;
    }

    /**
     * Sets the debug flag.
     *
     * @param bool $debug
     */
    public function setDebug(bool $debug)
    {
        $this->debug = $debug;
    }

    /**
     * Returns true if output should be extensive.
     *
     * @return bool
     */
    public function isVerbose()
    {
        return $this->verbose;
    }

    /**
     * Sets the verbose flag.
     *
     * @param bool $verbose
     */
    public function setVerbose(bool $verbose)
    {
        $this->verbose = $verbose;
    }

    /**
     * Returns true if a preview step is defined.
     *
     * @return bool
     */
    public function isPreview()
    {
        return $this->previewStep !== '';
    }

    /**
     * Returns the preview step.
     *
     * @return string
     */
    public function getPreviewStep()
    {
        return $this->previewStep;
    }

    /**
     * Sets the preview step.
     *
     * @param string $step
     */
    public function setPreviewStep(string $step)
    {
        $this->previewStep = $step;
    }

    /**
     * Gets the preview data.
     *
     * @return mixed
     */
    public function getPreviewData()
    {
        return $this->previewData;
    }

    /**
     * Sets the preview data.
     *
     * @param mixed $previewData
     */
    public function setPreviewData($previewData)
    {
        $this->previewData = $previewData;
    }

    /**
     * Resets the preview data to null.
     *
     * @return void
     */
    public function resetPreviewData()
    {
        $this->previewData = null;
    }

    /**
     * Returns the start time of the current run.
     *
     * @return int
     */
    public function getStartTime(): int
    {
        return $this->startTime;
    }

    /**
     * Sets the start time of the current run.
     *
     * @param int $startTime
     */
    public function setStartTime(int $startTime)
    {
        $this->startTime = $startTime;
    }

    /**
     * Returns the end time of the current run.
     *
     * @return int
     */
    public function getEndTime(): int
    {
        return $this->endTime;
    }

    /**
     * Sets the end time of the current run.
     *
     * @param int $endTime
     */
    public function setEndTime(int $endTime)
    {
        $this->endTime = $endTime;
    }

    /**
     * Sets the test mode flag.
     *
     * Don't use this unless you are really sure that it is what you want.
     * This is meant for unit testing only.
     *
     * @param bool $mode Set to true for test mode
     * @return void
     */
    public function setTestMode(bool $mode)
    {
        $this->testMode = $mode;
    }

    /**
     * Returns the value of the test mode flag.
     *
     * @return bool
     */
    public function isTestMode()
    {
        return $this->testMode;
    }
}
