<?php

namespace Cobweb\ExternalImport\Tests\Functional;

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
use Cobweb\ExternalImport\Step\CheckPermissionsStep;
use Cobweb\ExternalImport\Step\ClearCacheStep;
use Cobweb\ExternalImport\Step\ConnectorCallbackStep;
use Cobweb\ExternalImport\Step\HandleDataStep;
use Cobweb\ExternalImport\Step\ReadDataStep;
use Cobweb\ExternalImport\Step\StoreDataStep;
use Cobweb\ExternalImport\Step\TransformDataStep;
use Cobweb\ExternalImport\Step\ValidateConfigurationStep;
use Cobweb\ExternalImport\Step\ValidateConnectorStep;
use Cobweb\ExternalImport\Step\ValidateDataStep;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use \TYPO3\CMS\Core\Localization\LanguageService;

/**
 * Test suite for the preview feature of the Importer class.
 *
 * @package Cobweb\ExternalImport\Tests\Functional
 */
class ImporterPreviewTest extends FunctionalTestCase
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
     * @var Importer
     */
    protected $subject;

    protected function setUp()
    {
        parent::setUp();
        try {
            $this->setUpBackendUserFromFixture(1);
            // Connector services need a global LanguageService object
            $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageService::class);

            $objectManager = new ObjectManager();
            $this->subject = $objectManager->get(Importer::class);
            $this->importDataSet(__DIR__ . '/Fixtures/StoragePage.xml');
            $this->subject->setForcedStoragePid(1);
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

    /**
     * @test
     */
    public function runPreviewWithWrongPreviewStepIssuesWarning(): void
    {
        $this->subject->setPreviewStep('foo');
        $messages = $this->subject->synchronize(
                'tx_externalimporttest_tag',
                0
        );
        self::assertCount(
                1,
                $messages[AbstractMessage::WARNING]
        );
    }

    /**
     * @test
     */
    public function runPreviewOnCheckPermissionsStepReturnsNull(): void
    {
        $this->subject->setPreviewStep(CheckPermissionsStep::class);
        $messages = $this->subject->synchronize(
                'tx_externalimporttest_tag',
                0
        );
        self::assertNull(
                $this->subject->getPreviewData()
        );
    }

    /**
     * @test
     */
    public function runPreviewOnValidateConfigurationStepReturnsNull(): void
    {
        $this->subject->setPreviewStep(ValidateConfigurationStep::class);
        $messages = $this->subject->synchronize(
                'tx_externalimporttest_tag',
                0
        );
        self::assertNull(
                $this->subject->getPreviewData()
        );
    }

    /**
     * @test
     */
    public function runPreviewOnValidateConnectorStepReturnsNull(): void
    {
        $this->subject->setPreviewStep(ValidateConnectorStep::class);
        $messages = $this->subject->synchronize(
                'tx_externalimporttest_tag',
                0
        );
        self::assertNull(
                $this->subject->getPreviewData()
        );
    }

    public function readPreviewProvider(): array
    {
        return [
                'xml-type data' => [
                        'table' => 'tx_externalimporttest_product',
                        'index' => 'base',
                        'result' => 'EXT:externalimport_test/Resources/Private/ImportData/Test/Products.xml'
                ],
                'array-type data' => [
                        'table' => 'tx_externalimporttest_tag',
                        'index' => 0,
                        'result' => [
                                [
                                        'Code' => 'attack',
                                        'Name' => 'Weapons '
                                ],
                                [
                                        'Code' => 'defense',
                                        'Name' => 'Armor & Shields'
                                ],
                                [
                                        'Code' => 'rude',
                                        'Name' => 'F**k'
                                ],
                                [
                                        'Code' => 'metal',
                                        'Name' => 'Metallic objects'
                                ],
                                [
                                        'Code' => 'fun',
                                        'Name' => ' Fun, free time'
                                ],
                                [
                                        'Code' => 'food',
                                        'Name' => 'Food & Drinks'
                                ]
                        ]
                ]
        ];
    }

    /**
     * @test
     * @dataProvider readPreviewProvider
     * @param $table
     * @param $index
     * @param $result
     */
    public function runPreviewOnReadDataStepReturnsRawData($table, $index, $result): void
    {
        $this->subject->setPreviewStep(ReadDataStep::class);
        $messages = $this->subject->synchronize(
                $table,
                $index
        );
        // The result variable may be pointing to a file, in which case we want to read it
        if (is_string($result) && strpos($result, 'EXT:') === 0) {
            $result = file_get_contents(
                    GeneralUtility::getFileAbsFileName($result)
            );
        }
        self::assertSame(
                $result,
                $this->subject->getPreviewData()
        );
    }

    public function handlePreviewProvider(): array
    {
        return [
                'xml-type data' => [
                        'table' => 'tx_externalimporttest_product',
                        'index' => 'base',
                        'result' => [
                                [
                                        'attributes' => "\n\t\t\t<quality>Robust</quality>\n\t\t\t<quality>Stylish</quality>\n\t\t\t<quality>Reliable</quality>\n\t\t",
                                        'categories' => 'USEFUL',
                                        'name' => 'Long sword',
                                        'sku' => '000001',
                                        'tags' => 'attack,metal'
                                ],
                                [
                                        'attributes' => "\n\t\t\t<quality>Not too cumbersome</quality>\n\t\t\t<quality>Lets the air flow</quality>\n\t\t",
                                        'categories' => 'USEFUL',
                                        'name' => 'Chain mail',
                                        'sku' => '000005',
                                        'tags' => 'defense,metal'
                                ]
                        ]
                ],
                'array-type data' => [
                        'table' => 'tx_externalimporttest_tag',
                        'index' => 0,
                        'result' => [
                                [
                                        'code' => 'attack',
                                        'name' => 'Weapons '
                                ],
                                [
                                        'code' => 'defense',
                                        'name' => 'Armor & Shields'
                                ],
                                [
                                        'code' => 'metal',
                                        'name' => 'Metallic objects'
                                ],
                                [
                                        'code' => 'fun',
                                        'name' => ' Fun, free time'
                                ],
                                [
                                        'code' => 'food',
                                        'name' => 'Food & Drinks'
                                ]
                        ]
                ],
                'array-type data with sub-structure' => [
                        'table' => 'tx_externalimporttest_order',
                        'index' => 0,
                        'result' => [
                                [
                                        'client_id' => 'Conan the Barbarian',
                                        'order_date' => '2014-08-07 14:32',
                                        'order_id' => '000001',
                                        'products' => '000001',
                                        'qty' => '3'
                                ],
                                [
                                        'client_id' => 'Conan the Barbarian',
                                        'order_date' => '2014-08-07 14:32',
                                        'order_id' => '000001',
                                        'products' => '000005',
                                        'qty' => '1'
                                ],
                                [
                                        'client_id' => 'Conan the Barbarian',
                                        'order_date' => '2014-08-07 14:32',
                                        'order_id' => '000001',
                                        'products' => '000101',
                                        'qty' => '10'
                                ],
                                [
                                        'client_id' => 'Conan the Barbarian',
                                        'order_date' => '2014-08-07 14:32',
                                        'order_id' => '000001',
                                        'products' => '000102',
                                        'qty' => '2'
                                ],
                                [
                                        'client_id' => 'Sonja the Red',
                                        'order_date' => '2014-08-08 06:48',
                                        'order_id' => '000002',
                                        'products' => '000001',
                                        'qty' => '1'
                                ],
                                [
                                        'client_id' => 'Sonja the Red',
                                        'order_date' => '2014-08-08 06:48',
                                        'order_id' => '000002',
                                        'products' => '000005',
                                        'qty' => '2'
                                ],
                                [
                                        'client_id' => 'Sonja the Red',
                                        'order_date' => '2014-08-08 06:48',
                                        'order_id' => '000002',
                                        'products' => '000202',
                                        'qty' => '1'
                                ]
                        ]
                ]
        ];
    }

    /**
     * @test
     * @dataProvider handlePreviewProvider
     * @param $table
     * @param $index
     * @param $result
     */
    public function runPreviewOnHandleDataStepReturnsHandledData($table, $index, $result): void
    {
        $this->subject->setPreviewStep(HandleDataStep::class);
        $messages = $this->subject->synchronize(
                $table,
                $index
        );
        self::assertSame(
                $result,
                $this->subject->getPreviewData()
        );
    }

    /**
     * @test
     */
    public function runPreviewOnValidateDataStepReturnsNull(): void
    {
        $this->subject->setPreviewStep(ValidateDataStep::class);
        $messages = $this->subject->synchronize(
                'tx_externalimporttest_tag',
                0
        );
        self::assertNull(
                $this->subject->getPreviewData()
        );
    }

    public function transformPreviewProvider(): array
    {
        return [
                'tags' => [
                        'table' => 'tx_externalimporttest_tag',
                        'index' => 0,
                        'result' => [
                                [
                                        'code' => 'attack',
                                        'name' => 'Weapons'
                                ],
                                [
                                        'code' => 'defense',
                                        'name' => 'Armor & Shields'
                                ],
                                [
                                        'code' => 'metal',
                                        'name' => 'Metallic objects'
                                ],
                                [
                                        'code' => 'fun',
                                        'name' => 'Fun, free time'
                                ],
                                [
                                        'code' => 'food',
                                        'name' => 'Food & Drinks'
                                ]
                        ]
                ],
                'base products' => [
                        'table' => 'tx_externalimporttest_product',
                        'index' => 'base',
                        'result' => [
                                [
                                        'attributes' => "PREVIEW: <ul>\n\t\t\t<li>Robust</li>\n\t\t\t<li>Stylish</li>\n\t\t\t<li>Reliable</li>\n\t\t</ul>",
                                        'categories' => '',
                                        'name' => 'Long sword (base)',
                                        'sku' => '000001'
                                ],
                                [
                                        'attributes' => "PREVIEW: <ul>\n\t\t\t<li>Not too cumbersome</li>\n\t\t\t<li>Lets the air flow</li>\n\t\t</ul>",
                                        'categories' => '',
                                        'name' => 'Chain mail (base)',
                                        'sku' => '000005'
                                ]
                        ]
                ]
        ];
    }

    /**
     * @test
     * @dataProvider transformPreviewProvider
     * @param $table
     * @param $index
     * @param $result
     */
    public function runPreviewOnTransformDataStepReturnsTransformedData($table, $index, $result): void
    {
        $this->subject->setPreviewStep(TransformDataStep::class);
        $messages = $this->subject->synchronize(
                $table,
                $index
        );
        self::assertSame(
                $result,
                $this->subject->getPreviewData()
        );
    }

    public function storePreviewProvider(): array
    {
        return [
                'tags' => [
                        'fixtures' => [
                                __DIR__ . '/Fixtures/StoreDataStepPreviewTest.xml'
                        ],
                        'prerequisistes' => [],
                        'table' => 'tx_externalimporttest_tag',
                        'index' => 0,
                        'testDatabase' => true,
                        'recordsCount' => 1,
                        'result' => [
                                'data' => [
                                        'tx_externalimporttest_tag' => [
                                                'NEW1' => [
                                                        'code' => 'attack',
                                                        'name' => 'Weapons',
                                                        'pid' => 1
                                                ],
                                                'NEW2' => [
                                                        'code' => 'defense',
                                                        'name' => 'Armor & Shields',
                                                        'pid' => 1
                                                ],
                                                'NEW3' => [
                                                        'code' => 'metal',
                                                        'name' => 'Metallic objects',
                                                        'pid' => 1
                                                ],
                                                'NEW4' => [
                                                        'code' => 'fun',
                                                        'name' => 'Fun, free time',
                                                        'pid' => 1
                                                ],
                                                'NEW5' => [
                                                        'code' => 'food',
                                                        'name' => 'Food & Drinks',
                                                        'pid' => 1
                                                ]
                                        ]
                                ],
                                'commands-delete' => [
                                        'tx_externalimporttest_tag' => [
                                                2 => [
                                                        'delete' => 1
                                                ]
                                        ]
                                ],
                                'commands-move' => [
                                        'tx_externalimporttest_tag' => []
                                ]
                        ]
                ],
                'base products (insert)' => [
                        'fixtures' => [],
                        'prerequisistes' => [
                                [
                                        'table' => 'tx_externalimporttest_tag',
                                        'index' => 0
                                ]
                        ],
                        'table' => 'tx_externalimporttest_product',
                        'index' => 'base',
                        'testDatabase' => true,
                        'recordsCount' => 0,
                        'result' => [
                                'data' => [
                                        'tx_externalimporttest_product' => [
                                                'NEW1' => [
                                                        'attributes' => "PREVIEW: <ul>\n\t\t\t<li>Robust</li>\n\t\t\t<li>Stylish</li>\n\t\t\t<li>Reliable</li>\n\t\t</ul>",
                                                        'categories' => '',
                                                        'name' => 'Long sword (base)',
                                                        'sku' => '000001',
                                                        'tags' => '1,3',
                                                        'pid' => 1
                                                ],
                                                'NEW2' => [
                                                        'attributes' => "PREVIEW: <ul>\n\t\t\t<li>Not too cumbersome</li>\n\t\t\t<li>Lets the air flow</li>\n\t\t</ul>",
                                                        'categories' => '',
                                                        'name' => 'Chain mail (base)',
                                                        'sku' => '000005',
                                                        'tags' => '2,3',
                                                        'pid' => 1
                                                ]
                                        ]
                                ],
                                'commands-delete' => [],
                                'commands-move' => [
                                        'tx_externalimporttest_product' => []
                                ]
                        ]
                ],
                'base products (update)' => [
                        'fixtures' => [],
                        'prerequisistes' => [
                                [
                                        'table' => 'tx_externalimporttest_product',
                                        'index' => 'base'
                                ]
                        ],
                        'table' => 'tx_externalimporttest_product',
                        'index' => 'base',
                        'testDatabase' => false,
                        'recordsCount' => 0,
                        'result' => [
                                'data' => [
                                        'tx_externalimporttest_product' => [
                                                '1' => [
                                                        'attributes' => "PREVIEW: <ul>\n\t\t\t<li>Robust</li>\n\t\t\t<li>Stylish</li>\n\t\t\t<li>Reliable</li>\n\t\t</ul>",
                                                        'categories' => '',
                                                        'name' => 'Long sword (base)',
                                                        'sku' => '000001',
                                                ],
                                                '2' => [
                                                        'attributes' => "PREVIEW: <ul>\n\t\t\t<li>Not too cumbersome</li>\n\t\t\t<li>Lets the air flow</li>\n\t\t</ul>",
                                                        'categories' => '',
                                                        'name' => 'Chain mail (base)',
                                                        'sku' => '000005'
                                                ]
                                        ]
                                ],
                                'commands-delete' => [],
                                'commands-move' => [
                                        'tx_externalimporttest_product' => []
                                ]
                        ]
                ],
                'update products with move' => [
                        'fixtures' => [
                                __DIR__ . '/Fixtures/ExtraStoragePage.xml'
                        ],
                        'prerequisistes' => [
                                [
                                        'table' => 'tx_externalimporttest_product',
                                        'index' => 'base'
                                ]
                        ],
                        'table' => 'tx_externalimporttest_product',
                        'index' => 'updated_products',
                        'testDatabase' => false,
                        'recordsCount' => 0,
                        'result' => [
                                'data' => [
                                        'tx_externalimporttest_product' => [
                                                '1' => [
                                                        'name' => 'Long sword (updated)',
                                                        'pid' => '2',
                                                        'sku' => '000001'
                                                ]
                                        ]
                                ],
                                'commands-delete' => [],
                                'commands-move' => [
                                        'tx_externalimporttest_product' => [
                                                '1' => [
                                                        'move' => 2
                                                ]
                                        ]
                                ]
                        ]
                ],
                'bundles' => [
                        'fixtures' => [],
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
                        'table' => 'tx_externalimporttest_bundle',
                        'index' => 0,
                        'testDatabase' => true,
                        'recordsCount' => 0,
                        'result' => [
                                'data' => [
                                        'tx_externalimporttest_bundle' => [
                                                'NEW1' => [
                                                        'bundle_code' => 'JOY01',
                                                        'maker' => 'Doctor Strange',
                                                        'name' => 'Bundle of joy',
                                                        'products' => '3,4',
                                                        'pid' => 1
                                                ],
                                                'NEW2' => [
                                                        'bundle_code' => 'PAIN01',
                                                        'maker' => 'Loki',
                                                        'name' => 'Bundle of pain',
                                                        'products' => '1,2',
                                                        'pid' => 1
                                                ],
                                                'NEW3' => [
                                                        'bundle_code' => 'PAIN02',
                                                        'maker' => 'Mad Max',
                                                        'name' => 'Bundle of extra pain',
                                                        'products' => '6,1,5,2',
                                                        'pid' => 1
                                                ]
                                        ]
                                ],
                                'commands-delete' => [],
                                'commands-move' => [
                                        'tx_externalimporttest_bundle' => []
                                ]
                        ]
                ]
        ];
    }

    /**
     * @test
     * @dataProvider storePreviewProvider
     * @param array $fixtures List of fixture files to load
     * @param array $prerequisites List of imports to perform before the one being actually tested
     * @param $table
     * @param $index
     * @param bool $testDatabase TRUE to test DB insertions
     * @param int $recordsCount How many records should be in the database
     * @param $result
     */
    public function runPreviewOnStoreDataStepReturnsStorageDataAndWritesNothingToDatabase($fixtures, $prerequisites, $table, $index, $testDatabase, $recordsCount, $result): void
    {
        // Load designated fixture files
        if (count($fixtures) > 0) {
            foreach ($fixtures as $fixture) {
                try {
                    $this->importDataSet($fixture);
                } catch (\Exception $e) {
                    self::markTestSkipped(
                            sprintf(
                                    'Could not load fixture file %s',
                                    $fixture
                            )
                    );
                }
            }
        }
        // Run necessary synchronizations
        if (count($prerequisites) > 0) {
            foreach ($prerequisites as $prerequisite) {
                $messages = $this->subject->synchronize(
                        $prerequisite['table'],
                        $prerequisite['index']
                );
            }
        }
        // Run the actual test
        $this->subject->setPreviewStep(StoreDataStep::class);
        $this->subject->setTestMode(true);
        $messages = $this->subject->synchronize(
                $table,
                $index
        );
        // Test the preview data
        self::assertSame(
                $result,
                $this->subject->getPreviewData()
        );
        // Test that nothing was written to the database, i.e. the record count is unchanged
        // (if appropriate, not for update scenarios)
        if ($testDatabase) {
            $countItems = $this->getDatabaseConnection()->selectCount(
                    'uid',
                    $table
            );
            self::assertEquals($recordsCount, $countItems);
        }
    }

    public function clearCachePreviewProvider(): array
    {
        return [
                'tags' => [
                        'table' => 'tx_externalimporttest_tag',
                        'index' => 0,
                        'result' => [
                                'caches' => []
                        ]
                ],
                'product pages' => [
                        'table' => 'pages',
                        'index' => 'product_pages',
                        'result' => [
                                'caches' => [
                                        'pages'
                                ]
                        ]
                ]
        ];
    }

    /**
     * @test
     * @dataProvider clearCachePreviewProvider
     * @param $table
     * @param $index
     * @param $result
     */
    public function runPreviewOnClearCacheStepReturnsCacheListAndClearsNothing($table, $index, $result): void
    {
        try {
            $this->importDataSet(__DIR__ . '/Fixtures/ClearCacheStepPreviewTest.xml');
        } catch (\Exception $e) {
            self::markTestSkipped('Could not load fixture file');
        }
        $this->subject->setPreviewStep(ClearCacheStep::class);
        $this->subject->setTestMode(true);
        $messages = $this->subject->synchronize(
                $table,
                $index
        );
        self::assertSame(
                $result,
                $this->subject->getPreviewData()
        );
        // The cache item created with the fixture should not be have been cleared
        $countCacheItems = $this->getDatabaseConnection()->selectCount(
                'id',
                'cf_cache_pages_tags'
        );
        self::assertEquals(1, $countCacheItems);
    }

    /**
     * @test
     */
    public function runPreviewOnConnectorCallbackStepReturnsNull(): void
    {
        $this->subject->setPreviewStep(ConnectorCallbackStep::class);
        $messages = $this->subject->synchronize(
                'tx_externalimporttest_tag',
                0
        );
        self::assertNull(
                $this->subject->getPreviewData()
        );
    }
}