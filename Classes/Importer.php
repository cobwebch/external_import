<?php

declare(strict_types=1);

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
use Cobweb\ExternalImport\Domain\Model\Configuration;
use Cobweb\ExternalImport\Domain\Model\Data;
use Cobweb\ExternalImport\Domain\Repository\ConfigurationRepository;
use Cobweb\ExternalImport\Domain\Repository\TemporaryKeyRepository;
use Cobweb\ExternalImport\Domain\Repository\UidRepository;
use Cobweb\ExternalImport\Exception\InvalidPreviewStepException;
use Cobweb\ExternalImport\Exception\NoConfigurationException;
use Cobweb\ExternalImport\Step\AbstractStep;
use Cobweb\ExternalImport\Utility\ReportingUtility;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * This class drives the import process by moving from step to step, aborting when necessary and triggering the reporting.
 */
class Importer implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const DEFAULT_PRIORITY = 1000;

    /**
     * @var array Extension configuration
     */
    protected array $extensionConfiguration = [];

    /**
     * @var array List of result messages
     */
    protected array $messages = [];

    /**
     * @var ConfigurationRepository
     */
    protected ConfigurationRepository $configurationRepository;

    /**
     * @var Configuration|null Full External Import configuration
     */
    protected ?Configuration $externalConfiguration = null;

    /**
     * @var ReportingUtility Utility for reporting after import
     */
    protected ReportingUtility $reportingUtility;

    /**
     * @var UidRepository
     */
    protected UidRepository $uidRepository;

    /**
     * @var TemporaryKeyRepository
     */
    protected TemporaryKeyRepository $temporaryKeyRepository;

    /**
     * @var int|null Externally enforced id of a page where the records should be stored (overrides "pid", used for testing)
     */
    protected ?int $forcedStoragePid = null;

    /**
     * @var string Context in which the import run is executed
     */
    protected string $context = 'manual';

    protected ?AbstractCallContext $callContext = null;

    /**
     * @var bool Whether debugging is turned on or off
     */
    protected bool $debug = false;

    /**
     * @var bool Whether the output should be verbose or not (currently only affects calls made via the command line)
     */
    protected bool $verbose = false;

    /**
     * @var bool True if the process has been aborted by one of the steps
     */
    protected bool $processAborted = false;

    /**
     * @var string Name of the Step class at which the process should stop when running in preview mode
     */
    protected string $previewStep = '';

    /**
     * @var string|array Data to be returned as preview data
     */
    protected $previewData;

    // Data object from the current step
    protected ?Data $currentData = null;

    /**
     * @var bool Set to true to trigger testing mode (used only for unit testing)
     */
    protected bool $testMode = false;

    /**
     * @var int Start time of the current run
     */
    protected int $startTime = 0;

    /**
     * @var int End time of the current run
     */
    protected int $endTime = 0;

    /**
     * @var array List of default steps for the synchronize data process
     */
    public const SYNCHRONYZE_DATA_STEPS = [
            Step\CheckPermissionsStep::class,
            Step\ValidateConfigurationStep::class,
            Step\ValidateConnectorStep::class,
            Step\ReadDataStep::class,
            Step\HandleDataStep::class,
            Step\ValidateDataStep::class,
            Step\TransformDataStep::class,
            Step\StoreDataStep::class,
            Step\ClearCacheStep::class,
            Step\ConnectorCallbackStep::class,
            Step\ReportStep::class,
    ];

    /**
     * @var array List of default steps for the import data process
     */
    public const IMPORT_DATA_STEPS = [
            Step\CheckPermissionsStep::class,
            Step\ValidateConfigurationStep::class,
            Step\HandleDataStep::class,
            Step\ValidateDataStep::class,
            Step\TransformDataStep::class,
            Step\StoreDataStep::class,
            Step\ClearCacheStep::class,
            Step\ReportStep::class,
    ];

    /**
     * Importer constructor.
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException
     */
    public function __construct(
        ConfigurationRepository $configurationRepository,
        ReportingUtility $reportingUtility,
        UidRepository $uidRepository,
        TemporaryKeyRepository $temporaryKeyRepository,
        ExtensionConfiguration $extensionConfiguration
    ) {
        $this->configurationRepository = $configurationRepository;
        $this->reportingUtility = $reportingUtility;
        $this->uidRepository = $uidRepository;
        $this->temporaryKeyRepository = $temporaryKeyRepository;

        $this->extensionConfiguration = $extensionConfiguration->get(
            'external_import'
        );
        $this->setDebug((bool)$this->extensionConfiguration['debug']);

        // Force PHP limit execution time if set
        if (isset($this->extensionConfiguration['timelimit']) && ((int)$this->extensionConfiguration['timelimit'] > -1)) {
            set_time_limit((int)$this->extensionConfiguration['timelimit']);
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

    /**
     * Stores information about the synchronized table into member variables.
     *
     * @param string $table Name of the table to synchronise
     * @param mixed $index Index of the synchronisation configuration to use
     * @param array|null $defaultSteps List of default steps (if null will be guessed by the Configuration object)
     * @throws NoConfigurationException
     */
    protected function initialize(string $table, $index, ?array $defaultSteps): void
    {
        // Assign back-reference to reporting utility
        $this->reportingUtility->setImporter($this);

        // Fetch the requested configuration
        $this->externalConfiguration = $this->configurationRepository->findConfigurationObject(
            $table,
            $index,
            $defaultSteps
        );
        if ($this->forcedStoragePid !== null) {
            $this->externalConfiguration->setStoragePid($this->forcedStoragePid);
        }
        // Initialize existing uids list
        $this->uidRepository->setConfiguration($this->externalConfiguration);
        $this->uidRepository->resetExistingUids();
        $this->uidRepository->resetCurrentPids();
    }

    /**
     * Calls on the distant data source and synchronizes the data into the TYPO3 database.
     *
     * @param string $table Name of the table to synchronise
     * @param mixed $index Index of the synchronisation configuration to use
     * @return array List of error or success messages
     */
    public function synchronize(string $table, $index): array
    {
        // Initialize message array
        $this->resetMessages();
        // Reset abort status
        $this->setProcessAborted(false);
        try {
            $this->initialize(
                $table,
                $index,
                self::SYNCHRONYZE_DATA_STEPS
            );

            $this->currentData = GeneralUtility::makeInstance(Data::class);
            $this->runSteps();
        } catch (InvalidPreviewStepException $e) {
            $this->addMessage(
                LocalizationUtility::translate(
                    'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:wrongPreviewStep',
                    'external_import',
                    [
                        $e->getMessage(),
                    ]
                ),
                ContextualFeedbackSeverity::WARNING->value
            );
        } catch (NoConfigurationException $e) {
            $this->addMessage(
                LocalizationUtility::translate(
                    'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:noConfigurationFound',
                    'external_import',
                    [
                        $table,
                        $index,
                        $e->getMessage(),
                        $e->getCode(),
                    ]
                )
            );
        } catch (\Exception $e) {
            $this->addMessage(
                LocalizationUtility::translate(
                    'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:generalProcessError',
                    'external_import',
                    [
                        $e->getMessage(),
                        $e->getCode(),
                    ]
                )
            );
        }

        return $this->messages;
    }

    /**
     * Receives raw data from some external source, transforms it and stores it into the TYPO3 database.
     *
     * @param string $table Name of the table to import into
     * @param mixed $index Index of the synchronisation configuration to use
     * @param mixed $rawData Data in the format provided by the external source (XML string, PHP array, etc.)
     * @return array List of error or success messages
     */
    public function import(string $table, $index, $rawData): array
    {
        // Initialize message array
        $this->resetMessages();
        // Reset abort status
        $this->setProcessAborted(false);
        // Set context to API no matter what
        $this->setContext('api');
        try {
            $this->initialize(
                $table,
                $index,
                self::IMPORT_DATA_STEPS
            );

            // Initialize the Data object with the raw data
            $this->currentData = GeneralUtility::makeInstance(Data::class);
            $this->currentData->setRawData($rawData);
            $this->runSteps();
        } catch (InvalidPreviewStepException $e) {
            $this->addMessage(
                LocalizationUtility::translate(
                    'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:wrongPreviewStep',
                    'external_import',
                    [
                        $e->getMessage(),
                    ]
                )
            );
        } catch (NoConfigurationException $e) {
            $this->addMessage(
                LocalizationUtility::translate(
                    'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:noConfigurationFound',
                    'external_import',
                    [
                        $table,
                        $index,
                        $e->getMessage(),
                        $e->getCode(),
                    ]
                )
            );
        } catch (\Exception $e) {
            $this->addMessage(
                LocalizationUtility::translate(
                    'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:generalProcessError',
                    'external_import',
                    [
                        $e->getMessage(),
                        $e->getCode(),
                    ]
                )
            );
        }

        return $this->messages;
    }

    /**
     * Runs the process through the defined steps.
     *
     * @throws Exception\InvalidPreviewStepException
     */
    public function runSteps(): void
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
            /** @var AbstractStep $step */
            $step = GeneralUtility::makeInstance($stepClass);
            if (!$this->isProcessAborted() || ($this->isProcessAborted() && $step->isExecuteDespiteAbort())) {
                $step->setImporter($this);
                $step->setData($this->currentData);
                if ($this->externalConfiguration->hasParametersForStep($stepClass)) {
                    $step->setParameters($this->externalConfiguration->getParametersForStep($stepClass));
                }
                $step->run();
                // Abort process if step required it
                if ($step->isAbortFlag()) {
                    $this->setProcessAborted(true);
                    // Report about aborting
                    $this->addMessage(
                        LocalizationUtility::translate(
                            'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:importAborted',
                            'external_import'
                        ),
                        ContextualFeedbackSeverity::WARNING->value
                    );
                }
                $this->currentData = $step->getData();
                // We set the end time after each step, so that we still capture a certain duration even if one step crashes unexpectedly
                $this->setEndTime(time());
            }
            // If preview is on and the step is matched, exit process
            // NOTE: this happens after abort, as aborting means there's an underlying problem, which should be reported no matter what
            if ($this->isPreview() && $this->getPreviewStep() === $stepClass) {
                if ($step->hasDownloadableData()) {
                    $this->currentData->setDownloadable(true);
                }
                break;
            }
        }
    }

    // Getters and setters

    /**
     * Returns the model object containing the whole External Import configuration.
     *
     * @return Configuration|null
     */
    public function getExternalConfiguration(): ?Domain\Model\Configuration
    {
        return $this->externalConfiguration;
    }

    /**
     * Set the External Import configuration.
     *
     * This is meant for testing. Do not use unless you really know what you're doing.
     *
     * @param Configuration $configuration
     */
    public function setExternalConfiguration(Configuration $configuration): void
    {
        $this->externalConfiguration = $configuration;
    }

    /**
     * Returns the instance of the configuration repository.
     *
     * @return ConfigurationRepository
     */
    public function getConfigurationRepository(): ConfigurationRepository
    {
        return $this->configurationRepository;
    }

    /**
     * Returns the extension's configuration.
     *
     * It is used to avoid reading it multiple times from the various components of this extension.
     *
     * @return array The extension's configuration
     */
    public function getExtensionConfiguration(): array
    {
        return $this->extensionConfiguration;
    }

    /**
     * Writes debug messages, depending on debug flag.
     *
     * The output varies depending on TYPO3 version and call context.
     *
     * @param string $message The debug message
     * @param int $severity The severity of the issue
     * @param null $data Data associated with the debugging information
     */
    public function debug(string $message, int $severity = 0, $data = null): void
    {
        if ($this->isDebug()) {
            $data = is_array($data) ? $data : [$data];
            $message = sprintf(
                '[External Import - %s - %s] %s',
                $this->externalConfiguration->getTable(),
                $this->externalConfiguration->getIndex(),
                $message
            );
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

    /**
     * Adds a message to the message queue that will be returned
     * when the synchronization is complete.
     *
     * @param string $text The message itself
     * @param int $status Status of the message. Expected is "success", "warning" or "error"
     */
    public function addMessage(string $text, int $status = ContextualFeedbackSeverity::ERROR->value): void
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
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * Empties the internal message queue.
     */
    public function resetMessages(): void
    {
        $this->messages = [
            ContextualFeedbackSeverity::ERROR->value => [],
            ContextualFeedbackSeverity::WARNING->value => [],
            ContextualFeedbackSeverity::OK->value => [],
        ];
    }

    /**
     * Returns the current instance of the reporting utility.
     *
     * @return ReportingUtility
     */
    public function getReportingUtility(): ReportingUtility
    {
        return $this->reportingUtility;
    }

    /**
     * Returns the uid repository.
     *
     * @return UidRepository
     */
    public function getUidRepository(): UidRepository
    {
        return $this->uidRepository;
    }

    /**
     * Returns the temporary key repository.
     *
     * @return TemporaryKeyRepository
     */
    public function getTemporaryKeyRepository(): TemporaryKeyRepository
    {
        return $this->temporaryKeyRepository;
    }

    /**
     * Forces the storage pid for imported records.
     *
     * @param mixed $pid
     */
    public function setForcedStoragePid($pid): void
    {
        $this->forcedStoragePid = (int)$pid;
    }

    /**
     * Returns the current execution context.
     *
     * @return string
     */
    public function getContext(): string
    {
        return $this->context;
    }

    /**
     * Sets the execution context.
     *
     * @param string $context
     */
    public function setContext(string $context): void
    {
        $this->context = $context;
    }

    /**
     * @return AbstractCallContext
     */
    public function getCallContext(): AbstractCallContext
    {
        return $this->callContext;
    }

    /**
     * @param AbstractCallContext $callContext
     */
    public function setCallContext(AbstractCallContext $callContext): void
    {
        $this->callContext = $callContext;
    }

    /**
     * Returns true if debugging is turned on.
     *
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->debug;
    }

    /**
     * Sets the debug flag.
     *
     * @param bool $debug
     */
    public function setDebug(bool $debug): void
    {
        $this->debug = $debug;
    }

    /**
     * Returns true if output should be extensive.
     *
     * @return bool
     */
    public function isVerbose(): bool
    {
        return $this->verbose;
    }

    /**
     * Sets the verbose flag.
     *
     * @param bool $verbose
     */
    public function setVerbose(bool $verbose): void
    {
        $this->verbose = $verbose;
    }

    /**
     * @return bool
     */
    public function isProcessAborted(): bool
    {
        return $this->processAborted;
    }

    /**
     * @param bool $processAborted
     */
    public function setProcessAborted(bool $processAborted): void
    {
        $this->processAborted = $processAborted;
    }

    /**
     * Returns true if a preview step is defined.
     *
     * @return bool
     */
    public function isPreview(): bool
    {
        return $this->previewStep !== '';
    }

    /**
     * Returns the preview step.
     *
     * @return string
     */
    public function getPreviewStep(): string
    {
        return $this->previewStep;
    }

    /**
     * Sets the preview step.
     *
     * @param string $step
     */
    public function setPreviewStep(string $step): void
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
     * Return the current value of the data object
     *
     * @return Data|null
     */
    public function getCurrentData(): ?Data
    {
        return $this->currentData;
    }

    /**
     * Sets the preview data.
     *
     * @param mixed $previewData
     */
    public function setPreviewData($previewData): void
    {
        $this->previewData = $previewData;
    }

    /**
     * Resets the preview data to null.
     */
    public function resetPreviewData(): void
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
    public function setStartTime(int $startTime): void
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
    public function setEndTime(int $endTime): void
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
     */
    public function setTestMode(bool $mode): void
    {
        $this->testMode = $mode;
        // Cascade the test mode to the temporary key repository (if initialized)
        if ($this->temporaryKeyRepository) {
            $this->temporaryKeyRepository->setTestMode($mode);
        }
    }

    /**
     * Returns the value of the test mode flag.
     *
     * @return bool
     */
    public function isTestMode(): bool
    {
        return $this->testMode;
    }
}
