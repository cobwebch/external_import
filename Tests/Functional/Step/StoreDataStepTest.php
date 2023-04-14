<?php

declare(strict_types=1);

namespace Cobweb\ExternalImport\Tests\Functional\Step;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Cobweb\ExternalImport\Domain\Model\Configuration;
use Cobweb\ExternalImport\Domain\Model\Data;
use Cobweb\ExternalImport\Domain\Repository\TemporaryKeyRepository;
use Cobweb\ExternalImport\Domain\Repository\UidRepository;
use Cobweb\ExternalImport\Importer;
use Cobweb\ExternalImport\Step\StoreDataStep;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test suite for the StoreDataStep class.
 *
 * NOTE: the TCE structure produced for storage is tested in \Cobweb\ExternalImport\Tests\Functional\ImporterPreviewTest
 */
class StoreDataStepTest extends FunctionalTestCase
{
    protected $testExtensionsToLoad = [
        'typo3conf/ext/external_import',
        'typo3conf/ext/externalimport_test'
    ];

    /**
     * @var StoreDataStep
     */
    protected $subject;

    public function __sleep()
    {
        return [];
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->subject = GeneralUtility::makeInstance(StoreDataStep::class);
        $this->subject->setData(GeneralUtility::makeInstance(Data::class));
    }

    public function dataToStoreProvider(): array
    {
        return [
            'no denormalized data' => [
                'generalConfiguration' => [
                    'referenceUid' => 'code'
                ],
                'columnConfiguration' => [
                    'code' => [],
                    'first_name' => [],
                    'last_name' => [],
                    'useless' => [
                        Configuration::DO_NOT_SAVE_KEY => true
                    ]
                ],
                'input' => [
                    [
                        'code' => 'JP',
                        'first_name' => 'Joey',
                        'last_name' => 'Pechorin',
                        'useless' => 'Useless information'
                    ],
                    [
                        'code' => 'AP',
                        'first_name' => 'Amnesia',
                        'last_name' => 'Phreedom',
                        'useless' => 'Useless information'
                    ]
                ],
                'output' => [
                    1 => [
                        'code' => 'JP',
                        'first_name' => 'Joey',
                        'last_name' => 'Pechorin'
                    ],
                    'NEW1' => [
                        'code' => 'AP',
                        'first_name' => 'Amnesia',
                        'last_name' => 'Phreedom'
                    ]
                ],
                'existingUids' => [
                    'JP' => 1
                ]
            ],
            'no denormalized data - insert disabled' => [
                'generalConfiguration' => [
                    'referenceUid' => 'code',
                    'disabledOperations' => 'insert'
                ],
                'columnConfiguration' => [
                    'code' => [],
                    'first_name' => [],
                    'last_name' => [],
                    'useless' => [
                        Configuration::DO_NOT_SAVE_KEY => true
                    ]
                ],
                'input' => [
                    [
                        'code' => 'JP',
                        'first_name' => 'Joey',
                        'last_name' => 'Pechorin',
                        'useless' => 'Useless information'
                    ],
                    [
                        'code' => 'AP',
                        'first_name' => 'Amnesia',
                        'last_name' => 'Phreedom',
                        'useless' => 'Useless information'
                    ]
                ],
                'output' => [
                    1 => [
                        'code' => 'JP',
                        'first_name' => 'Joey',
                        'last_name' => 'Pechorin'
                    ]
                ],
                'existingUids' => [
                    'JP' => 1
                ]
            ],
            'no denormalized data - update disabled' => [
                'generalConfiguration' => [
                    'referenceUid' => 'code',
                    'disabledOperations' => 'update'
                ],
                'columnConfiguration' => [
                    'code' => [],
                    'first_name' => [],
                    'last_name' => [],
                    'useless' => [
                        Configuration::DO_NOT_SAVE_KEY => true
                    ]
                ],
                'input' => [
                    [
                        'code' => 'JP',
                        'first_name' => 'Joey',
                        'last_name' => 'Pechorin',
                        'useless' => 'Useless information'
                    ],
                    [
                        'code' => 'AP',
                        'first_name' => 'Amnesia',
                        'last_name' => 'Phreedom',
                        'useless' => 'Useless information'
                    ]
                ],
                'output' => [
                    'NEW1' => [
                        'code' => 'AP',
                        'first_name' => 'Amnesia',
                        'last_name' => 'Phreedom'
                    ]
                ],
                'existingUids' => [
                    'JP' => 1
                ]
            ],
            'single denormalized data' => [
                'generalConfiguration' => [
                    'referenceUid' => 'code'
                ],
                'columnConfiguration' => [
                    'code' => [],
                    'first_name' => [],
                    'last_name' => [],
                    'book' => [
                        'multipleRows' => true
                    ],
                    'useless' => [
                        Configuration::DO_NOT_SAVE_KEY => true
                    ]
                ],
                'input' => [
                    [
                        'code' => 'JP',
                        'first_name' => 'Joey',
                        'last_name' => 'Pechorin',
                        'book' => 2,
                        'useless' => 'Useless information'
                    ],
                    [
                        'code' => 'AP',
                        'first_name' => 'Amnesia',
                        'last_name' => 'Phreedom',
                        'book' => 1,
                        'useless' => 'Useless information'
                    ],
                    [
                        'code' => 'AP',
                        'first_name' => 'Amnesia',
                        'last_name' => 'Phreedom',
                        'book' => 2,
                        'useless' => 'Useless information'
                    ]
                ],
                'output' => [
                    1 => [
                        'code' => 'JP',
                        'first_name' => 'Joey',
                        'last_name' => 'Pechorin',
                        'book' => '2'
                    ],
                    'NEW1' => [
                        'code' => 'AP',
                        'first_name' => 'Amnesia',
                        'last_name' => 'Phreedom',
                        'book' => '1,2'
                    ]
                ],
                'existingUids' => [
                    'JP' => 1
                ]
            ],
            'multiple denormalized data' => [
                'generalConfiguration' => [
                    'referenceUid' => 'code'
                ],
                'columnConfiguration' => [
                    'code' => [],
                    'first_name' => [],
                    'last_name' => [],
                    'book' => [
                        'multipleRows' => true
                    ],
                    'relations' => [
                        'multipleRows' => true
                    ]
                ],
                'input' => [
                    [
                        'code' => 'JP',
                        'first_name' => 'Joey',
                        'last_name' => 'Pechorin',
                        'book' => 2,
                        'relations' => 'TP'
                    ],
                    [
                        'code' => 'JP',
                        'first_name' => 'Joey',
                        'last_name' => 'Pechorin',
                        'book' => 2,
                        'relations' => 'JF'
                    ],
                    [
                        'code' => 'AP',
                        'first_name' => 'Amnesia',
                        'last_name' => 'Phreedom',
                        'book' => 1,
                        'relations' => 'JP'
                    ],
                    [
                        'code' => 'AP',
                        'first_name' => 'Amnesia',
                        'last_name' => 'Phreedom',
                        'book' => 1,
                        'relations' => 'JF'
                    ],
                    [
                        'code' => 'AP',
                        'first_name' => 'Amnesia',
                        'last_name' => 'Phreedom',
                        'book' => 2,
                        'relations' => 'JP'
                    ],
                    [
                        'code' => 'AP',
                        'first_name' => 'Amnesia',
                        'last_name' => 'Phreedom',
                        'book' => 2,
                        'relations' => 'JF'
                    ]
                ],
                'output' => [
                    1 => [
                        'code' => 'JP',
                        'first_name' => 'Joey',
                        'last_name' => 'Pechorin',
                        'book' => '2',
                        'relations' => 'TP,JF'
                    ],
                    'NEW1' => [
                        'code' => 'AP',
                        'first_name' => 'Amnesia',
                        'last_name' => 'Phreedom',
                        'book' => '1,2',
                        'relations' => 'JP,JF'
                    ]
                ],
                'existingUids' => [
                    'JP' => 1
                ]
            ],
            'children data' => [
                'generalConfiguration' => [
                    'referenceUid' => 'order'
                ],
                'columnConfiguration' => [
                    'order' => [],
                    'customer' => [],
                    'products' => [
                        'children' => [
                            'table' => 'tx_externalimporttest_order_items',
                            'columns' => [
                                'uid_local' => [
                                    'field' => '__parent.id__',
                                ],
                                'uid_foreign' => [
                                    'field' => 'products',
                                ],
                                'quantity' => [
                                    'field' => 'quantity'
                                ]
                            ]
                        ]
                    ]
                ],
                'input' => [
                    [
                        'order' => '000001',
                        'customer' => 'Conan the Barbarian',
                        'products' => 1,
                        'quantity' => 3
                    ],
                    [
                        'order' => '000001',
                        'customer' => 'Conan the Barbarian',
                        'products' => 2,
                        'quantity' => 5
                    ],
                    [
                        'order' => '000001',
                        'customer' => 'Conan the Barbarian',
                        'products' => 3,
                        'quantity' => 10
                    ],
                    [
                        'order' => '000002',
                        'customer' => 'Sonja the Red',
                        'products' => 1,
                        'quantity' => 2
                    ],
                    [
                        'order' => '000002',
                        'customer' => 'Sonja the Red',
                        'products' => 2,
                        'quantity' => 3
                    ],
                    [
                        'order' => '000003',
                        'customer' => 'The Black Currant',
                        // Test that no children are generated, because the field is not defined
                        'products' => null,
                        'quantity' => 3
                    ]
                ],
                'output' => [
                    1 => [
                        'order' => '000001',
                        'customer' => 'Conan the Barbarian',
                        'products' => 1,
                        '__children__' => [
                            'products' => [
                                'tx_externalimporttest_order_items' => [
                                    'NEW1' => [
                                        'uid_local' => 1,
                                        'uid_foreign' => 1,
                                        'quantity' => 3
                                    ],
                                    'NEW2' => [
                                        'uid_local' => 1,
                                        'uid_foreign' => 2,
                                        'quantity' => 5
                                    ],
                                    'NEW3' => [
                                        'uid_local' => 1,
                                        'uid_foreign' => 3,
                                        'quantity' => 10
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'NEW4' => [
                        'order' => '000002',
                        'customer' => 'Sonja the Red',
                        'products' => 1,
                        '__children__' => [
                            'products' => [
                                'tx_externalimporttest_order_items' => [
                                    'NEW5' => [
                                        'uid_local' => 'NEW4',
                                        'uid_foreign' => 1,
                                        'quantity' => 2
                                    ],
                                    'NEW6' => [
                                        'uid_local' => 'NEW4',
                                        'uid_foreign' => 2,
                                        'quantity' => 3
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'NEW7' => [
                        'order' => '000003',
                        'customer' => 'The Black Currant',
                        '__children__' => []
                    ]
                ],
                'existingUids' => [
                    '000001' => 1
                ]
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataToStoreProvider
     * @param array $generalConfiguration
     * @param array $columnConfiguration
     * @param array $input
     * @param array $output
     * @param array $existingUids
     * @throws \TYPO3\CMS\Extbase\Object\Exception
     */
    public function prepareDataToStoreReturnsPreparedData(array $generalConfiguration, array $columnConfiguration, array $input, array $output, array $existingUids): void
    {
        $configuration = GeneralUtility::makeInstance(Configuration::class);
        $configuration->setGeneralConfiguration($generalConfiguration);
        $configuration->setColumnConfiguration($columnConfiguration);
        $configuration->processConfiguration();
        $configuration->setTable('foo');
        $this->subject->getData()->setRecords($input);
        $uidRepository = $this->createMock(UidRepository::class);
        $uidRepository->method('getExistingUids')->willReturn($existingUids);
        $importer = $this->createMock(Importer::class);
        $importer->method('getExternalConfiguration')->willReturn($configuration);
        $importer->method('getUidRepository')->willReturn($uidRepository);
        $temporaryKeyRepository = GeneralUtility::makeInstance(TemporaryKeyRepository::class);
        $temporaryKeyRepository->setTestMode(true);
        $importer->method('getTemporaryKeyRepository')->willReturn($temporaryKeyRepository);
        $this->subject->setImporter($importer);

        self::assertSame(
            $output,
            $this->subject->prepareDataToStore()
        );
    }

    public function childStructureProvider(): array
    {
        return [
            'orders' => [
                'childConfiguration' => [
                    'uid_local' => [
                        'field' => '__parent.id__',
                    ],
                    'uid_foreign' => [
                        'field' => 'products',
                    ],
                    'quantity' => [
                        'field' => 'quantity'
                    ]
                ],
                'parentId' => 'NEW2',
                'parentData' => [
                    'products' => 1,
                    'quantity' => 3
                ],
                'result' => [
                    'NEW1' => [
                        'uid_local' => 'NEW2',
                        'uid_foreign' => 1,
                        'quantity' => 3
                    ]
                ]
            ]
        ];
    }

    /**
     * @test
     * @dataProvider childStructureProvider
     * @param array $childConfiguration
     * @param mixed $parentId
     * @param array $parentData
     * @param array $result
     * @throws \TYPO3\CMS\Extbase\Object\Exception
     */
    public function prepareChildStructureReturnsChildData(array $childConfiguration, $parentId, array $parentData, array $result): void
    {
        $importer = $this->createMock(Importer::class);
        $temporaryKeyRepository = GeneralUtility::makeInstance(TemporaryKeyRepository::class);
        $temporaryKeyRepository->setTestMode(true);
        $importer->method('getTemporaryKeyRepository')->willReturn($temporaryKeyRepository);
        $importer->method('getExternalConfiguration')->willReturn(
            GeneralUtility::makeInstance(Configuration::class)
        );
        $this->subject->setImporter($importer);
        self::assertSame(
            $result,
            $this->subject->prepareChildStructure('foo', $childConfiguration, $parentId, $parentData, [])
        );
    }
}
