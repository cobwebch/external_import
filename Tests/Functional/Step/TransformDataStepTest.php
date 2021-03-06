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

use Cobweb\ExternalImport\Domain\Model\Configuration;
use Cobweb\ExternalImport\Step\TransformDataStep;
use Cobweb\ExternalImport\Transformation\DateTimeTransformation;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Test suite for the TransformDataStep class.
 *
 * @package Cobweb\ExternalImport\Tests\Unit\Validator
 */
class TransformDataStepTest extends FunctionalTestCase
{
    protected $testExtensionsToLoad = [
            'typo3conf/ext/external_import',
            'typo3conf/ext/externalimport_test'
    ];

    /**
     * @var TransformDataStep
     */
    protected $subject;

    public function setUp()
    {
        parent::setUp();
        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        $this->subject = $objectManager->get(\Cobweb\ExternalImport\Step\TransformDataStep::class);
        $importer = $this->createMock(\Cobweb\ExternalImport\Importer::class);
        $configuration = $objectManager->get(\Cobweb\ExternalImport\Domain\Model\Configuration::class);
        $configuration->setTable('foo');
        $importer->method('getExternalConfiguration')->willReturn($configuration);
        $this->subject->setImporter(
                $importer
        );
    }

    public function trimDataProvider(): array
    {
        return [
                'Trim data (true)' => [
                        'foo',
                        true,
                        [
                                0 => [
                                        'foo' => ' White space all around ',
                                        'bar' => ' Not trimmed '
                                ],
                                1 => [
                                        'foo' => ' White space left',
                                        'bar' => ' Not trimmed '
                                ],
                                2 => [
                                        'foo' => 'White space right ',
                                        'bar' => ' Not trimmed '
                                ],
                                3 => [
                                        'foo' => 'No white space',
                                        'bar' => ' Not trimmed '
                                ]
                        ],
                        [
                                0 => [
                                        'foo' => 'White space all around',
                                        'bar' => ' Not trimmed '
                                ],
                                1 => [
                                        'foo' => 'White space left',
                                        'bar' => ' Not trimmed '
                                ],
                                2 => [
                                        'foo' => 'White space right',
                                        'bar' => ' Not trimmed '
                                ],
                                3 => [
                                        'foo' => 'No white space',
                                        'bar' => ' Not trimmed '
                                ]
                        ]
                ],
                'Do not trim data (false)' => [
                        'foo',
                        false,
                        [
                                0 => [
                                        'foo' => ' White space all around ',
                                        'bar' => ' Not trimmed '
                                ],
                                1 => [
                                        'foo' => 'No white space',
                                        'bar' => ' Not trimmed '
                                ]
                        ],
                        [
                                0 => [
                                        'foo' => ' White space all around ',
                                        'bar' => ' Not trimmed '
                                ],
                                1 => [
                                        'foo' => 'No white space',
                                        'bar' => ' Not trimmed '
                                ]
                        ]
                ]
        ];
    }

    /**
     * Tests the applyTrim() method.
     *
     * @param string $name Name of the column to transform
     * @param bool $configuration True if data needs to be trimmed
     * @param array $records Records to handle
     * @param array $expected Expected results
     * @test
     * @dataProvider trimDataProvider
     */
    public function applyTrimTrimsDataIfTrue($name, $configuration, array $records, array $expected): void
    {
        $result = $this->subject->applyTrim(
                $name,
                $configuration,
                $records
        );
        self::assertSame($expected, $result);
    }

    public function mappingDataProvider(): array
    {
        return [
            'Map to sys_category with default value' => [
                    'foo',
                    [
                            'table' => 'sys_category',
                            'referenceField' => 'external_key',
                            'default' => 19
                    ],
                    [
                            0 => [
                                    'foo' => 'USEFUL',
                                    'bar' => 42
                            ],
                            1 => [
                                    'foo' => 'USELESS',
                                    'bar' => 17
                            ],
                            2 => [
                                    'foo' => 'UNKNOWN',
                                    'bar' => 24
                            ],
                    ],
                    [
                            0 => [
                                    'foo' => '1',
                                    'bar' => 42
                            ],
                            1 => [
                                    'foo' => '2',
                                    'bar' => 17
                            ],
                            2 => [
                                    'foo' => 19,
                                    'bar' => 24
                            ]
                    ]
            ],
            'Map to sys_category without default value' => [
                    'foo',
                    [
                            'table' => 'sys_category',
                            'referenceField' => 'external_key'
                    ],
                    [
                            0 => [
                                    'foo' => 'USEFUL',
                                    'bar' => 42
                            ],
                            1 => [
                                    'foo' => 'USELESS',
                                    'bar' => 17
                            ],
                            2 => [
                                    'foo' => 'UNKNOWN',
                                    'bar' => 24
                            ],
                    ],
                    [
                            0 => [
                                    'foo' => '1',
                                    'bar' => 42
                            ],
                            1 => [
                                    'foo' => '2',
                                    'bar' => 17
                            ],
                            2 => [
                                    'bar' => 24
                            ]
                    ]
            ]
        ];
    }

    /**
     * Tests the applyMapping() method.
     *
     * @param string $name Name of the column to transform
     * @param array $configuration Mapping configuration
     * @param array $records Records to handle
     * @param array $expected Expected results
     * @test
     * @dataProvider mappingDataProvider
     * @throws \Nimut\TestingFramework\Exception\Exception
     */
    public function applyMappingMapsData($name, $configuration, array $records, array $expected): void
    {
        $this->importDataSet(__DIR__ . '/../Fixtures/Categories.xml');
        $result = $this->subject->applyMapping(
                $name,
                $configuration,
                $records
        );
        self::assertSame($expected, $result);
    }

    /**
     * Tests the applyValue() method.
     *
     * @test
     */
    public function applyValueAppliesValue(): void
    {
        $result = $this->subject->applyValue(
                'foo',
                42,
                [
                        0 => [
                                'foo' => 17,
                                'bar' => 4
                        ],
                        1 => [
                                'foo' => 23,
                                'bar' => 8
                        ]
                ]
        );
        self::assertSame(
                [
                        0 => [
                                'foo' => 42,
                                'bar' => 4
                        ],
                        1 => [
                                'foo' => 42,
                                'bar' => 8
                        ]
                ],
                $result
        );
    }

    /**
     * Tests the applyValue() method.
     *
     * @test
     */
    public function applyRteEnabledFlagAppliesFlag(): void
    {
        $result = $this->subject->applyRteEnabledFlag(
                'foo',
                true,
                [
                        0 => [
                                'foo' => 17,
                                'bar' => 4
                        ],
                        1 => [
                                'foo' => 23,
                                'bar' => 8
                        ]
                ]
        );
        self::assertSame(
                [
                        0 => [
                                'foo' => 17,
                                'bar' => 4,
                                '_TRANSFORM_foo' => 'RTE'
                        ],
                        1 => [
                                'foo' => 23,
                                'bar' => 8,
                                '_TRANSFORM_foo' => 'RTE'
                        ]
                ],
                $result
        );
    }

    public function userFuncDataProvider(): array
    {
        return [
                'Valid configuration - data transformed' => [
                        'foo',
                        [
                                'class' => DateTimeTransformation::class,
                                'method' => 'parseDate',
                                'parameters' => [
                                        'function' => 'date',
                                        'format' => 'U'
                                ]
                        ],
                        [
                                0 => [
                                        'foo' => '2017-10-11T18:29:01+02:00',
                                        'bar' => 4
                                ]
                        ],
                        [
                                0 => [
                                        'foo' => '1507739341',
                                        'bar' => 4
                                ]
                        ]
                ]
        ];
    }

    /**
     * Tests the applyUserFunction() method.
     *
     * @param string $name Name of the column to transform
     * @param array $configuration True if data needs to be trimmed
     * @param array $records Records to handle
     * @param array $expected Expected results
     * @test
     * @dataProvider userFuncDataProvider
     */
    public function applyUserFunctionTransformsDataIfValid($name, $configuration, array $records, array $expected): void
    {
        $result = $this->subject->applyUserFunction(
                $name,
                $configuration,
                $records
        );
        self::assertSame($expected, $result);
    }
}
