<?php

declare(strict_types=1);

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

use Cobweb\ExternalImport\Domain\Model\Configuration;
use Cobweb\ExternalImport\Domain\Repository\ConfigurationRepository;
use Cobweb\ExternalImport\Testing\FunctionalTestCaseWithDatabaseTools;
use Cobweb\ExternalimportTest\UserFunction\Transformation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test suite for the ConfigurationRepository class.
 */
class ConfigurationRepositoryTest extends FunctionalTestCaseWithDatabaseTools
{
    protected array $coreExtensionsToLoad = [
        'scheduler',
    ];

    protected array $testExtensionsToLoad = [
        'cobweb/svconnector',
        'cobweb/svconnector_csv',
        'cobweb/svconnector_feed',
        'cobweb/svconnector_json',
        'cobweb/external_import',
        'cobweb/externalimport_test',
    ];

    protected ConfigurationRepository $subject;

    public function setUp(): void
    {
        parent::setUp();
        try {
            $this->initializeBackendUser();
            Bootstrap::initializeLanguageObject();

            $this->subject = GeneralUtility::makeInstance(ConfigurationRepository::class);
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

    #[Test]
    public function findByGroupFindsDesignatedGroup(): void
    {
        $groups = $this->subject->findByGroup('Products');
        self::assertCount(
            3,
            $groups
        );
    }

    #[Test]
    public function findAllGroupsReturnsListOfGroups(): void
    {
        self::assertSame(
            [
                'Products',
            ],
            $this->subject->findAllGroups()
        );
    }

    public static function syncFlagProvider(): array
    {
        return [
            'sync is true' => [
                'sync' => true,
                'expectedCount' => 16,
            ],
            'sync is false' => [
                'sync' => false,
                'expectedCount' => 1,
            ],
        ];
    }

    #[Test] #[DataProvider('syncFlagProvider')]
    public function findBySyncFindsCorrectCountOfConfigurations(bool $sync, int $expectedCount): void
    {
        // TODO: this is not very satisfying, because the default user provided by the backend user fixture is admin
        self::assertCount(
            $expectedCount,
            $this->subject->findBySync($sync)
        );
    }

    public static function findConfigurationProvider(): array
    {
        return [
            'simple configuration' => [
                'table' => 'tx_externalimporttest_bundle',
                'index' => 0,
                'expectedReferenceUidValue' => 'bundle_code',
                'testColumnName' => 'bundle_code',
                'expectedColumnConfiguration' => [
                    'field' => 'code',
                    'transformations' => [
                        10 => [
                            'trim' => true,
                        ],
                    ],
                ],
                'expectedAdditionalFieldsConfiguration' => [
                    'position' => [
                        'field' => 'position',
                        'transformations' => [
                            10 => [
                                'userFunction' => [
                                    'class' => Transformation::class,
                                    'method' => 'stripPositionMarker',
                                ],
                            ],
                        ],
                        Configuration::DO_NOT_SAVE_KEY => true,
                    ],
                ],
            ],
            'configuration with useColumnIndex and no specific configuration' => [
                'table' => 'tx_externalimporttest_product',
                'index' => 'stable',
                'expectedReferenceUidValue' => 'sku',
                'testColumnName' => 'sku',
                // NOTE: this is expected to match information from the "base" configuration,
                // since the "stable" configuration has the useColumnIndex property pointing to "base" configuration
                'expectedColumnConfiguration' => [
                    'xpath' => './self::*[@type="current"]/item',
                    'attribute' => 'sku',
                ],
                'expectedAdditionalFieldsConfiguration' => [],
            ],
            'configuration with useColumnIndex but specific configuration' => [
                'table' => 'tx_externalimporttest_product',
                'index' => 'stable',
                'expectedReferenceUidValue' => 'sku',
                'testColumnName' => 'name',
                // NOTE: in this case the "name" column has its own configuration, despite the use of useColumnIndex
                'expectedColumnConfiguration' => [
                    'xpath' => './self::*[@type="current"]/item',
                    'transformations' => [
                        10 => [
                            'userFunction' => [
                                'class' => Transformation::class,
                                'method' => 'caseTransformation',
                                'parameters' => [
                                    'transformation' => 'upper',
                                ],
                            ],
                        ],
                    ],
                ],
                'expectedAdditionalFieldsConfiguration' => [],
            ],
        ];
    }

    #[Test] #[DataProvider('findConfigurationProvider')]
    public function findConfigurationObjectReturnsExpectedConfiguration(
        string $table,
        mixed $index,
        string $expectedReferenceUidValue,
        string $testColumnName,
        array $expectedColumnConfiguration,
        array $expectedAdditionalFieldsConfiguration
    ): void {
        $configuration = $this->subject->findConfigurationObject(
            $table,
            $index
        );
        self::assertSame(
            $expectedReferenceUidValue,
            $configuration->getGeneralConfigurationProperty('referenceUid')
        );
        self::assertSame(
            $expectedColumnConfiguration,
            $configuration->getConfigurationForColumn($testColumnName)
        );
        self::assertSame(
            $expectedAdditionalFieldsConfiguration,
            $configuration->getAdditionalFields()
        );
    }

    #[Test]
    public function findGlobalWriteAccessReturnsAll(): void
    {
        // TODO: this is not very satisfying, because the default user provided by the backend user fixture is admin
        self::assertSame(
            'all',
            $this->subject->findGlobalWriteAccess()
        );
    }

    #[Test]
    public function findOrderedConfigurationsReturnsFullOrderedList(): void
    {
        $expectedList = [
            1000 => [
                ['table' => 'tx_externalimporttest_product', 'index' => 'general_configuration_errors', 'group' => '-'],
            ],
            5000 => [
                ['table' => 'tx_externalimporttest_tag', 'index' => 0, 'group' => '-'],
            ],
            5050 => [
                ['table' => 'sys_category', 'index' => 'product_categories', 'group' => '-'],
                ['table' => 'sys_category', 'index' => 'column_configuration_errors', 'group' => '-'],
            ],
            5080 => [
                ['table' => 'tx_externalimporttest_designer', 'index' => 0, 'group' => '-'],
            ],
            5100 => [
                ['table' => 'tx_externalimporttest_product', 'index' => 'base', 'group' => 'Products'],
            ],
            5110 => [
                ['table' => 'tx_externalimporttest_product', 'index' => 'more', 'group' => 'Products'],
            ],
            5120 => [
                ['table' => 'tx_externalimporttest_product', 'index' => 'stable', 'group' => 'Products'],
            ],
            5200 => [
                ['table' => 'tx_externalimporttest_bundle', 'index' => 0, 'group' => '-'],
            ],
            5300 => [
                ['table' => 'tx_externalimporttest_order', 'index' => 0, 'group' => '-'],
            ],
            5400 => [
                ['table' => 'tx_externalimporttest_store', 'index' => 0, 'group' => '-'],
            ],
            5410 => [
                ['table' => 'tx_externalimporttest_product', 'index' => 'products_for_stores', 'group' => '-'],
            ],
            5500 => [
                ['table' => 'tx_externalimporttest_invoice', 'index' => 0, 'group' => '-'],
            ],
            5800 => [
                ['table' => 'pages', 'index' => 'product_pages', 'group' => '-'],
            ],
            5810 => [
                ['table' => 'tx_externalimporttest_product', 'index' => 'updated_products', 'group' => '-'],
            ],
            5900 => [
                ['table' => 'tx_externalimporttest_tag', 'index' => 'only-delete', 'group' => '-'],
            ],
        ];
        self::assertSame(
            $expectedList,
            $this->subject->findOrderedConfigurations()
        );
    }

    #[Test]
    public function findByTableAndIndexReturnsExternalConfiguration(): void
    {
        $externalConfiguration = $this->subject->findByTableAndIndex(
            'tx_externalimporttest_bundle',
            0
        );
        self::assertSame(
            [
                'general' => [
                    'connector' => 'json',
                    'parameters' => [
                        'uri' => 'EXT:externalimport_test/Resources/Private/ImportData/Test/Bundles.json',
                    ],
                    'data' => 'array',
                    'referenceUid' => 'bundle_code',
                    'priority' => 5200,
                    'description' => 'List of bundles',
                    'pid' => 0,
                ],
                'additionalFields' => [
                    'position' => [
                        'field' => 'position',
                        'transformations' => [
                            10 => [
                                'userFunction' => [
                                    'class' => Transformation::class,
                                    'method' => 'stripPositionMarker',
                                ],
                            ],
                        ],
                    ],
                ],
                'columns' => [
                    'bundle_code' => [
                        'field' => 'code',
                        'transformations' => [
                            10 => [
                                'trim' => true,
                            ],
                        ],
                    ],
                    'maker' => [
                        'arrayPath' => 'maker/name',
                        'transformations' => [
                            10 => [
                                'trim' => true,
                            ],
                        ],
                    ],
                    'name' => [
                        'field' => 'name',
                        'transformations' => [
                            10 => [
                                'trim' => true,
                            ],
                        ],
                    ],
                    'notes' => [
                        'field' => 'notes',
                        'transformations' => [
                            10 => [
                                'trim' => true,
                            ],
                        ],
                    ],
                    'products' => [
                        'field' => 'product',
                        'multipleRows' => true,
                        'multipleSorting' => 'position',
                        'transformations' => [
                            10 => [
                                'mapping' => [
                                    'table' => 'tx_externalimporttest_product',
                                    'referenceField' => 'sku',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            $externalConfiguration
        );
    }

    #[Test]
    public function findByTableAndIndexWithWrongInformationThrowsException(): void
    {
        $this->expectException(\Cobweb\ExternalImport\Exception\NoConfigurationException::class);
        $this->subject->findByTableAndIndex(
            'foo',
            'bar'
        );
    }
}
