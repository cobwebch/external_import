<?php

use Cobweb\ExternalImport\Controller\LogAjaxController;

return [
    'tx_externalimport_loglist' => [
        'path' => '/external_import/log/get',
        'target' => LogAjaxController::class . '::getAction',
    ],
];
