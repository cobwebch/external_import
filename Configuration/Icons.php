<?php

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    'tx_externalimport-main-module' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:external_import/Resources/Public/Icons/MainModuleIcon.svg',
    ],
    'tx_externalimport-data-module' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:external_import/Resources/Public/Icons/DataModuleIcon.svg',
    ],
    'tx_externalimport-log-module' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:external_import/Resources/Public/Icons/LogModuleIcon.svg',
    ],
    'tx_external_import-log' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:external_import/Resources/Public/Icons/Log.svg',
    ],
    'tx_external_import-reaction-import' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:external_import/Resources/Public/Icons/Reaction.svg',
    ],
];
