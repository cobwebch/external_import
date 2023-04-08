<?php

declare(strict_types=1);

return [
    \Cobweb\ExternalImport\Domain\Model\BackendUser::class => [
        'tableName' => 'be_users',
        'properties' => [
            'userName' => [
                'fieldName' => 'username',
            ],
        ],
    ],
];
