<?php

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

use Cobweb\ExternalImport\Importer;
use Cobweb\ExternalImport\Step\StoreDataStep;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Test suite for the StoreDataStep class.
 *
 * @package Cobweb\ExternalImport\Tests\Functional
 */
class StoreDataStepTest extends FunctionalTestCase
{
    protected $testExtensionsToLoad = [
            'typo3conf/ext/svconnector',
            'typo3conf/ext/svconnector_csv',
            'typo3conf/ext/svconnector_feed',
            'typo3conf/ext/svconnector_json',
            'typo3conf/ext/external_import',
            'typo3conf/ext/externalimport_test'
    ];

    /**
     * @var StoreDataStep
     */
    protected $subject;

    /**
     * @var Importer
     */
    protected $importer;

    public function setUp()
    {
        parent::setUp();
        try {
            $this->setUpBackendUserFromFixture(1);
            // Connector services need a global LanguageService object
            $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageService::class);

            $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
            $this->subject = $objectManager->get(StoreDataStep::class);
            $this->importer = $objectManager->get(Importer::class);
            $this->importDataSet(__DIR__ . '/../Fixtures/StoragePage.xml');
            $this->importer->setForcedStoragePid(1);
            $this->subject->setImporter($this->importer);
        } catch (\Exception $e) {
            self::markTestSkipped(
                    sprintf(
                            'Some initializations could not be performed (Exception: %s [%d])',
                            $e->getMessage(),
                            $e->getCode()
                    )
            );
        }
    }

    public function handleMmRelationsProvider(): array
    {
        return [
                'products (no MM)' => [
                        'prerequisites' => [],
                        'ctrlConfiguration' => [
                                'referenceUid' => 'sku'
                        ],
                        // No MM configuration
                        'columnConfiguration' => [],
                        // No need for complete records, as there are no MM relations anyway
                        'records' => [
                                [
                                        'sku' => '000001',
                                        'name' => 'Long sword'
                                ],
                                [
                                        'sku' => '000002',
                                        'name' => 'Chain mail'
                                ]
                        ],
                        'expectedMappings' => [],
                        'expectedFullMappings' => []
                ],
                'bundles (MM with sorting)' => [
                        'prerequisites' => [
                                [
                                        'table' => 'tx_externalimporttest_product',
                                        'index' => 'base'
                                ],
                                [
                                        'table' => 'tx_externalimporttest_product',
                                        'index' => 'more'
                                ],
                                [
                                        'table' => 'tx_externalimporttest_product',
                                        'index' => 'stable'
                                ]
                        ],
                        // Only define relevant configuration
                        'ctrlConfiguration' => [
                                'referenceUid' => 'bundle_code'
                        ],
                        // Only define relevant configuration
                        'columnConfiguration' => [
                                'products' => [
                                        'MM' => [
                                                'mapping' => [
                                                        'table' => 'tx_externalimporttest_product',
                                                        'referenceField' => 'sku'
                                                ],
                                                'sorting' => 'position'
                                        ]
                                ]
                        ],
                        // Only the necessary fields
                        'records' => [
                                [
                                        'bundle_code' => 'JOY01',
                                        'products' => '000101',
                                        'position' => 1
                                ],
                                [
                                        'bundle_code' => 'JOY01',
                                        'products' => '000102',
                                        'position' => 2
                                ],
                                [
                                        'bundle_code' => 'PAIN01',
                                        'products' => '000005',
                                        'position' => 2
                                ],
                                [
                                        'bundle_code' => 'PAIN01',
                                        'products' => '000001',
                                        'position' => 1
                                ],
                                [
                                        'bundle_code' => 'PAIN02',
                                        'products' => '000005',
                                        'position' => 4
                                ],
                                [
                                        'bundle_code' => 'PAIN02',
                                        'products' => '000001',
                                        'position' => 2
                                ],
                                [
                                        'bundle_code' => 'PAIN02',
                                        'products' => '000202',
                                        'position' => 1
                                ],
                                [
                                        'bundle_code' => 'PAIN02',
                                        'products' => '000201',
                                        'position' => 3
                                ]
                        ],
                        'expectedMappings' => [
                                'products' => [
                                        'JOY01' => [
                                                1 => 3,
                                                2 => 4
                                        ],
                                        'PAIN01' => [
                                                1 => 1,
                                                2 => 2
                                        ],
                                        'PAIN02' => [
                                                1 => 6,
                                                2 => 1,
                                                3 => 5,
                                                4 => 2
                                        ]
                                ]
                        ],
                        'expectedFullMappings' => []
                ],
                'orders (MM with additional fields)' => [
                        'prerequisites' => [
                                [
                                        'table' => 'tx_externalimporttest_product',
                                        'index' => 'base'
                                ],
                                [
                                        'table' => 'tx_externalimporttest_product',
                                        'index' => 'more'
                                ],
                                [
                                        'table' => 'tx_externalimporttest_product',
                                        'index' => 'stable'
                                ]
                        ],
                        // Only define relevant configuration
                        'ctrlConfiguration' => [
                                'referenceUid' => 'order_id'
                        ],
                        // Only define relevant configuration
                        'columnConfiguration' => [
                                'products' => [
                                        'MM' => [
                                                'mapping' => [
                                                        'table' => 'tx_externalimporttest_product',
                                                        'referenceField' => 'sku'
                                                ],
                                                'additionalFields' => [
                                                        'quantity' => 'qty'
                                                ]
                                        ]
                                ]
                        ],
                        // Only the necessary fields
                        'records' => [
                                [
                                        'order_id' => '000001',
                                        'products' => '000001',
                                        'qty' => 3
                                ],
                                [
                                        'order_id' => '000001',
                                        'products' => '000005',
                                        'qty' => 1
                                ],
                                [
                                        'order_id' => '000001',
                                        'products' => '000101',
                                        'qty' => 10
                                ],
                                [
                                        'order_id' => '000001',
                                        'products' => '000102',
                                        'qty' => 2
                                ],
                                [
                                        'order_id' => '000002',
                                        'products' => '000001',
                                        'qty' => 1
                                ],
                                [
                                        'order_id' => '000002',
                                        'products' => '000005',
                                        'qty' => 2
                                ],
                                [
                                        'order_id' => '000002',
                                        'products' => '000202',
                                        'qty' => 1
                                ]
                        ],
                        'expectedMappings' => [
                                'products' => [
                                        '000001' => [
                                                0 => 1,
                                                1 => 2,
                                                2 => 3,
                                                3 => 4
                                        ],
                                        '000002' => [
                                                0 => 1,
                                                1 => 2,
                                                2 => 6
                                        ]
                                ]
                        ],
                        'expectedFullMappings' => [
                                'products' => [
                                        '000001' => [
                                                0 => [
                                                        'value' => 1,
                                                        'additionalFields' => [
                                                                'quantity' => 3
                                                        ]
                                                ],
                                                1 => [
                                                        'value' => 2,
                                                        'additionalFields' => [
                                                                'quantity' => 1
                                                        ]
                                                ],
                                                2 => [
                                                        'value' => 3,
                                                        'additionalFields' => [
                                                                'quantity' => 10
                                                        ]
                                                ],
                                                3 => [
                                                        'value' => 4,
                                                        'additionalFields' => [
                                                                'quantity' => 2
                                                        ]
                                                ]
                                        ],
                                        '000002' => [
                                                0 => [
                                                        'value' => 1,
                                                        'additionalFields' => [
                                                                'quantity' => 1
                                                        ]
                                                ],
                                                1 => [
                                                        'value' => 2,
                                                        'additionalFields' => [
                                                                'quantity' => 2
                                                        ]
                                                ],
                                                2 => [
                                                        'value' => 6,
                                                        'additionalFields' => [
                                                                'quantity' => 1
                                                        ]
                                                ],
                                        ]
                                ]
                        ]
                ]
        ];
    }

    /**
     * @test
     * @dataProvider handleMmRelationsProvider
     * @param array $prerequisites List of imports that need to be run before the test can be performed
     * @param array $ctrlConfiguration
     * @param array $columnConfiguration
     * @param array $records
     * @param array $expectedMappings
     * @param array $expectedFullMappings
     */
    public function handleMmRelationsPreparesMappings(
            $prerequisites,
            $ctrlConfiguration,
            $columnConfiguration,
            $records,
            $expectedMappings,
            $expectedFullMappings
    ): void {
        // Run necessary synchronizations
        if (count($prerequisites) > 0) {
            foreach ($prerequisites as $prerequisite) {
                $messages = $this->importer->synchronize(
                        $prerequisite['table'],
                        $prerequisite['index']
                );
            }
        }
        // Run the actual test
        $this->subject->handleMmRelations($ctrlConfiguration, $columnConfiguration, $records);
        self::assertSame(
                $expectedMappings,
                $this->subject->getMappings()
        );
        self::assertSame(
                $expectedFullMappings,
                $this->subject->getFullMappings()
        );
    }
}