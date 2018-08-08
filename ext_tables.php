<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

// First register a main module
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'Cobweb.ExternalImport',
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
/** @noinspection TranslationMissingInspection */
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'Cobweb.ExternalImport',
        // Make it a submodule of 'ExternalImport'
        'ExternalImport',
        // Submodule key
        'external_import',
        // Position
        '',
        [
                // An array holding the controller-action-combinations that are accessible
                'DataModule' => 'listSynchronizable, listNonSynchronizable, synchronize, preview, viewConfiguration, newTask, createTask, editTask, updateTask, deleteTask'
        ],
        [
                'access' => 'user,group',
                'icon' => 'EXT:external_import/Resources/Public/Icons/DataModuleIcon.svg',
                'labels' => 'LLL:EXT:external_import/Resources/Private/Language/DataModule.xlf'
        ]
);
// Register the "Log" backend module
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'Cobweb.ExternalImport',
        // Make it a submodule of 'ExternalImport'
        'ExternalImport',
        // Submodule key
        'external_import_log',
        // Position
        '',
        [
                // An array holding the controller-action-combinations that are accessible
                'LogModule' => 'list, get'
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
                'source' => 'EXT:external_import/Resources/Public/Icons/Log.png'
        ]
);
