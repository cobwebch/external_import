<?php

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

// TODO: remove old icon when dropping compatibility with TYPO3 13
$version = VersionNumberUtility::convertVersionStringToArray(VersionNumberUtility::getCurrentTypo3Version());
if ($version['version_main'] >= 14) {
    $dataModuleIcon = 'EXT:external_import/Resources/Public/Icons/module-externalimport-data.svg';
    $logModuleIcon = 'EXT:external_import/Resources/Public/Icons/module-externalimport-log.svg';
} else {
    $dataModuleIcon = 'EXT:external_import/Resources/Public/Icons/DataModuleIcon.svg';
    $logModuleIcon = 'EXT:external_import/Resources/Public/Icons/LogModuleIcon.svg';
}

return [
    'tx_externalimport-main-module' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:external_import/Resources/Public/Icons/MainModuleIcon.svg',
    ],
    'tx_externalimport-data-module' => [
        'provider' => SvgIconProvider::class,
        'source' => $dataModuleIcon,
    ],
    'tx_externalimport-log-module' => [
        'provider' => SvgIconProvider::class,
        'source' => $logModuleIcon,
    ],
    'tx_external_import-log' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:external_import/Resources/Public/Icons/Log.svg',
    ],
    'tx_external_import-reaction-import' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:external_import/Resources/Public/Icons/Reaction.svg',
    ],
    'tx_external_import-task' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:external_import/Resources/Public/Icons/Task.svg',
    ],
];
