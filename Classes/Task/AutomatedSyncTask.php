<?php
namespace Cobweb\ExternalImport\Task;

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
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * This class executes Scheduler events for automatic synchronisations of external data
 *
 * @author Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package TYPO3
 * @subpackage tx_externalimport
 */
class AutomatedSyncTask extends AbstractTask
{
    /**
     * @var string Name of the table to synchronize ("all" for all tables)
     */
    public $table;

    /**
     * @var mixed Index of the particular synchronization
     */
    public $index;

    /**
     * Executes the job registered in the Scheduler task
     *
     * @throws \Exception
     * @return boolean
     */
    public function execute()
    {
        $result = true;
        $reportContent = '';

        // Instantiate the import object and call appropriate method depending on command
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        /** @var $importer Importer */
        $importer = $objectManager->get(Importer::class);
        // Get the extension's configuration from the importer object
        $extensionConfiguration = $importer->getExtensionConfiguration();
        // Synchronize all tables
        $globalStatus = 'OK';
        $allMessages = array();
        if ($this->table === 'all') {
            $configurations = $importer->getConfigurationRepository()->findOrderedConfigurations();
            foreach ($configurations as $tableList) {
                foreach ($tableList as $configuration) {
                    $messages = $importer->synchronizeData(
                            $configuration['table'],
                            $configuration['index']
                    );
                    $key = $configuration['table'] . '/' . $configuration['index'];
                    $allMessages[$key] = $messages;
                }
            }
            // If necessary, prepare a report with all messages
            if (!empty($extensionConfiguration['reportEmail'])) {
                foreach ($allMessages as $key => $messages) {
                    list($table, $index) = explode('/', $key);
                    $reportContent .= $importer->reportForTable($table, $index, $messages);
                    $reportContent .= "\n\n";
                    if (count($messages['error']) > 0) {
                        $globalStatus = 'ERROR';
                    } elseif (count($messages['warning']) > 0) {
                        $globalStatus = 'WARNING';
                    }
                }
                // Assemble the subject and send the mail
                $subject = (empty($extensionConfiguration['reportSubject'])) ? '' : $extensionConfiguration['reportSubject'];
                $subject .= ' [' . $globalStatus . '] ' . 'Full synchronization';
                $importer->sendMail($subject, $reportContent);
            }
        } else {
            $messages = $importer->synchronizeData($this->table, $this->index);
            // If necessary, prepare a report with all messages
            if (!empty($extensionConfiguration['reportEmail'])) {
                $reportContent .= $importer->reportForTable($this->table, $this->index, $messages);
                $reportContent .= "\n\n";
                if (count($messages['error']) > 0) {
                    $globalStatus = 'ERROR';
                } elseif (count($messages['warning']) > 0) {
                    $globalStatus = 'WARNING';
                }
                // Assemble the subject and send the mail
                $subject = (empty($extensionConfiguration['reportSubject'])) ? '' : $extensionConfiguration['reportSubject'];
                $subject .= ' [' . $globalStatus . '] ' . 'Synchronization of table ' . $this->table . ', index ' . $this->index;
                $importer->sendMail($subject, $reportContent);
            }
        }
        // If any warning or error happened, throw an exception
        if ($globalStatus !== 'OK') {
            throw new \Exception('One or more errors or warnings happened. Please consult the log.', 1258116760);
        }
        return $result;
    }

    /**
     * This method returns the synchronized table and index as additional information
     *
     * @return    string    Information to display
     */
    public function getAdditionalInformation()
    {
        if ($this->table === 'all') {
            $info = $GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:allTables');
        } else {
            $info = sprintf($GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:tableAndIndex'), $this->table,
                    $this->index);
        }
        return $info;
    }
}
