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
use Cobweb\ExternalImport\Importer;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * This class performs various reporting actions after a data import has taken place.
 *
 * @package Cobweb\ExternalImport\Utility
 */
class ReportingUtility
{
    /**
     * @var Importer Back-reference to the calling instance
     */
    protected $importer;

    /**
     * @var array Extension configuration
     */
    protected $extensionConfiguration = array();

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

    public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function injectLogRepository(\Cobweb\ExternalImport\Domain\Repository\LogRepository $logRepository)
    {
        $this->logRepository = $logRepository;
    }

    public function injectPersistenceManager(\TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager $persistenceManager)
    {
        $this->persistenceManager = $persistenceManager;
    }

    /**
     * Sets a back-reference to the Importer object.
     *
     * @param Importer $importer
     * @return void
     */
    public function setImporter(Importer $importer)
    {
        $this->importer = $importer;
        $this->extensionConfiguration = $importer->getExtensionConfiguration();
    }

    /**
     * Stores the messages to the devLog.
     *
     * @return void
     */
    public function writeToDevLog()
    {
        if ($this->extensionConfiguration['debug']) {
            $messages = $this->importer->getMessages();

            // Define a global severity based on the highest issue level reported
            $severity = -1;
            if (count($messages[FlashMessage::ERROR]) > 0) {
                $severity = 3;
            } elseif (count($messages[FlashMessage::WARNING]) > 0) {
                $severity = 2;
            }

            // Log all the messages in one go
            GeneralUtility::devLog(
                    LocalizationUtility::translate(
                            'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:sync_table',
                            'external_import',
                            array(
                                    $this->importer->getExternalConfiguration()->getTable()
                            )
                    ),
                    'external_import',
                    $severity,
                    $messages
            );
        }
    }

    /**
     * Stores the messages to the external_import log.
     *
     * @return void
     */
    public function writeToLog()
    {
        $messages = $this->importer->getMessages();
        foreach ($messages as $status => $messageList) {
            foreach ($messageList as $message) {
                /** @var Log $logEntry */
                $logEntry = $this->objectManager->get(Log::class);
                $logEntry->setPid($this->extensionConfiguration['logStorage']);
                $logEntry->setStatus($status);
                $logEntry->setCrdate(
                        new \DateTime('@' . $GLOBALS['EXEC_TIME'])
                );
                $logEntry->setCruserId(
                        (isset($GLOBALS['BE_USER']->user['uid'])) ? $GLOBALS['BE_USER']->user['uid'] : 0
                );
                $logEntry->setConfiguration(
                        $this->importer->getExternalConfiguration()->getTable() . ' / ' . $this->importer->getExternalConfiguration()->getIndex()
                );
                $logEntry->setMessage($message);
                $this->logRepository->add($logEntry);
            }
        }
        // Make sure the entries are persisted (this will not happen automatically
        // when called from the command line)
        $this->persistenceManager->persistAll();
    }
}