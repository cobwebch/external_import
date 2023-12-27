<?php

// TODO: remove when TYPO3 11 compatibility is dropped
// First register a main module
use Cobweb\ExternalImport\Controller\DataModuleController;
use Cobweb\ExternalImport\Controller\LogModuleController;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

ExtensionUtility::registerModule(
    'ExternalImport',
    // New main module
    'ExternalImport',
    '',
    '',
    [],
    [
        'access' => '',
        'icon' => 'EXT:external_import/Resources/Public/Icons/MainModuleIcon.svg',
        'labels' => 'LLL:EXT:external_import/Resources/Private/Language/MainModule.xlf'
    ]
);
// Register the "Data Import" backend module
ExtensionUtility::registerModule(
    'ExternalImport',
    // Make it a submodule of 'ExternalImport'
    'ExternalImport',
    // Submodule key
    'external_import',
    // Position
    '',
    [
        DataModuleController::class => 'listSynchronizable, listNonSynchronizable, synchronize, preview, downloadPreview, viewConfiguration, newTask, createTask, editTask, updateTask, deleteTask'
    ],
    [
        'access' => 'user,group',
        'icon' => 'EXT:external_import/Resources/Public/Icons/DataModuleIcon.svg',
        'labels' => 'LLL:EXT:external_import/Resources/Private/Language/DataModule.xlf'
    ]
);
// Register the "Log" backend module
ExtensionUtility::registerModule(
    'ExternalImport',
    // Make it a submodule of 'ExternalImport'
    'ExternalImport',
    // Submodule key
    'external_import_log',
    // Position
    '',
    [
        LogModuleController::class => 'list, get'
    ],
    [
        'access' => 'user,group',
        'icon' => 'EXT:external_import/Resources/Public/Icons/LogModuleIcon.svg',
        'labels' => 'LLL:EXT:external_import/Resources/Private/Language/LogModule.xlf'
    ]
);
