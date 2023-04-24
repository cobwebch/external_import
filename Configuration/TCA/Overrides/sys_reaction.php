<?php

use Cobweb\ExternalImport\Reaction\ImportReaction;
use Cobweb\ExternalImport\Utility\CompatibilityUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

if (CompatibilityUtility::isV12()) {
    ExtensionManagementUtility::addTcaSelectItem(
        'sys_reaction',
        'reaction_type',
        [
            'label' => ImportReaction::getDescription(),
            'value' => ImportReaction::getType(),
            'icon' => ImportReaction::getIconIdentifier(),
        ],
    );
    $GLOBALS['TCA']['sys_reaction']['ctrl']['typeicon_classes'][ImportReaction::getType()] = ImportReaction::getIconIdentifier();

    ExtensionManagementUtility::addTCAcolumns(
        'sys_reaction',
        [
            'external_import_configuration' => [
                'label' => 'LLL:EXT:external_import/Resources/Private/Language/locallang_db.xlf:sys_reaction.external_import_configuration',
                'description' => 'LLL:EXT:external_import/Resources/Private/Language/locallang_db.xlf:sys_reaction.external_import_configuration.description',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'items' => [
                        [
                            'label' => '',
                            'value' => '',
                        ],
                    ],
                    'itemsProcFunc' => \Cobweb\ExternalImport\UserFunction\ConfigurationItems::class . '->listConfigurationItems',
                ],
            ],
        ],
    );

    $GLOBALS['TCA']['sys_reaction']['palettes']['externalImport'] = [
        'label' => 'LLL:EXT:external_import/Resources/Private/Language/locallang_db.xlf:sys_reaction.palette.additional',
        'showitem' => 'external_import_configuration, --linebreak--, impersonate_user',
    ];

    $GLOBALS['TCA']['sys_reaction']['types'][ImportReaction::getType()] = [
        'showitem' => '
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
            --palette--;;config, --palette--;;externalImport,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
            --palette--;;access',
        'columnsOverrides' => [
            'impersonate_user' => [
                'config' => [
                    'minitems' => 1,
                ],
            ],
        ],
    ];
}
