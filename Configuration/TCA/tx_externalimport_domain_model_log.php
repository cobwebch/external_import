<?php

return [
        'ctrl' => [
                'title' => 'LLL:EXT:external_import/Resources/Private/Language/locallang_db.xlf:tx_externalimport_domain_model_log',
                'label' => 'configuration',
                'crdate' => 'crdate',
                'cruser_id' => 'cruser_id',
                'rootLevel' => -1,
                'default_sortby' => 'ORDER BY crdate DESC',
                'searchFields' => 'configuration',
                'typeicon_classes' => [
                        'default' => 'tx_external_import-log'
                ]
        ],
        'interface' => [
                'showRecordFieldList' => 'status, crdate, cruser_id, configuration, message'
        ],
        'columns' => [
                'status' => [
                        'exclude' => 0,
                        'label' => 'LLL:EXT:external_import/Resources/Private/Language/locallang_db.xlf:tx_externalimport_domain_model_log.status',
                        'config' => [
                                'readOnly' => true,
                                'type' => 'input',
                                'size' => 10,
                                'max' => 5,
                                'eval' => 'int',
                        ]
                ],
                'crdate' => [
                        'exclude' => 0,
                        'label' => 'LLL:EXT:external_import/Resources/Private/Language/locallang_db.xlf:tx_externalimport_domain_model_log.crdate',
                        'config' => [
                                'readOnly' => true,
                                'type' => 'input',
                                'renderType' => 'inputDateTime',
                                'size' => 20,
                                'eval' => 'datetime',
                        ]
                ],
                'cruser_id' => [
                        'exclude' => 0,
                        'label' => 'LLL:EXT:external_import/Resources/Private/Language/locallang_db.xlf:tx_externalimport_domain_model_log.cruser_id',
                        'config' => [
                                'readOnly' => true,
                                'type' => 'select',
                                'size' => 1,
                                'foreign_table' => 'be_users',
                                'maxitems' => 1
                        ]
                ],
                'configuration' => [
                        'exclude' => 0,
                        'label' => 'LLL:EXT:external_import/Resources/Private/Language/locallang_db.xlf:tx_externalimport_domain_model_log.configuration',
                        'config' => [
                                'readOnly' => true,
                                'type' => 'input',
                                'eval' => 'trim, required'
                        ]
                ],
                'context' => [
                        'exclude' => 0,
                        'label' => 'LLL:EXT:external_import/Resources/Private/Language/locallang_db.xlf:tx_externalimport_domain_model_log.configuration',
                        'config' => [
                                'readOnly' => true,
                                'type' => 'input',
                                'max' => 50,
                                'eval' => 'trim, required'
                        ]
                ],
                'message' => [
                        'exclude' => 0,
                        'label' => 'LLL:EXT:external_import/Resources/Private/Language/locallang_db.xlf:tx_externalimport_domain_model_log.message',
                        'config' => [
                                'readOnly' => true,
                                'type' => 'text',
                        ]
                ],
                'duration' => [
                        'exclude' => 0,
                        'label' => 'LLL:EXT:external_import/Resources/Private/Language/locallang_db.xlf:tx_externalimport_domain_model_log.duration',
                        'config' => [
                                'readOnly' => true,
                                'type' => 'input',
                                'max' => 20,
                                'eval' => 'int'
                        ]
                ]
        ],
        'types' => [
                '0' => [
                        'showitem' => 'status, crdate, cruser_id, configuration, context, message, duration'
                ]
        ]
];
