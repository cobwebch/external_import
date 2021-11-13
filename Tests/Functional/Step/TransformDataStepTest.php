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
use Cobweb\ExternalImport\Importer;
use Cobweb\ExternalImport\Step\TransformDataStep;
use Cobweb\ExternalImport\Transformation\DateTimeTransformation;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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

    public function setUp(): void
    {
        parent::setUp();
        $this->subject = GeneralUtility::makeInstance(TransformDataStep::class);
        $importer = $this->createMock(Importer::class);
        $configuration = GeneralUtility::makeInstance(Configuration::class);
        $configuration->setTable('foo');
        $importer->method('getExternalConfiguration')->willReturn($configuration);
        $this->subject->setImporter(
            $importer
        );
        // Global language object is needed for some localized log messages
        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageServiceFactory::class)->create('en');
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
            ],
            'Trim not string data' => [
                'foo',
                true,
                [
                    0 => [
                        'foo' => ['bar' => 'baz']
                    ],
                    1 => [
                        'foo' => 2
                    ],
                    2 => [
                        'foo' => true
                    ],
                    3 => [
                        'bar' => 'baz'
                    ]
                ],
                [
                    0 => [
                        'foo' => ['bar' => 'baz']
                    ],
                    1 => [
                        'foo' => 2
                    ],
                    2 => [
                        'foo' => true
                    ],
                    3 => [
                        'bar' => 'baz'
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
    public function applyTrimTrimsDataIfTrue(string $name, bool $configuration, array $records, array $expected): void
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
    public function applyMappingMapsData(string $name, array $configuration, array $records, array $expected): void
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

    public function userFunctionDataProvider(): array
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
     * @param array $configuration userFunction configuration
     * @param array $records Records to handle
     * @param array $expected Expected results
     * @test
     * @dataProvider userFunctionDataProvider
     */
    public function applyUserFunctionTransformsDataIfValid(string $name, array $configuration, array $records, array $expected): void
    {
        $result = $this->subject->applyUserFunction(
            $name,
            $configuration,
            $records
        );
        self::assertSame($expected, $result);
    }

    public function isEmptyDataProvider(): array
    {
        $emptyArray = [
            0 => [
                'foo' => 'Hello world',
                'bar' => 'Foo is not empty'
            ],
            1 => [
                'bar' => 'Foo is empty'
            ],
            2 => [
                'foo' => '',
                'bar' => 'Foo is empty'
            ],
            3 => [
                'foo' => 0,
                'bar' => 'Foo is empty'
            ],
            4 => [
                'foo' => false,
                'bar' => 'Foo is empty'
            ],
            5 => [
                'foo' => null,
                'bar' => 'Foo is empty'
            ]
        ];
        return [
            'No empty records - no expression - nothing happens' => [
                'name' => 'foo',
                'configuration' => [
                    'invalidate' => true
                ],
                'records' => [
                    0 => [
                        'foo' => 'Hello world',
                        'bar' => 'Foo is not empty'
                    ],
                    1 => [
                        'foo' => 'This is me!',
                        'bar' => 'Foo is not empty'
                    ]
                ],
                'expected' => [
                    0 => [
                        'foo' => 'Hello world',
                        'bar' => 'Foo is not empty'
                    ],
                    1 => [
                        'foo' => 'This is me!',
                        'bar' => 'Foo is not empty'
                    ]
                ]
            ],
            'Empty records - no expression - invalidate' => [
                'name' => 'foo',
                'configuration' => [
                    'invalidate' => true
                ],
                'records' => $emptyArray,
                'expected' => [
                    0 => [
                        'foo' => 'Hello world',
                        'bar' => 'Foo is not empty'
                    ]
                ]
            ],
            'Empty records - no expression - default value' => [
                'name' => 'foo',
                'configuration' => [
                    'default' => 'Foo is foo'
                ],
                'records' => $emptyArray,
                'expected' => [
                    0 => [
                        'foo' => 'Hello world',
                        'bar' => 'Foo is not empty'
                    ],
                    1 => [
                        'bar' => 'Foo is empty',
                        'foo' => 'Foo is foo'
                    ],
                    2 => [
                        'foo' => 'Foo is foo',
                        'bar' => 'Foo is empty'
                    ],
                    3 => [
                        'foo' => 'Foo is foo',
                        'bar' => 'Foo is empty'
                    ],
                    4 => [
                        'foo' => 'Foo is foo',
                        'bar' => 'Foo is empty'
                    ],
                    5 => [
                        'foo' => 'Foo is foo',
                        'bar' => 'Foo is empty'
                    ]
                ]
            ],
            'Empty records - expression (null) - invalidate' => [
                'name' => 'foo',
                'configuration' => [
                    'expression' => 'foo === null',
                    'invalidate' => true
                ],
                'records' => [
                    0 => [
                        'foo' => 'Hello world',
                        'bar' => 'Foo is not empty'
                    ],
                    1 => [
                        'bar' => 'Foo is empty'
                    ],
                    2 => [
                        'foo' => 'Me again :-)',
                        'bar' => 'Foo is not empty'
                    ]
                ],
                'expected' => [
                    0 => [
                        'foo' => 'Hello world',
                        'bar' => 'Foo is not empty'
                    ],
                    1 => [
                        'foo' => 'Me again :-)',
                        'bar' => 'Foo is not empty'
                    ]
                ]
            ],
            'Empty records - expression (empty string) - invalidate' => [
                'name' => 'foo',
                'configuration' => [
                    'expression' => 'foo === ""',
                    'invalidate' => true
                ],
                'records' => [
                    0 => [
                        'foo' => 'Hello world',
                        'bar' => 'Foo is not empty'
                    ],
                    1 => [
                        'foo' => '',
                        'bar' => 'Foo is empty'
                    ],
                    2 => [
                        'foo' => 'Me again :-)',
                        'bar' => 'Foo is not empty'
                    ]
                ],
                'expected' => [
                    0 => [
                        'foo' => 'Hello world',
                        'bar' => 'Foo is not empty'
                    ],
                    1 => [
                        'foo' => 'Me again :-)',
                        'bar' => 'Foo is not empty'
                    ]
                ]
            ],
            'Expression not testing emptiness' => [
                'name' => 'foo',
                'configuration' => [
                    'expression' => 'foo + bar',
                    'invalidate' => true
                ],
                'records' => [
                    // This first record will be removed, because 2 + 5 = 7, which is equivalent to true when casting to boolean
                    0 => [
                        'foo' => 2,
                        'bar' => 5
                    ],
                    1 => [
                        'foo' => 1,
                        'bar' => -1
                    ]
                ],
                'expected' => [
                    0 => [
                        'foo' => 1,
                        'bar' => -1
                    ]
                ]
            ]
        ];
    }

    /**
     * Tests the applyIsEmpty() method.
     *
     * @param string $name Name of the column to transform
     * @param array $configuration isEmpty configuration
     * @param array $records Records to handle
     * @param array $expected Expected results
     * @test
     * @dataProvider isEmptyDataProvider
     */
    public function applyIsEmptyFiltersRecordsOrSetsDefaultValue(string $name, array $configuration, array $records, array $expected): void
    {
        $result = $this->subject->applyIsEmpty(
            $name,
            $configuration,
            $records
        );
        self::assertSame($expected, $result);
    }
}
