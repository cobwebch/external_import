<?php
namespace Cobweb\ExternalImport\Tests\Unit\Domain\Model;

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
use Cobweb\ExternalImport\Importer;
use Cobweb\SvconnectorFeed\Service\ConnectorFeed;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ConfigurationTest extends UnitTestCase
{
    /**
     * @var Configuration
     */
    protected $subject;

    public function setUp()
    {
        parent::setUp();
        $this->subject = GeneralUtility::makeInstance(Configuration::class);
    }

    /**
     * @test
     */
    public function getAdditionalFieldsInitiallyReturnsEmptyArray(): void
    {
        self::assertSame(
                [],
                $this->subject->getAdditionalFields()
        );
    }

    /**
     * @test
     */
    public function getColumnConfigurationInitiallyReturnsEmptyArray(): void
    {
        self::assertSame(
                [],
                $this->subject->getColumnConfiguration()
        );
    }

    /**
     * @test
     */
    public function getConfigurationForColumnInitiallyReturnsEmptyArray(): void
    {
        self::assertSame(
                [],
                $this->subject->getConfigurationForColumn('foo')
        );
    }

    /**
     * @test
     */
    public function getConnectorInitiallyReturnsNull(): void
    {
        self::assertNull(
                $this->subject->getConnector()
        );
    }

    /**
     * @test
     */
    public function getCountAdditionalFieldsInitiallyReturnsZero(): void
    {
        self::assertSame(
                0,
                $this->subject->getCountAdditionalFields()
        );
    }

    /**
     * @test
     */
    public function getGenerallConfigurationInitiallyReturnsEmptyArray(): void
    {
        self::assertSame(
                [],
                $this->subject->getGeneralConfiguration()
        );
    }

    /**
     * @test
     */
    public function getGenerallConfigurationPropertyInitiallyReturnsNull(): void
    {
        self::assertNull(
                $this->subject->getGeneralConfigurationProperty('foo')
        );
    }

    /**
     * @test
     */
    public function getIndexInitiallyReturnsNull(): void
    {
        self::assertNull(
                $this->subject->getIndex()
        );
    }

    /**
     * @test
     */
    public function getStepsInitiallyReturnsEmptyArray(): void
    {
        self::assertSame(
                [],
                $this->subject->getSteps()
        );
    }

    /**
     * @test
     */
    public function hasParametersForStepInitiallyReturnsFalse(): void
    {
        self::assertFalse(
                $this->subject->hasParametersForStep(\Cobweb\ExternalImport\Step\StoreDataStep::class)
        );
    }

    /**
     * @test
     */
    public function getParametersForStepInitiallyReturnsEmptyArray(): void
    {
        self::assertSame(
                [],
                $this->subject->getParametersForStep(\Cobweb\ExternalImport\Step\StoreDataStep::class)
        );
    }

    /**
     * @test
     */
    public function setParametersForStepSetsParameters(): void
    {
        $parameters = ['foo', 'bar' => 'baz'];
        $this->subject->setParametersForStep(
                $parameters,
                \Cobweb\ExternalImport\Step\StoreDataStep::class
        );
        self::assertSame(
                $parameters,
                $this->subject->getParametersForStep(\Cobweb\ExternalImport\Step\StoreDataStep::class)
        );
    }

    /**
     * @test
     */
    public function getStoragePidInitiallyReturnsNull(): void
    {
        self::assertNull(
                $this->subject->getStoragePid()
        );
    }

    /**
     * @test
     */
    public function getTableInitiallyReturnsNull(): void
    {
        self::assertNull(
                $this->subject->getTable()
        );
    }

    public function ctrlConfigurationProvider(): array
    {
        return [
                'sample configuration' => [
                        [
                                'connector' => 'feed',
                                'pid' => 42,
                                'additionalFields' => 'foo,bar'
                        ],
                        42
                ]
        ];
    }

    /**
     * @test
     * @param array $configuration
     * @param int $pid
     * @dataProvider ctrlConfigurationProvider
     */
    public function setGeneralConfigurationSetsGeneralConfigurationAndMore($configuration, $pid): void
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

    public function columnConfigurationProvider(): array
    {
        return [
                'configuration without additional fields' => [
                        [],
                        [
                                'foo' => [
                                        'field' => 'bar',
                                        'transformations' => [
                                                20 => [
                                                        'value' => 3
                                                ],
                                                10 => [
                                                        'value' => 4
                                                ]
                                        ]
                                ]
                        ],
                        'foo',
                        [
                                'field' => 'bar',
                                'transformations' => [
                                        10 => [
                                                'value' => 4
                                        ],
                                        20 => [
                                                'value' => 3
                                        ]
                                ]
                        ]
                ],
                'configuration with additional fields' => [
                        [
                                'baz' => [
                                        'field' => 'baz'
                                ]
                        ],
                        [
                                'foo' => [
                                        'field' => 'bar',
                                        'transformations' => [
                                                20 => [
                                                        'value' => 3
                                                ],
                                                10 => [
                                                        'value' => 4
                                                ]
                                        ]
                                ]
                        ],
                        'baz',
                        [
                                'field' => 'baz',
                                Configuration::DO_NOT_SAVE_KEY => true
                        ]
                ]
        ];
    }

    /**
     * @test
     * @dataProvider columnConfigurationProvider
     * @param array $additionalFieldsConfiguration
     * @param array $columnConfiguration
     * @param string $columnName
     * @param array $processedConfiguration
     */
    public function setColumnConfigurationSetsConfigurationAndSortsTransformations($additionalFieldsConfiguration, $columnConfiguration, $columnName, $processedConfiguration): void
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

    /**
     * @test
     */
    public function setAdditionalFieldsSetsFieldsAndCount(): void
    {
        $additionalFields = [
                'foo' => [
                        'field' => 'foo'
                ],
                'bar' => [
                        'field' => 'bar'
                ]
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

    /**
     * @test
     */
    public function setConnectorSetsConnector(): void
    {
        $connector = GeneralUtility::makeInstance(ConnectorFeed::class);
        $this->subject->setConnector($connector);
        self::assertSame(
                $connector,
                $this->subject->getConnector()
        );
    }

    /**
     * @test
     */
    public function setCountAdditionalFieldsSetsCount(): void
    {
        $countAdditionalFields = 2;
        $this->subject->setCountAdditionalFields($countAdditionalFields);
        self::assertEquals(
                $countAdditionalFields,
                $this->subject->getCountAdditionalFields()
        );
    }

    /**
     * @test
     */
    public function setIndexSetsIndex(): void
    {
        $index = 2;
        $this->subject->setIndex($index);
        self::assertEquals(
                $index,
                $this->subject->getIndex()
        );
    }

    /**
     * @test
     */
    public function setStoragePidSetsPid(): void
    {
        $storagePid = 2;
        $this->subject->setStoragePid($storagePid);
        self::assertEquals(
                $storagePid,
                $this->subject->getStoragePid()
        );
    }

    /**
     * @test
     */
    public function setTableSetsTable(): void
    {
        $table = 'tx_foo_bar';
        $this->subject->setTable($table);
        self::assertEquals(
                $table,
                $this->subject->getTable()
        );
    }
}