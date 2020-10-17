<?php

namespace Cobweb\ExternalImport\Utility;

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

use Cobweb\ExternalImport\Domain\Model\Log;
use Cobweb\ExternalImport\Domain\Repository\LogRepository;
use Cobweb\ExternalImport\Exception\UnknownReportingKeyException;
use Cobweb\ExternalImport\Importer;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

/**
 * This class performs various reporting actions after a data import has taken place.
 *
 * @package Cobweb\ExternalImport\Utility
 */
class ReportingUtility implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var Importer Back-reference to the calling instance
     */
    protected $importer;

    /**
     * @var array Extension configuration
     */
    protected $extensionConfiguration = [];

    /**
     * @var array List of arbitrary values reported by different steps in the process
     */
    protected $reportingValues = [];

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var LogRepository
     */
    protected $logRepository;

    /**
     * @var PersistenceManager
     */
    protected $persistenceManager;

    /**
     * @var Context
     */
    protected $context;

    public function injectObjectManager(ObjectManager $objectManager): void
    {
        $this->objectManager = $objectManager;
    }

    public function injectLogRepository(LogRepository $logRepository): void
    {
        $this->logRepository = $logRepository;
    }

    public function injectPersistenceManager(PersistenceManager $persistenceManager): void
    {
        $this->persistenceManager = $persistenceManager;
    }

    public function injectContext(Context $context): void
    {
        $this->context = $context;
    }

    /**
     * Sets a back-reference to the Importer object.
     *
     * @param Importer $importer
     * @return void
     */
    public function setImporter(Importer $importer): void
    {
        $this->importer = $importer;
        $this->extensionConfiguration = $importer->getExtensionConfiguration();
    }

    /**
     * Stores the messages to the external_import log.
     *
     * @return void
     */
    public function writeToLog(): void
    {
        // Don't log in preview mode
        if (!$this->importer->isPreview()) {
            $messages = $this->importer->getMessages();
            $importContext = $this->importer->getContext();
            $now = new \DateTime();
            $now->setTimestamp($this->context->getPropertyFromAspect('date', 'timestamp'));

            try {
                $currentUser = $this->context->getPropertyFromAspect('backend.user', 'id');
            } catch (AspectNotFoundException $e) {
                $currentUser = 0;
            }
            foreach ($messages as $status => $messageList) {
                foreach ($messageList as $message) {
                    /** @var Log $logEntry */
                    $logEntry = $this->objectManager->get(Log::class);
                    $logEntry->setPid($this->extensionConfiguration['logStorage']);
                    $logEntry->setStatus($status);
                    $logEntry->setCrdate($now);
                    $logEntry->setCruserId($currentUser);
                    $logEntry->setConfiguration(
                            $this->importer->getExternalConfiguration()->getTable() . ' / ' . $this->importer->getExternalConfiguration()->getIndex()
                    );
                    $logEntry->setContext($importContext);
                    $logEntry->setMessage($message);
                    $logEntry->setDuration(
                            $this->importer->getEndTime() - $this->importer->getStartTime()
                    );
                    try {
                        $this->logRepository->add($logEntry);
                    }
                    catch (\Exception $e) {
                        // Nothing to do
                    }
                }
            }
            // Make sure the entries are persisted (this will not happen automatically
            // when called from the command line)
            $this->persistenceManager->persistAll();
        }
    }

    /**
     * Assembles a synchronization report for a given table/index.
     *
     * @param string $table Name of the table
     * @param integer $index Number of the synchronisation configuration
     * @param array $messages List of messages for the given table
     * @return string Formatted text of the report
     */
    public function reportForTable($table, $index, $messages): string
    {
        $languageObject = $this->getLanguageObject();
        $report = sprintf(
                        $languageObject->sL('LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:synchronizeTableX'),
                        $table,
                        $index
                ) . "\n";
        foreach ($messages as $type => $messageList) {
            $report .= $languageObject->sL('LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:label.' . $type) . "\n";
            if (count($messageList) === 0) {
                $report .= "\t" . $languageObject->sL('LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:no.' . $type) . "\n";
            } else {
                foreach ($messageList as $aMessage) {
                    $report .= "\t- " . $aMessage . "\n";
                }
            }
        }
        $report .= "\n\n";
        return $report;
    }

    /**
     * Sends a reporting mail to the configured e-mail address.
     *
     * @param string $subject Subject of the mail
     * @param string $body Text body of the mail
     * @return void
     */
    public function sendMail($subject, $body): void
    {
        $result = 0;
        // Get default mail configuration for sending the report
        $senderMail = $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'] ?? '';
        $senderName = $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'] ?? $senderMail;
        // If no mail could be found, avoid sending the mail
        // The message will be logged as an error
        if (empty($senderMail)) {
            $message = 'No sender mail defined. Please define $GLOBALS[\'TYPO3_CONF_VARS\'][\'MAIL\'][\'defaultMailFromAddress\'] and $GLOBALS[\'TYPO3_CONF_VARS\'][\'MAIL\'][\'defaultMailFromName\'].';

        // Proceed with sending the mail
        } else {
            // Instantiate and initialize the mail object
            /** @var $mailObject MailMessage */
            $mailObject = GeneralUtility::makeInstance(MailMessage::class);
            try {
                $sender = [
                        $senderMail => $senderName
                ];
                $mailObject->setFrom($sender);
                $mailObject->setReplyTo($sender);
                $mailObject->setTo(
                        [
                                $this->extensionConfiguration['reportEmail']
                        ]
                );
                $mailObject->setSubject($subject);
                // Adapt to changing mail API
                // TODO: remove check once compat with v9 is droppped
                if (VersionNumberUtility::convertVersionNumberToInteger(VersionNumberUtility::getNumericTypo3Version()) > 10000000) {
                    $mailObject->text($body);
                } else {
                    $mailObject->setBody($body);
                }
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
            $this->logger->error($comment);
        }
    }

    /**
     * @param string $step Name of the step (class)
     * @param string $key Name of the key
     * @param mixed $value Value to store
     * @return void
     */
    public function setValueForStep(string $step, string $key, $value): void
    {
        if (!array_key_exists($step, $this->reportingValues)) {
            $this->reportingValues[$step] = [];
        }
        $this->reportingValues[$step][$key] = $value;
    }

    /**
     * @param string $step Name of the step (class)
     * @param string $key Name of the key
     * @return mixed
     * @throws UnknownReportingKeyException
     */
    public function getValueForStep(string $step, string $key)
    {
        if (isset($this->reportingValues[$step][$key])) {
            return $this->reportingValues[$step][$key];
        }
        throw new UnknownReportingKeyException(
                sprintf(
                        'No value found for step "%1$s" and key "%2$s"',
                        $step,
                        $key
                ),
                1530635849

        );
    }

    /**
     * Returns the global language object.
     *
     * @return LanguageService
     */
    protected function getLanguageObject(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}