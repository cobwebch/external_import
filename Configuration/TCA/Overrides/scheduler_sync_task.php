<?php

use Cobweb\ExternalImport\Processors\ConfigurationsProcessor;
use Cobweb\ExternalImport\Task\SynchronizationTask;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

defined('TYPO3') or die();

// TODO: remove check when dropping support for TYPO3 < 14
$version = VersionNumberUtility::convertVersionStringToArray(VersionNumberUtility::getCurrentTypo3Version());
if ($version['version_main'] >= 14) {
    if (isset($GLOBALS['TCA']['tx_scheduler_task'])) {
        ExtensionManagementUtility::addTCAcolumns(
            'tx_scheduler_task',
            [
                'sync_item' => [
                    'label' => 'external_import.db:tx_scheduler_task.sync_item',
                    'config' => [
                        'type' => 'select',
                        'renderType' => 'selectSingle',
                        'items' => [
                            [
                                'label' => 'external_import.db:tx_scheduler_task.sync_item.all',
                                'value' => 'all',
                            ],
                        ],
                        'itemsProcessors' => [
                            100 => [
                                'class' => ConfigurationsProcessor::class,
                            ],
                        ],
                    ],
                ],
                'sync_storage' => [
                    'label' => 'external_import.db:tx_scheduler_task.sync_storage',
                    'description' => 'external_import.db:tx_scheduler_task.sync_storage.description',
                    'config' => [
                        'type' => 'group',
                        'allowed' => 'pages',
                        'maxitems' => 1,
                    ],
                ],
            ]
        );

        ExtensionManagementUtility::addRecordType(
            item: [
                'label' => 'external_import.db:task.title',
                'description' => 'external_import.db:task.description',
                'value' => SynchronizationTask::class,
                'icon' => 'tx_external_import-task',
                'group' => 'external_import',
            ],
            showItemList: '
            --div--;core.form.tabs:general,
                tasktype,
                task_group,
                description,
                sync_item,
                sync_storage,
            --div--;scheduler.messages:scheduler.form.palettes.timing,
                --palette--;;execution,
            --div--;core.form.tabs:access,
                disable,
            --div--;core.form.tabs:extended,
        ',
            table: 'tx_scheduler_task'
        );
    }
}
