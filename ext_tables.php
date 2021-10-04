<?php

// First register a main module
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
    'ExternalImport',
    // New main module
    'ExternalImport',
    '',
    '',
    [],
    [
        'access' => 'user,group',
        'icon' => 'EXT:external_import/Resources/Public/Icons/MainModuleIcon.svg',
        'labels' => 'LLL:EXT:external_import/Resources/Private/Language/MainModule.xlf'
    ]
);
// Register the "Data Import" backend module
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
    'ExternalImport',
    // Make it a submodule of 'ExternalImport'
    'ExternalImport',
    // Submodule key
    'external_import',
    // Position
    '',
    [
        \Cobweb\ExternalImport\Controller\DataModuleController::class => 'listSynchronizable, listNonSynchronizable, synchronize, preview, viewConfiguration, newTask, createTask, editTask, updateTask, deleteTask'
    ],
    [
        'access' => 'user,group',
        'icon' => 'EXT:external_import/Resources/Public/Icons/DataModuleIcon.svg',
        'labels' => 'LLL:EXT:external_import/Resources/Private/Language/DataModule.xlf'
    ]
);
// Register the "Log" backend module
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
    'ExternalImport',
    // Make it a submodule of 'ExternalImport'
    'ExternalImport',
    // Submodule key
    'external_import_log',
    // Position
    '',
    [
        \Cobweb\ExternalImport\Controller\LogModuleController::class => 'list, get'
    ],
    [
        'access' => 'user,group',
        'icon' => 'EXT:external_import/Resources/Public/Icons/LogModuleIcon.svg',
        'labels' => 'LLL:EXT:external_import/Resources/Private/Language/LogModule.xlf'
    ]
);

// Register sprite icons for new tables
/** @var \TYPO3\CMS\Core\Imaging\IconRegistry $iconRegistry */
$iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
$iconRegistry->registerIcon(
    'tx_external_import-log',
    \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
    [
        'source' => 'EXT:external_import/Resources/Public/Icons/Log.svg'
    ]
);
$iconRegistry->registerIcon(
    'tx_external_import-loader',
    \TYPO3\CMS\Core\Imaging\IconProvider\FontawesomeIconProvider::class,
    [
        'name' => 'spinner',
        'spinning' => true
    ]
);
$iconRegistry->registerIcon(
    'tx_external_import-process-arrow',
    \TYPO3\CMS\Core\Imaging\IconProvider\FontawesomeIconProvider::class,
    [
        'name' => 'arrow-circle-right'
    ]
);
