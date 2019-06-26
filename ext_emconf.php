<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "external_import".
 *
 * Auto generated 31-03-2017 20:45
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = [
        'title' => 'External Data Import',
        'description' => 'Tool for importing data from external sources into the TYPO3 CMS database, using an extended TCA syntax. Provides a BE module, a Scheduler task, a command-line tool and an API.',
        'category' => 'module',
        'author' => 'Francois Suter (Cobweb)',
        'author_email' => 'typo3@cobweb.ch',
        'state' => 'stable',
        'uploadfolder' => 0,
        'createDirs' => '',
        'clearCacheOnLoad' => 0,
        'author_company' => '',
        'version' => '4.1.3',
        'constraints' =>
                [
                        'depends' =>
                                [
                                        'svconnector' => '3.3.0-0.0.0',
                                        'typo3' => '8.7.0-9.99.99',
                                ],
                        'conflicts' =>
                                [
                                ],
                        'suggests' =>
                                [
                                        'externalimport_tut' => '2.0.1-0.0.0',
                                        'scheduler' => '',
                                ],
                ],
];
