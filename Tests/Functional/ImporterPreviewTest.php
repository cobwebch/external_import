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
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Lang\LanguageService;

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
    protected $subject = null;

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
    public function runPreviewWithWrongPreviewStepIssuesWarning()
    {
        $this->subject->setPreviewStep('foo');
        $messages = $this->subject->synchronize(
                'tx_externalimporttest_tag',
                0
        );
        self::assertCount(
                1,
                $messages[\TYPO3\CMS\Core\Messaging\AbstractMessage::WARNING]
        );
    }

    /**
     * @test
     */
    public function runPreviewOnCheckPermissionsStepReturnsNull()
    {
        $this->subject->setPreviewStep(\Cobweb\ExternalImport\Step\CheckPermissionsStep::class);
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
    public function runPreviewOnValidateConfigurationStepReturnsNull()
    {
        $this->subject->setPreviewStep(\Cobweb\ExternalImport\Step\ValidateConfigurationStep::class);
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
    public function runPreviewOnValidateConnectorStepReturnsNull()
    {
        $this->subject->setPreviewStep(\Cobweb\ExternalImport\Step\ValidateConnectorStep::class);
        $messages = $this->subject->synchronize(
                'tx_externalimporttest_tag',
                0
        );
        self::assertNull(
                $this->subject->getPreviewData()
        );
    }

    public function readPreviewProvider()
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
    public function runPreviewOnReadDataStepReturnsRawData($table, $index, $result)
    {
        $this->subject->setPreviewStep(\Cobweb\ExternalImport\Step\ReadDataStep::class);
        $messages = $this->subject->synchronize(
                $table,
                $index
        );
        // The result variable, may be pointing to a file, in which case we want to read it
        if (is_string($result) && strpos($result, 'EXT:') === 0) {
            $result = file_get_contents(
                    GeneralUtility::getFileAbsFileName($result)
            );
        }
        self::assertSame(
                $this->subject->getPreviewData(),
                $result
        );
    }

    public function handlePreviewProvider()
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
    public function runPreviewOnHandleDataStepReturnsHandledData($table, $index, $result)
    {
        $this->subject->setPreviewStep(\Cobweb\ExternalImport\Step\HandleDataStep::class);
        $messages = $this->subject->synchronize(
                $table,
                $index
        );
        self::assertSame(
                $this->subject->getPreviewData(),
                $result
        );
    }

    /**
     * @test
     */
    public function runPreviewOnValidateDataStepReturnsNull()
    {
        $this->subject->setPreviewStep(\Cobweb\ExternalImport\Step\ValidateDataStep::class);
        $messages = $this->subject->synchronize(
                'tx_externalimporttest_tag',
                0
        );
        self::assertNull(
                $this->subject->getPreviewData()
        );
    }

    public function transformPreviewProvider()
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
                                        'attributes' => "<ul>\n\t\t\t<li>Robust</li>\n\t\t\t<li>Stylish</li>\n\t\t\t<li>Reliable</li>\n\t\t</ul>",
                                        'categories' => '',
                                        'name' => 'Long sword (base)',
                                        'sku' => '000001'
                                ],
                                [
                                        'attributes' => "<ul>\n\t\t\t<li>Not too cumbersome</li>\n\t\t\t<li>Lets the air flow</li>\n\t\t</ul>",
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
    public function runPreviewOnTransformDataStepReturnsTransformedData($table, $index, $result)
    {
        $this->subject->setPreviewStep(\Cobweb\ExternalImport\Step\TransformDataStep::class);
        $messages = $this->subject->synchronize(
                $table,
                $index
        );
        self::assertSame(
                $this->subject->getPreviewData(),
                $result
        );
    }

    public function storePreviewProvider()
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
                                                        'attributes' => "<ul>\n\t\t\t<li>Robust</li>\n\t\t\t<li>Stylish</li>\n\t\t\t<li>Reliable</li>\n\t\t</ul>",
                                                        'categories' => '',
                                                        'name' => 'Long sword (base)',
                                                        'sku' => '000001',
                                                        'tags' => '1,3',
                                                        'pid' => 1
                                                ],
                                                'NEW2' => [
                                                        'attributes' => "<ul>\n\t\t\t<li>Not too cumbersome</li>\n\t\t\t<li>Lets the air flow</li>\n\t\t</ul>",
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
                                                        'attributes' => "<ul>\n\t\t\t<li>Robust</li>\n\t\t\t<li>Stylish</li>\n\t\t\t<li>Reliable</li>\n\t\t</ul>",
                                                        'categories' => '',
                                                        'name' => 'Long sword (base)',
                                                        'sku' => '000001'
                                                ],
                                                '2' => [
                                                        'attributes' => "<ul>\n\t\t\t<li>Not too cumbersome</li>\n\t\t\t<li>Lets the air flow</li>\n\t\t</ul>",
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
                                                        'pid' => '2',
                                                        'sku' => '000001'
                                                ]
                                        ]
                                ],
                                'commands-delete' => [],
                                'commands-move' => [
                                        'tx_externalimporttest_product' => [
                                                '1' => [
                                                        'move' => '2'
                                                ]
                                        ]
                                ]
                        ]
                ],
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
    public function runPreviewOnStoreDataStepReturnsStorageDataAndWritesNothingToDatabase($fixtures, $prerequisites, $table, $index, $testDatabase, $recordsCount, $result)
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
        $this->subject->setPreviewStep(\Cobweb\ExternalImport\Step\StoreDataStep::class);
        $this->subject->setTestMode(true);
        $messages = $this->subject->synchronize(
                $table,
                $index
        );
        // Test the preview data
        self::assertSame(
                $this->subject->getPreviewData(),
                $result
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

    public function clearCachePreviewProvider()
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
    public function runPreviewOnClearCacheStepReturnsCacheListAndClearsNothing($table, $index, $result)
    {
        try {
            $this->importDataSet(__DIR__ . '/Fixtures/ClearCacheStepPreviewTest.xml');
        } catch (\Exception $e) {
            self::markTestSkipped('Could not load fixture file');
        }
        $this->subject->setPreviewStep(\Cobweb\ExternalImport\Step\ClearCacheStep::class);
        $this->subject->setTestMode(true);
        $messages = $this->subject->synchronize(
                $table,
                $index
        );
        self::assertSame(
                $this->subject->getPreviewData(),
                $result
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
    public function runPreviewOnConnectorCallbackStepReturnsNull()
    {
        $this->subject->setPreviewStep(\Cobweb\ExternalImport\Step\ConnectorCallbackStep::class);
        $messages = $this->subject->synchronize(
                'tx_externalimporttest_tag',
                0
        );
        self::assertNull(
                $this->subject->getPreviewData()
        );
    }
}