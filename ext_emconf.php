<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'External Data Import',
    'description' => 'Tool for importing data from external sources into the TYPO3 CMS database, using an extended TCA syntax. Provides a BE module, a Scheduler task, a command-line tool and an API.',
    'category' => 'module',
    'author' => 'Francois Suter (Idéative)',
    'author_email' => 'typo3@ideative.ch',
    'state' => 'stable',
    'author_company' => '',
    'version' => '9.0.0',
    'constraints' =>
        [
            'depends' =>
                [
                    'svconnector' => '7.0.0-7.99.99',
                    'typo3' => '13.4.0-14.3.99',
                    'reactions' => '',
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
