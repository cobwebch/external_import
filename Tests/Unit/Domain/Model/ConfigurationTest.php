<?php

declare(strict_types=1);

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

namespace Cobweb\ExternalImport\Tests\Unit\Domain\Model;

use Cobweb\ExternalImport\Domain\Model\Configuration;
use Cobweb\ExternalImport\Importer;
use Cobweb\ExternalImport\Step\StoreDataStep;
use Cobweb\Svconnector\Service\ConnectorBase;
use Cobweb\SvconnectorFeed\Service\ConnectorFeed;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ConfigurationTest extends UnitTestCase
{
    protected Configuration $subject;

    public function setUp(): void
    {
        parent::setUp();
        $this->subject = GeneralUtility::makeInstance(Configuration::class);
    }

    #[Test]
    public function getAdditionalFieldsInitiallyReturnsEmptyArray(): void
    {
        self::assertSame(
            [],
            $this->subject->getAdditionalFields()
        );
    }

    #[Test]
    public function getColumnConfigurationInitiallyReturnsEmptyArray(): void
    {
        self::assertSame(
            [],
            $this->subject->getColumnConfiguration()
        );
    }

    #[Test]
    public function getConfigurationForColumnInitiallyReturnsEmptyArray(): void
    {
        self::assertSame(
            [],
            $this->subject->getConfigurationForColumn('foo')
        );
    }

    #[Test]
    public function getConnectorInitiallyReturnsNull(): void
    {
        self::assertNull(
            $this->subject->getConnector()
        );
    }

    #[Test]
    public function getCountAdditionalFieldsInitiallyReturnsZero(): void
    {
        self::assertSame(
            0,
            $this->subject->getCountAdditionalFields()
        );
    }

    #[Test]
    public function getGenerallConfigurationInitiallyReturnsEmptyArray(): void
    {
        self::assertSame(
            [],
            $this->subject->getGeneralConfiguration()
        );
    }

    #[Test]
    public function getGenerallConfigurationPropertyInitiallyReturnsNull(): void
    {
        self::assertNull(
            $this->subject->getGeneralConfigurationProperty('foo')
        );
    }

    #[Test]
    public function getIndexInitiallyReturnsNull(): void
    {
        self::assertNull(
            $this->subject->getIndex()
        );
    }

    #[Test]
    public function getStepsInitiallyReturnsEmptyArray(): void
    {
        self::assertSame(
            [],
            $this->subject->getSteps()
        );
    }

    #[Test]
    public function hasParametersForStepInitiallyReturnsFalse(): void
    {
        self::assertFalse(
            $this->subject->hasParametersForStep(StoreDataStep::class)
        );
    }

    #[Test]
    public function getParametersForStepInitiallyReturnsEmptyArray(): void
    {
        self::assertSame(
            [],
            $this->subject->getParametersForStep(StoreDataStep::class)
        );
    }

    #[Test]
    public function setParametersForStepSetsParameters(): void
    {
        $parameters = ['foo', 'bar' => 'baz'];
        $this->subject->setParametersForStep(
            $parameters,
            StoreDataStep::class
        );
        self::assertSame(
            $parameters,
            $this->subject->getParametersForStep(StoreDataStep::class)
        );
    }

    #[Test]
    public function getStoragePidInitiallyReturnsNull(): void
    {
        self::assertNull(
            $this->subject->getStoragePid()
        );
    }

    #[Test]
    public function getTableInitiallyReturnsNull(): void
    {
        self::assertNull(
            $this->subject->getTable()
        );
    }

    public static function ctrlConfigurationProvider(): array
    {
        return [
            'sample configuration' => [
                'configuration' => [
                    'connector' => 'feed',
                    'pid' => 42,
                    'additionalFields' => 'foo,bar',
                ],
                'pid' => 42,
            ],
        ];
    }

    #[Test] #[DataProvider('ctrlConfigurationProvider')]
    public function setGeneralConfigurationSetsGeneralConfigurationAndMore(array $configuration, int $pid): void
    {
        $this->subject->setGeneralConfiguration($configuration);
        self::assertSame(
            $configuration,
            $this->subject->getGeneralConfiguration()
        );
        self::assertEquals(
            $pid,
            $this->subject->getStoragePid()
        );
        self::assertSame(
            Importer::SYNCHRONYZE_DATA_STEPS,
            $this->subject->getSteps()
        );
    }

    public static function columnConfigurationProvider(): array
    {
        return [
            'configuration without additional fields' => [
                'additionalFieldsConfiguration' => [],
                'columnConfiguration' => [
                    'foo' => [
                        'field' => 'bar',
                        'transformations' => [
                            20 => [
                                'value' => 3,
                            ],
                            10 => [
                                'value' => 4,
                            ],
                        ],
                    ],
                ],
                'columnName' => 'foo',
                'processedConfiguration' => [
                    'field' => 'bar',
                    'transformations' => [
                        10 => [
                            'value' => 4,
                        ],
                        20 => [
                            'value' => 3,
                        ],
                    ],
                ],
            ],
            'configuration with additional fields' => [
                'additionalFieldsConfiguration' => [
                    'baz' => [
                        'field' => 'baz',
                    ],
                ],
                'columnConfiguration' => [
                    'foo' => [
                        'field' => 'bar',
                        'transformations' => [
                            20 => [
                                'value' => 3,
                            ],
                            10 => [
                                'value' => 4,
                            ],
                        ],
                    ],
                ],
                'columnName' => 'baz',
                'processedConfiguration' => [
                    'field' => 'baz',
                    Configuration::DO_NOT_SAVE_KEY => true,
                ],
            ],
        ];
    }

    #[Test] #[DataProvider('columnConfigurationProvider')]
    public function setColumnConfigurationSetsConfigurationAndSortsTransformations(array $additionalFieldsConfiguration, array $columnConfiguration, string $columnName, array $processedConfiguration): void
    {
        if (count($additionalFieldsConfiguration) > 0) {
            $this->subject->setAdditionalFields($additionalFieldsConfiguration);
        }
        $this->subject->setColumnConfiguration($columnConfiguration);
        self::assertSame(
            $processedConfiguration,
            $this->subject->getConfigurationForColumn($columnName)
        );
    }

    #[Test]
    public function setAdditionalFieldsSetsFieldsAndCount(): void
    {
        $additionalFields = [
            'foo' => [
                'field' => 'foo',
            ],
            'bar' => [
                'field' => 'bar',
            ],
        ];
        $this->subject->setAdditionalFields($additionalFields);
        // When set, additional fields get extra data attached to them
        $additionalFields['foo'][Configuration::DO_NOT_SAVE_KEY] = true;
        $additionalFields['bar'][Configuration::DO_NOT_SAVE_KEY] = true;
        self::assertSame(
            $additionalFields,
            $this->subject->getAdditionalFields()
        );
        self::assertEquals(
            count($additionalFields),
            $this->subject->getCountAdditionalFields()
        );
    }

    #[Test]
    public function setConnectorSetsConnector(): void
    {
        // Use a mock as full initialization requires to be in a functional testing environment
        /** @var ConnectorBase $connector */
        $connector = $this->getAccessibleMock(
            ConnectorFeed::class,
            [],
            [],
            '',
            false
        );
        $this->subject->setConnector($connector);
        self::assertSame(
            $connector,
            $this->subject->getConnector()
        );
    }

    #[Test]
    public function setCountAdditionalFieldsSetsCount(): void
    {
        $countAdditionalFields = 2;
        $this->subject->setCountAdditionalFields($countAdditionalFields);
        self::assertEquals(
            $countAdditionalFields,
            $this->subject->getCountAdditionalFields()
        );
    }

    #[Test]
    public function setIndexSetsIndex(): void
    {
        $index = 2;
        $this->subject->setIndex($index);
        self::assertEquals(
            $index,
            $this->subject->getIndex()
        );
    }

    #[Test]
    public function setStoragePidSetsPid(): void
    {
        $storagePid = 2;
        $this->subject->setStoragePid($storagePid);
        self::assertEquals(
            $storagePid,
            $this->subject->getStoragePid()
        );
    }

    #[Test]
    public function setTableSetsTable(): void
    {
        $table = 'tx_foo_bar';
        $this->subject->setTable($table);
        self::assertEquals(
            $table,
            $this->subject->getTable()
        );
    }

    public static function sortColumnsProvider(): array
    {
        return [
            'No sorting - output ordered by key, as per default' => [
                'columns' => [
                    'aa' => 'bar',
                    'bb' => 'foo',
                    'cc' => 'baz',
                    'dd' => 'foo2',
                ],
                'order' => '',
                'orderedColumns' => [
                    'aa' => 'bar',
                    'bb' => 'foo',
                    'cc' => 'baz',
                    'dd' => 'foo2',
                ],
            ],
            'Sorting - all columns ordered specifically' => [
                'columns' => [
                    'aa' => 'bar',
                    'bb' => 'foo',
                    'cc' => 'baz',
                    'dd' => 'foo2',
                ],
                'order' => 'dd, aa, bb, cc',
                'orderedColumns' => [
                    'dd' => 'foo2',
                    'aa' => 'bar',
                    'bb' => 'foo',
                    'cc' => 'baz',
                ],
            ],
            'Sorting - only some columns ordered' => [
                'columns' => [
                    'aa' => 'bar',
                    'bb' => 'foo',
                    'cc' => 'baz',
                    'dd' => 'foo2',
                ],
                'order' => 'bb, cc',
                'orderedColumns' => [
                    'bb' => 'foo',
                    'cc' => 'baz',
                    'aa' => 'bar',
                    'dd' => 'foo2',
                ],
            ],
            'Sorting - invalid columns are ignored' => [
                'columns' => [
                    'aa' => 'bar',
                    'bb' => 'foo',
                    'cc' => 'baz',
                    'dd' => 'foo2',
                ],
                'order' => 'bb, ff, cc',
                'orderedColumns' => [
                    'bb' => 'foo',
                    'cc' => 'baz',
                    'aa' => 'bar',
                    'dd' => 'foo2',
                ],
            ],
            'Sorting - duplicate columns are ignored, first occurrence is considered' => [
                'columns' => [
                    'aa' => 'bar',
                    'bb' => 'foo',
                    'cc' => 'baz',
                    'dd' => 'foo2',
                ],
                'order' => 'bb, cc, bb',
                'orderedColumns' => [
                    'bb' => 'foo',
                    'cc' => 'baz',
                    'aa' => 'bar',
                    'dd' => 'foo2',
                ],
            ],
        ];
    }

    #[Test] #[DataProvider('sortColumnsProvider')]
    public function sortColumnsSortsColumns(array $columns, string $order, array $orderedColumns): void
    {
        self::assertSame(
            $this->subject->sortColumns(
                $columns,
                $order
            ),
            $orderedColumns
        );
    }
}
