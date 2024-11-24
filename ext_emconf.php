<?php

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
    'version' => '7.2.9',
    'constraints' =>
        [
            'depends' =>
                [
                    'svconnector' => '5.0.0-5.99.99',
                    'typo3' => '11.5.0-12.4.99',
                    'scheduler' => '',
                ],
            'conflicts' =>
                [
                ],
            'suggests' =>
                [
                ],
        ],
];
