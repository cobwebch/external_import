<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'External Data Import',
    'description' => 'Tool for importing data from external sources into the TYPO3 CMS database, using an extended TCA syntax. Provides a BE module, a Scheduler task, a command-line tool and an API.',
    'category' => 'module',
    'author' => 'Francois Suter (Idéative)',
    'author_email' => 'typo3@ideative.ch',
    'state' => 'stable',
    'author_company' => '',
    'version' => '8.0.1',
    'constraints' =>
        [
            'depends' =>
                [
                    'svconnector' => '6.0.0-6.99.99',
                    'typo3' => '12.4.0-13.4.99',
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
