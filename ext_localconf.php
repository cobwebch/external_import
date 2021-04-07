<?php
// Register handler calls for Scheduler
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\Cobweb\ExternalImport\Task\AutomatedSyncTask::class] = [
        'extension' => 'external_import',
        'title' => 'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:scheduler.title',
        'description' => 'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:scheduler.description',
        'additionalFields' => \Cobweb\ExternalImport\Task\AutomatedSyncAdditionalFieldProvider::class
];

// Set up garbage collection
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask::class]['options']['tables']['tx_externalimport_domain_model_log'] = [
    'dateField' => 'crdate',
    'expirePeriod' => 180,
];

// Add custom permission options for the backend module
$GLOBALS['TYPO3_CONF_VARS']['BE']['customPermOptions'] = [
        'tx_externalimport_bemodule_actions' => [
                'header' => 'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:bemodulePermissions',
                'items' => [
                        'sync' => [
                                'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:bemodulePermissions.runSync',
                                'actions-refresh',
                                'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:bemodulePermissions.runSync.description'
                        ],
                        'scheduler' => [
                                'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:bemodulePermissions.scheduler',
                                'mimetypes-x-tx_scheduler_task_group',
                                'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:bemodulePermissions.scheduler.description'
                        ]
                ]
        ]
];
