<?php

return array(
        'ctrl' => array(
                'title' => 'LLL:EXT:external_import/Resources/Private/Language/locallang_db.xlf:tx_externalimport_domain_model_log',
                'label' => 'configuration',
                'crdate' => 'crdate',
                'cruser_id' => 'cruser_id',
                'rootLevel' => -1,
                'default_sortby' => 'ORDER BY crdate DESC',
                'searchFields' => 'configuration',
                'typeicon_classes' => array(
                        'default' => 'tx_external_import-log'
                ),
        ),
        'interface' => array(
                'showRecordFieldList' => 'status, crdate, cruser_id, configuration, message'
        ),
        'columns' => array(
                'status' => array(
                        'exclude' => 0,
                        'label' => 'LLL:EXT:external_import/Resources/Private/Language/locallang_db.xlf:tx_externalimport_domain_model_log.status',
                        'config' => array(
                                'readOnly' => true,
                                'type' => 'input',
                                'size' => 10,
                                'max' => 5,
                                'eval' => 'int',
                        )
                ),
                'crdate' => array(
                        'exclude' => 0,
                        'label' => 'LLL:EXT:external_import/Resources/Private/Language/locallang_db.xlf:tx_externalimport_domain_model_log.crdate',
                        'config' => array(
                                'readOnly' => true,
                                'type' => 'input',
                                'size' => 20,
                                'eval' => 'datetime',
                        )
                ),
                'cruser_id' => array(
                        'exclude' => 0,
                        'label' => 'LLL:EXT:external_import/Resources/Private/Language/locallang_db.xlf:tx_externalimport_domain_model_log.cruser_id',
                        'config' => array(
                                'readOnly' => true,
                                'type' => 'select',
                                'size' => 1,
                                'foreign_table' => 'be_users',
                                'maxitems' => 1
                        )
                ),
                'configuration' => array(
                        'exclude' => 0,
                        'label' => 'LLL:EXT:external_import/Resources/Private/Language/locallang_db.xlf:tx_externalimport_domain_model_log.configuration',
                        'config' => array(
                                'readOnly' => true,
                                'type' => 'input',
                                'eval' => 'trim, required'
                        )
                ),
                'context' => array(
                        'exclude' => 0,
                        'label' => 'LLL:EXT:external_import/Resources/Private/Language/locallang_db.xlf:tx_externalimport_domain_model_log.configuration',
                        'config' => array(
                                'readOnly' => true,
                                'type' => 'input',
                                'max' => 50,
                                'eval' => 'trim, required'
                        )
                ),
                'message' => array(
                        'exclude' => 0,
                        'label' => 'LLL:EXT:external_import/Resources/Private/Language/locallang_db.xlf:tx_externalimport_domain_model_log.message',
                        'config' => array(
                                'readOnly' => true,
                                'type' => 'text',
                        )
                ),
        ),
        'types' => array(
                '0' => array('showitem' => 'status, crdate, cruser_id, configuration, message')
        )
);
