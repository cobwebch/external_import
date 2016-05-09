<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

// Load the module only in the BE context
if (TYPO3_MODE === 'BE') {
    // First register a main module
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
            'Cobweb.ExternalImport',
            // New main module
            'ExternalImport',
            '',
            '',
            array(),
            array(
                    'access' => '',
                    'icon' => 'EXT:' . $_EXTKEY . '/Resources/Public/Images/MainModuleIcon.svg',
                    'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/MainModule.xlf'
            )
    );
    // Now register the backend module
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
            'Cobweb.ExternalImport',
            // Make it a submodule of 'ExternalImport'
            'ExternalImport',
            // Submodule key
            'external_import',
            // Position
            '',
            array(
                    // An array holding the controller-action-combinations that are accessible
                    'Module' => 'listSynchronizable, listNonSynchronizable, synchronize, viewConfiguration, newTask, createTask, editTask, updateTask, deleteTask'
            ),
            array(
                    'access' => 'user,group',
                    'icon' => 'EXT:' . $_EXTKEY . '/Resources/Public/Images/SubModuleIcon.svg',
                    'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/SubModule.xlf'
            )
    );
}
