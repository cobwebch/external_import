<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

// Register handler calls for Scheduler
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\Cobweb\ExternalImport\Task\AutomatedSyncTask::class] = array(
        'extension' => 'external_import',
        'title' => 'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:scheduler.title',
        'description' => 'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:scheduler.description',
        'additionalFields' => \Cobweb\ExternalImport\Task\AutomatedSyncAdditionalFieldProvider::class
);
