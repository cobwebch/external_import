<?php

namespace Cobweb\ExternalImport\Tests\Domain\Repository;

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

use Cobweb\ExternalImport\Domain\Repository\ConfigurationRepository;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Test suite for the ConfigurationRepository class.
 *
 * @package Cobweb\ExternalImport\Tests\Domain\Repository
 */
class ConfigurationRepositoryTest extends FunctionalTestCase
{
    protected $coreExtensionsToLoad = [
            'scheduler',
    ];

    protected $testExtensionsToLoad = [
            'typo3conf/ext/external_import',
            'typo3conf/ext/externalimport_test'
    ];

    /**
     * @var ConfigurationRepository
     */
    protected $subject;

    public function setUp()
    {
        parent::setUp();
        try {
            $this->setUpBackendUserFromFixture(1);
            // Configuration repository needs a global LanguageService object
            $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageService::class);

            $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
            $this->subject = $objectManager->get(ConfigurationRepository::class);
        }
        catch (\Exception $e) {
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
    public function findByGroupFindsDesignatedGroup(): void
    {
        $groups = $this->subject->findByGroup('Products');
        self::assertCount(
                3,
                $groups
        );
    }

    public function syncFlagProvider(): array
    {
        return [
                'sync is true' => [
                        true,
                        15
                ],
                'sync is false' => [
                        false,
                        1
                ]
        ];
    }

    /**
     * @test
     */
    public function findAllGroupsReturnsListOfGroups(): void
    {
        self::assertSame(
                [
                        'Products'
                ],
                $this->subject->findAllGroups()
        );
    }

    /**
     * @param bool $sync
     * @param int $expectedCount
     * @test
     * @dataProvider syncFlagProvider
     */
    public function findBySyncFindsCorrectCountOfConfigurations($sync, $expectedCount): void
    {
        // TODO: this is not very satisfying, because the default user provided by the backend user fixture is admin
        self::assertCount(
                $expectedCount,
                $this->subject->findBySync($sync)
        );
    }

    public function findConfigurationProvider(): array
    {
        return [
                'simple configuration' => [
                        // Table
                        'tx_externalimporttest_product',
                        // Index
                        'base',
                        // Sample test value from the ctrl configuration (property: nodetype)
                        'products',
                        // Sample test value from the columns configuration (column corresponding to referenceUid property, i.e. "sku")
                        [
                                'xpath' => './self::*[@type="current"]/item',
                                'attribute' => 'sku'
                        ]
                ],
                'configuration with useColumnIndex' => [
                        'tx_externalimporttest_product',
                        'stable',
                        // Same values as above, since useColumnIndex property points to "base" configuration
                        'products',
                        [
                                'xpath' => './self::*[@type="current"]/item',
                                'attribute' => 'sku'
                        ]
                ]
        ];
    }

    /**
     * @test
     * @dataProvider findConfigurationProvider
     * @param string $table
     * @param mixed $index
     * @param string $expectedCtrlValue
     * @param array $expectedColumnConfiguration
     */
    public function findConfigurationObjectReturnsExpectedConfiguration($table, $index, $expectedCtrlValue, $expectedColumnConfiguration): void
    {
        $configuration = $this->subject->findConfigurationObject(
                $table,
                $index
        );
        self::assertSame(
                $expectedCtrlValue,
                $configuration->getCtrlConfigurationProperty('nodetype')
        );
        self::assertSame(
                $expectedColumnConfiguration,
                $configuration->getConfigurationForColumn(
                        $configuration->getCtrlConfigurationProperty('referenceUid')
                )
        );
    }

    /**
     * @test
     */
    public function findGlobalWriteAccessReturnsAll(): void
    {
        // TODO: this is not very satisfying, because the default user provided by the backend user fixture is admin
        self::assertSame(
                'all',
                $this->subject->findGlobalWriteAccess()
        );
    }

    /**
     * @test
     */
    public function findOrderedConfigurationsReturnsFullOrderedList(): void
    {
        $expectedList = [
                1000 => [
                        ['table' => 'tx_externalimporttest_product', 'index' => 'control_configuration_errors', 'group' => '-']
                ],
                5000 => [
                        ['table' => 'tx_externalimporttest_tag', 'index' => 0, 'group' => '-']
                ],
                5050 => [
                        ['table' => 'sys_category', 'index' => 'product_categories', 'group' => '-'],
                        ['table' => 'sys_category', 'index' => 'column_configuration_errors', 'group' => '-']
                ],
                5080 => [
                        ['table' => 'tx_externalimporttest_designer', 'index' => 0, 'group' => '-']
                ],
                5100 => [
                        ['table' => 'tx_externalimporttest_product', 'index' => 'base', 'group' => 'Products']
                ],
                5110 => [
                        ['table' => 'tx_externalimporttest_product', 'index' => 'more', 'group' => 'Products']
                ],
                5120 => [
                        ['table' => 'tx_externalimporttest_product', 'index' => 'stable', 'group' => 'Products']
                ],
                5200 => [
                        ['table' => 'tx_externalimporttest_bundle', 'index' => 0, 'group' => '-']
                ],
                5300 => [
                        ['table' => 'tx_externalimporttest_order', 'index' => 0, 'group' => '-']
                ],
                5400 => [
                        ['table' => 'tx_externalimporttest_store', 'index' => 0, 'group' => '-']
                ],
                5410 => [
                        ['table' => 'tx_externalimporttest_product', 'index' => 'products_for_stores', 'group' => '-']
                ],
                5500 => [
                        ['table' => 'tx_externalimporttest_invoice', 'index' => 0, 'group' => '-']
                ],
                5800 => [
                        ['table' => 'pages', 'index' => 'product_pages', 'group' => '-']
                ],
                5810 => [
                        ['table' => 'tx_externalimporttest_product', 'index' => 'updated_products', 'group' => '-']
                ]
        ];
        self::assertSame(
                $expectedList,
                $this->subject->findOrderedConfigurations()
        );
    }

    /**
     * @test
     */
    public function findByTableAndIndexReturnsCtrlConfiguration(): void
    {
        // Use a very simple configuration as an example
        $ctrlConfiguration = $this->subject->findByTableAndIndex(
                'tx_externalimporttest_tag',
                'api'
        );
        self::assertSame(
                [
                        'data' => 'array',
                        'referenceUid' => 'code',
                        'description' => 'Tags defined via the import API',
                        'pid' => 0
                ],
                $ctrlConfiguration
        );
    }

    /**
     * @test
     */
    public function findColumnsByTableAndIndexReturnsCtrlConfiguration(): void
    {
        $columnsConfiguration = $this->subject->findColumnsByTableAndIndex(
                'tx_externalimporttest_order',
                0
        );
        self::assertSame(
                [
                        'client_id' => [
                                'field' => 'customer',
                                'transformations' => [
                                        10 => [
                                                'trim' => true
                                        ]
                                ]
                        ],
                        'order_date' => [
                                'field' => 'date',
                                'transformations' => [
                                        10 => [
                                                'userFunc' => [
                                                        'class' => \Cobweb\ExternalImport\Transformation\DateTimeTransformation::class,
                                                        'method' => 'parseDate',
                                                        'params' => [
                                                                'enforceTimeZone' => true
                                                        ]
                                                ]
                                        ]
                                ]
                        ],
                        'order_id' => [
                                'field' => 'order',
                                'transformations' => [
                                        10 => [
                                                'trim' => true
                                        ]
                                ]
                        ],
                        'products' => [
                                'field' => 'product',
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
                $columnsConfiguration
        );
    }
}