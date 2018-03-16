<?php
namespace Cobweb\ExternalImport\Tests\Domain\Model;

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
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class ConfigurationTest extends FunctionalTestCase
{
    /**
     * @var Configuration
     */
    protected $subject;

    public function setUp()
    {
        parent::setUp();
        $objectManager = new ObjectManager();
        $this->subject = $objectManager->get(Configuration::class);
    }

    /**
     * @test
     */
    public function getAdditionalFieldsInitiallyReturnsEmptyArray()
    {
        self::assertSame(
                [],
                $this->subject->getAdditionalFields()
        );
    }

    /**
     * @test
     */
    public function getColumnConfigurationInitiallyReturnsNull()
    {
        self::assertNull(
                $this->subject->getColumnConfiguration()
        );
    }

    /**
     * @test
     */
    public function getConfigurationForColumnInitiallyReturnsEmptyArray()
    {
        self::assertSame(
                [],
                $this->subject->getConfigurationForColumn('foo')
        );
    }

    /**
     * @test
     */
    public function getConnectorInitiallyReturnsNull()
    {
        self::assertNull(
                $this->subject->getColumnConfiguration()
        );
    }

    /**
     * @test
     */
    public function getCountAdditionalFieldsInitiallyReturnsZero()
    {
        self::assertSame(
                0,
                $this->subject->getCountAdditionalFields()
        );
    }

    /**
     * @test
     */
    public function getCtrlConfigurationInitiallyReturnsNull()
    {
        self::assertNull(
                $this->subject->getCtrlConfiguration()
        );
    }

    /**
     * @test
     */
    public function getCtrlConfigurationPropertyInitiallyReturnsNull()
    {
        self::assertNull(
                $this->subject->getCtrlConfigurationProperty('foo')
        );
    }

    /**
     * @test
     */
    public function getIndexInitiallyReturnsNull()
    {
        self::assertNull(
                $this->subject->getIndex()
        );
    }

    /**
     * @test
     */
    public function getStepsInitiallyReturnsEmptyArray()
    {
        self::assertSame(
                [],
                $this->subject->getSteps()
        );
    }

    /**
     * @test
     */
    public function getStoragePidInitiallyReturnsNull()
    {
        self::assertNull(
                $this->subject->getStoragePid()
        );
    }

    /**
     * @test
     */
    public function getTableInitiallyReturnsNull()
    {
        self::assertNull(
                $this->subject->getTable()
        );
    }

    public function ctrlConfigurationProvider()
    {
        return [
                'sample configuration' => [
                        [
                                'connector' => 'feed',
                                'pid' => 42,
                                'additionalFields' => 'foo,bar'
                        ],
                        42,
                        ['foo', 'bar']
                ]
        ];
    }

    /**
     * @test
     * @param array $configuration
     * @param int $pid
     * @param array $additionalFields
     * @dataProvider ctrlConfigurationProvider
     */
    public function setCtrlConfigurationSetsCtrlConfigurationAndMore($configuration, $pid, $additionalFields) {
        $this->subject->setCtrlConfiguration($configuration);
        self::assertSame(
                $configuration,
                $this->subject->getCtrlConfiguration()
        );
        self::assertEquals(
                $pid,
                $this->subject->getStoragePid()
        );
        self::assertSame(
                Importer::SYNCHRONYZE_DATA_STEPS,
                $this->subject->getSteps()
        );
        self::assertSame(
                $additionalFields,
                $this->subject->getAdditionalFields()
        );
        self::assertEquals(
                count($additionalFields),
                $this->subject->getCountAdditionalFields()
        );
    }

    public function columnConfigurationProvider()
    {
        return [
                'sample configuration' => [
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
                ]
        ];
    }

    /**
     * @test
     * @dataProvider columnConfigurationProvider
     * @param array $configuration
     * @param string $columnName
     * @param array $processedConfiguration
     */
    public function setColumnConfigurationSetsConfigurationAndSortsTransformations($configuration, $columnName, $processedConfiguration)
    {
        $this->subject->setColumnConfiguration($configuration);
        self::assertSame(
                $processedConfiguration,
                $this->subject->getConfigurationForColumn($columnName)
        );
    }

    /**
     * @test
     */
    public function setAdditionalFieldsSetsFieldsAndCount()
    {
        $additionalFields = ['foo', 'bar'];
        $this->subject->setAdditionalFields($additionalFields);
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
    public function setConnectorSetsConnector()
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
    public function setCountAdditionalFieldsSetsCount()
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
    public function setIndexSetsIndex()
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
    public function setStoragePidSetsPid()
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
    public function setTableSetsTable()
    {
        $table = 'tx_foo_bar';
        $this->subject->setTable($table);
        self::assertEquals(
                $table,
                $this->subject->getTable()
        );
    }
}