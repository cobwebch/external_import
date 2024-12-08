<?php

use Cobweb\ExternalImport\Controller\DataModuleController;
use Cobweb\ExternalImport\Controller\LogModuleController;

return [
    'ExternalImport' => [
        'access' => '',
        'workspaces' => 'live',
        'iconIdentifier' => 'tx_externalimport-main-module',
        'path' => '/module/externalimport',
        'labels' => 'LLL:EXT:external_import/Resources/Private/Language/MainModule.xlf',
        'extensionName' => 'external_import',
    ],
    'ExternalImportData' => [
        'parent' => 'ExternalImport',
        'access' => 'user',
        'workspaces' => 'live',
        'iconIdentifier' => 'tx_externalimport-data-module',
        'path' => '/module/externalimport/data',
        'labels' => 'LLL:EXT:external_import/Resources/Private/Language/DataModule.xlf',
        'extensionName' => 'external_import',
        'controllerActions' => [
            DataModuleController::class => [
                'listSynchronizable',
                'listNonSynchronizable',
                'viewConfiguration',
                'synchronize',
                'preview',
                'downloadPreview',
                'newTask',
                'createTask',
                'updateTask',
                'editTask',
                'deleteTask',
            ],
        ],
    ],
    'ExternalImportLog' => [
        'parent' => 'ExternalImport',
        'access' => 'user',
        'workspaces' => 'live',
        'iconIdentifier' => 'tx_externalimport-log-module',
        'path' => '/module/externalimport/log',
        'labels' => 'LLL:EXT:external_import/Resources/Private/Language/LogModule.xlf',
        'extensionName' => 'external_import',
        'controllerActions' => [
            LogModuleController::class => [
                'list',
            ],
        ],
    ],
];
