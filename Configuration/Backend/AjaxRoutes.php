<?php

return [
        'tx_externalimport_loglist' => [
                'path' => '/external_import/log/get',
                'target' => \Cobweb\ExternalImport\Controller\LogModuleController::class . '::getAction'
        ]
];
