<?php

/***********************************************************************
 * Extension Manager/Repository config file for ext "external_import". *
 ***********************************************************************/

$EM_CONF[$_EXTKEY] = [
        'title' => 'External Data Import',
        'description' => 'Tool for importing data from external sources into the TYPO3 CMS database, using an extended TCA syntax. Provides a BE module, a Scheduler task, a command-line tool and an API.',
        'category' => 'module',
        'author' => 'Francois Suter (Idéative)',
        'author_email' => 'typo3@ideative.ch',
        'state' => 'stable',
        'uploadfolder' => 0,
        'createDirs' => '',
        'clearCacheOnLoad' => 0,
        'author_company' => '',
        'version' => '5.1.1',
        'constraints' =>
                [
                        'depends' =>
                                [
                                        'svconnector' => '3.4.0-0.0.0',
                                        'typo3' => '10.4.99-11.99.99',
                                        'scheduler' => '',
                                ],
                        'conflicts' =>
                                [
                                ],
                        'suggests' =>
                                [
                                        'externalimport_tut' => '2.0.1-0.0.0',
                                ],
                ],
];
