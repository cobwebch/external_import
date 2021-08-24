<?php

declare(strict_types=1);

namespace Cobweb\ExternalImport\Tests\Unit\Handler;

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
use Cobweb\ExternalImport\Handler\ArrayHandler;
use Cobweb\ExternalImport\Importer;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * Test suite for the ArrayHandler class.
 *
 * @package Cobweb\ExternalImport\Tests\Unit\Handler
 */
class ArrayHandlerTest extends UnitTestCase
{
    /**
     * @var ArrayHandler
     */
    protected $subject;

    public function setUp()
    {
        parent::setUp();
        $this->subject = new ArrayHandler();
    }

    public function getValueSuccessProvider(): array
    {
        return [
            'direct simple value' => [
                'record' => [
                    'foo' => 'bar'
                ],
                'configuration' => [
                    'field' => 'foo'
                ],
                'result' => 'bar'
            ],
            'simple array path with keys' => [
                'record' => [
                    'test' => [
                        'foo' => 'me',
                        'bar' => 'you'
                    ]
                ],
                'configuration' => [
                    'arrayPath' => 'test/foo'
                ],
                'result' => 'me'
            ],
            'simple array path with indices' => [
                'record' => [
                    'test' => [
                        0 => 'me',
                        1 => 'you'
                    ]
                ],
                'configuration' => [
                    'arrayPath' => 'test/0'
                ],
                'result' => 'me'
            ],
            'array path with simple condition' => [
                'record' => [
                    'test' => [
                        'data' => [
                            'list' => [
                                0 => 'me',
                                1 => 'you'
                            ]
                        ]
                    ]
                ],
                'configuration' => [
                    'arrayPath' => 'test/data/list'
                ],
                'result' => [
                    0 => 'me',
                    1 => 'you'
                ]
            ],
            'array path with self condition' => [
                'record' => [
                    'test' => [
                        'data' => [
                            'status' => 'valid',
                            'list' => [
                                0 => 'me',
                                1 => 'you'
                            ]
                        ]
                    ]
                ],
                'configuration' => [
                    'arrayPath' => 'test/data{status === \'valid\'}/list'
                ],
                'result' => [
                    0 => 'me',
                    1 => 'you'
                ]
            ],
            'array path with condition on simple type' => [
                'record' => [
                    'test' => [
                        'data' => 'me'
                    ]
                ],
                'configuration' => [
                    'arrayPath' => 'test/data{value === \'me\'}'
                ],
                'result' => 'me',
            ],
            'array path with children condition' => [
                'record' => [
                    'test' => [
                        'data' => [
                            [
                                'status' => 'valid',
                                'list' => [
                                    0 => 'me',
                                    1 => 'you'
                                ]
                            ],
                            [
                                'status' => 'invalid',
                                'list' => []
                            ],
                            [
                                'status' => 'valid',
                                'list' => [
                                    3 => 'them'
                                ]
                            ],
                        ]
                    ]
                ],
                'configuration' => [
                    'arrayPath' => 'test/data/*{status === \'valid\'}/list'
                ],
                'result' => [
                    0 => 'me',
                    1 => 'you',
                    2 => 'them'
                ]
            ],
            'substructure' => [
                'record' => [
                    'test' => [
                        'foo' => 'me',
                        'bar' => 'you'
                    ]
                ],
                'configuration' => [
                    'field' => 'test'
                ],
                'result' => [
                    'foo' => 'me',
                    'bar' => 'you'
                ]
            ]
        ];
    }

    /**
     * @test
     * @dataProvider getValueSuccessProvider
     * @param array $record
     * @param array $configuration
     * @param mixed $result
     */
    public function getValueReturnsValueIfFound(array $record, array $configuration, $result): void
    {
        $value = $this->subject->getValue($record, $configuration);
        self::assertSame(
            $result,
            $value
        );
    }

    public function getValueFailWithInvalidArgumentExceptionProvider(): array
    {
        return [
            'direct simple value' => [
                'record' => [
                    'foo' => 'bar'
                ],
                'configuration' => [
                    'field' => 'baz'
                ]
            ],
            'wrong array path value' => [
                'record' => [
                    'test' => [
                        'test' => [
                            'foo' => 'me',
                            'bar' => 'you'
                        ]
                    ]
                ],
                'configuration' => [
                    'arrayPath' => false
                ]
            ],
            'non-matching array path value (array type)' => [
                'record' => [
                    'test' => [
                        'test' => [
                            'foo' => 'me',
                            'bar' => 'you'
                        ]
                    ]
                ],
                'configuration' => [
                    'arrayPath' => 'foo/baz'
                ]
            ],
            'non-matching array path value (simple type)' => [
                'record' => [
                    'test' => [
                        'data' => 'me'
                    ]
                ],
                'configuration' => [
                    'arrayPath' => 'test/data{value === \'you\'}'
                ]
            ],
            'array path value with invalid condition' => [
                'record' => [
                    'test' => [
                        'test' => [
                            'foo' => 'me',
                            'bar' => 'you'
                        ]
                    ]
                ],
                'configuration' => [
                    'arrayPath' => 'foo/baz{foo === \'bar\'}'
                ]
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getValueFailWithInvalidArgumentExceptionProvider
     * @param array $record
     * @param array $configuration
     */
    public function getValueThrowsInvalidArgumentExceptionIfValueNotFound($record, $configuration): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $value = $this->subject->getValue($record, $configuration);
    }

    public function getSubstructureProvider(): array
    {
        return [
            [
                'structure' => [
                    [
                        'foo' => 'me',
                        'bar' => [
                            'who' => 'you'
                        ],
                        'baz' => 'them'
                    ],
                    [
                        'foo' => 'me2',
                        'bar' => [
                            'who' => 'you2'
                        ],
                        'baz' => 'them2'
                    ]
                ],
                'configuration' => [
                    'first' => [
                        'field' => 'foo'
                    ],
                    'second' => [
                        'arrayPath' => 'bar/who'
                    ],
                    'third' => [
                        'field' => 'unknown'
                    ]
                ],
                'result' => [
                    [
                        'first' => 'me',
                        'second' => 'you'
                    ],
                    [
                        'first' => 'me2',
                        'second' => 'you2'
                    ]
                ]
            ]
        ];
    }

    /**
     * @test
     * @dataProvider getSubstructureProvider
     * @param array $structure
     * @param array $configuration
     * @param array $result
     */
    public function getSubstructureValuesReturnsExpectedRows(array $structure, array $configuration, array $result): void
    {
        self::assertSame(
            $result,
            $this->subject->getSubstructureValues($structure, $configuration)
        );
    }

    public function rawDataProvider(): array
    {
        return [
            'empty raw data' => [
                'generalConfiguration' => [],
                'rawData' => [],
                'expectedStructure' => []
            ],
            'raw data with non matching fields' => [
                'generalConfiguration' => [],
                'rawData' => [
                    [
                        'crazy_field' => 2
                    ],
                    [
                        'crazy_field' => 1
                    ]
                ],
                'expectedStructure' => []
            ],
            'raw data with no additional field' => [
                'generalConfiguration' => [],
                'rawData' => [
                    [
                        'normal_field' => 2
                    ],
                    [
                        'normal_field' => 1
                    ],
                    [
                        'crazy_field' => 42
                    ]
                ],
                'expectedStructure' => [
                    [
                        'name' => 2
                    ],
                    [
                        'name' => 1
                    ]
                ]
            ],
            'raw data with column array path' => [
                'generalConfiguration' => [],
                'rawData' => [
                    [
                        'brand' => [
                            'name' => 'foo'
                        ]
                    ],
                    [
                        'brand' => [
                            'name' => 'bar'
                        ]
                    ],
                    [
                        'crazy_field' => 42
                    ]
                ],
                'expectedStructure' => [
                    [
                        'brand' => 'foo'
                    ],
                    [
                        'brand' => 'bar'
                    ]
                ]
            ],
            'raw data with additional field' => [
                'generalConfiguration' => [],
                'rawData' => [
                    [
                        'normal_field' => 2,
                        'special_field' => 'foo'
                    ],
                    [
                        'normal_field' => 1,
                        'special_field' => 'bar'
                    ]
                ],
                'expectedStructure' => [
                    [
                        'name' => 2,
                        'special' => 'foo'
                    ],
                    [
                        'name' => 1,
                        'special' => 'bar'
                    ]
                ]
            ],
            'raw data with general array path' => [
                'generalConfiguration' => [
                    'arrayPath' => 'data/items'
                ],
                'rawData' => [
                    'data' => [
                        'items' => [
                            [
                                'normal_field' => 2
                            ],
                            [
                                'normal_field' => 1
                            ],
                            [
                                'crazy_field' => 42
                            ]
                        ]
                    ]
                ],
                'expectedStructure' => [
                    [
                        'name' => 2
                    ],
                    [
                        'name' => 1
                    ]
                ]
            ],
            'raw data with general invalid array path (empty)' => [
                'generalConfiguration' => [
                    'arrayPath' => ''
                ],
                'rawData' => [
                    'data' => [
                        'items' => [
                            [
                                'normal_field' => 2
                            ],
                            [
                                'normal_field' => 1
                            ],
                            [
                                'crazy_field' => 42
                            ]
                        ]
                    ]
                ],
                'expectedStructure' => []
            ],
            'raw data with general non-matching array path' => [
                'generalConfiguration' => [
                    'arrayPath' => 'foo/bar'
                ],
                'rawData' => [
                    'data' => [
                        'items' => [
                            [
                                'normal_field' => 2
                            ],
                            [
                                'normal_field' => 1
                            ],
                            [
                                'crazy_field' => 42
                            ]
                        ]
                    ]
                ],
                'expectedStructure' => []
            ]
        ];
    }

    /**
     * @test
     * @param array $generalConfiguration
     * @param array $rawData
     * @param array $expectedStructure
     * @dataProvider rawDataProvider
     */
    public function handleDataReturnsStructureData(array $generalConfiguration, array $rawData, array $expectedStructure): void
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration->method('getColumnConfiguration')
            ->willReturn(
                [
                    'name' => [
                        'field' => 'normal_field'
                    ],
                    'brand' => [
                        'arrayPath' => 'brand|name',
                        'arrayPathSeparator' => '|'
                    ],
                    'special' => [
                        'field' => 'special_field',
                        Configuration::DO_NOT_SAVE_KEY => true
                    ]
                ]
            );
        $configuration->method('getGeneralConfiguration')
            ->willReturn($generalConfiguration);
        $importer = $this->createMock(Importer::class);
        $importer->method('getExternalConfiguration')
            ->willReturn($configuration);

        $structuredData = $this->subject->handleData($rawData, $importer);
        self::assertSame(
            $expectedStructure,
            $structuredData
        );
    }
}