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
use Cobweb\ExternalImport\Utility\MappingUtility;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test suite for the TransformDataStep class.
 */
class TransformDataStepTest extends FunctionalTestCase
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

    protected TransformDataStep $subject;

    public function setUp(): void
    {
        parent::setUp();
        $this->subject = new TransformDataStep(new MappingUtility());
        $importer = $this->createMock(Importer::class);
//        $configuration = $this->getAccessibleMock(Configuration::class);
        $configuration = $this->createMock(Configuration::class);
        $configuration->method('getTable')->willReturn('foo');
//        $configuration->setTable('foo');
        $importer->method('getExternalConfiguration')->willReturn($configuration);
        $this->subject->setImporter(
            $importer
        );
        // Global language object is needed for some localized log messages
        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageServiceFactory::class)->create('en');
    }

    public static function trimDataProvider(): array
    {
        return [
            'Trim data (true)' => [
                'name' => 'foo',
                'configuration' => true,
                'records' => [
                    0 => [
                        'foo' => ' White space all around ',
                        'bar' => ' Not trimmed ',
                    ],
                    1 => [
                        'foo' => ' White space left',
                        'bar' => ' Not trimmed ',
                    ],
                    2 => [
                        'foo' => 'White space right ',
                        'bar' => ' Not trimmed ',
                    ],
                    3 => [
                        'foo' => 'No white space',
                        'bar' => ' Not trimmed ',
                    ],
                ],
                'expected' => [
                    0 => [
                        'foo' => 'White space all around',
                        'bar' => ' Not trimmed ',
                    ],
                    1 => [
                        'foo' => 'White space left',
                        'bar' => ' Not trimmed ',
                    ],
                    2 => [
                        'foo' => 'White space right',
                        'bar' => ' Not trimmed ',
                    ],
                    3 => [
                        'foo' => 'No white space',
                        'bar' => ' Not trimmed ',
                    ],
                ],
            ],
            'Do not trim data (false)' => [
                'name' => 'foo',
                'configuration' => false,
                'records' => [
                    0 => [
                        'foo' => ' White space all around ',
                        'bar' => ' Not trimmed ',
                    ],
                    1 => [
                        'foo' => 'No white space',
                        'bar' => ' Not trimmed ',
                    ],
                ],
                'expected' => [
                    0 => [
                        'foo' => ' White space all around ',
                        'bar' => ' Not trimmed ',
                    ],
                    1 => [
                        'foo' => 'No white space',
                        'bar' => ' Not trimmed ',
                    ],
                ],
            ],
            'Trim not string data' => [
                'name' => 'foo',
                'configuration' => true,
                'records' => [
                    0 => [
                        'foo' => ['bar' => 'baz'],
                    ],
                    1 => [
                        'foo' => 2,
                    ],
                    2 => [
                        'foo' => true,
                    ],
                    3 => [
                        'bar' => 'baz',
                    ],
                ],
                'expected' => [
                    0 => [
                        'foo' => ['bar' => 'baz'],
                    ],
                    1 => [
                        'foo' => 2,
                    ],
                    2 => [
                        'foo' => true,
                    ],
                    3 => [
                        'bar' => 'baz',
                    ],
                ],
            ],
        ];
    }

    #[Test] #[DataProvider('trimDataProvider')]
    public function applyTrimTrimsDataIfTrue(string $name, bool $configuration, array $records, array $expected): void
    {
        $result = $this->subject->applyTrim(
            $name,
            $configuration,
            $records
        );
        self::assertSame($expected, $result);
    }

    public static function mappingDataProvider(): array
    {
        return [
            'Map to sys_category with default value' => [
                'name' => 'foo',
                'configuration' => [
                    'table' => 'sys_category',
                    'referenceField' => 'external_key',
                    'default' => 19,
                ],
                'records' => [
                    0 => [
                        'foo' => 'USEFUL',
                        'bar' => 42,
                    ],
                    1 => [
                        'foo' => 'USELESS',
                        'bar' => 17,
                    ],
                    2 => [
                        'foo' => 'UNKNOWN',
                        'bar' => 24,
                    ],
                ],
                'expected' => [
                    0 => [
                        'foo' => '1',
                        'bar' => 42,
                    ],
                    1 => [
                        'foo' => '2',
                        'bar' => 17,
                    ],
                    2 => [
                        'foo' => 19,
                        'bar' => 24,
                    ],
                ],
            ],
            'Map to sys_category without default value' => [
                'name' => 'foo',
                'configuration' => [
                    'table' => 'sys_category',
                    'referenceField' => 'external_key',
                ],
                'records' => [
                    0 => [
                        'foo' => 'USEFUL',
                        'bar' => 42,
                    ],
                    1 => [
                        'foo' => 'USELESS',
                        'bar' => 17,
                    ],
                    2 => [
                        'foo' => 'UNKNOWN',
                        'bar' => 24,
                    ],
                ],
                'expected' => [
                    0 => [
                        'foo' => '1',
                        'bar' => 42,
                    ],
                    1 => [
                        'foo' => '2',
                        'bar' => 17,
                    ],
                    2 => [
                        'bar' => 24,
                    ],
                ],
            ],
        ];
    }

    #[Test] #[DataProvider('mappingDataProvider')]
    public function applyMappingMapsData(string $name, array $configuration, array $records, array $expected): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Categories.csv');
        $result = $this->subject->applyMapping(
            $name,
            $configuration,
            $records
        );
        self::assertSame($expected, $result);
    }

    #[Test]
    public function applyValueAppliesValue(): void
    {
        $result = $this->subject->applyValue(
            'foo',
            42,
            [
                0 => [
                    'foo' => 17,
                    'bar' => 4,
                ],
                1 => [
                    'foo' => 23,
                    'bar' => 8,
                ],
            ]
        );
        self::assertSame(
            [
                0 => [
                    'foo' => 42,
                    'bar' => 4,
                ],
                1 => [
                    'foo' => 42,
                    'bar' => 8,
                ],
            ],
            $result
        );
    }

    #[Test]
    public function applyRteEnabledFlagAppliesFlag(): void
    {
        $result = $this->subject->applyRteEnabledFlag(
            'foo',
            true,
            [
                0 => [
                    'foo' => 17,
                    'bar' => 4,
                ],
                1 => [
                    'foo' => 23,
                    'bar' => 8,
                ],
            ]
        );
        self::assertSame(
            [
                0 => [
                    'foo' => 17,
                    'bar' => 4,
                    '_TRANSFORM_foo' => 'RTE',
                ],
                1 => [
                    'foo' => 23,
                    'bar' => 8,
                    '_TRANSFORM_foo' => 'RTE',
                ],
            ],
            $result
        );
    }

    public static function userFunctionDataProvider(): array
    {
        return [
            'Valid configuration - data transformed' => [
                'name' => 'foo',
                'configuration' => [
                    'class' => DateTimeTransformation::class,
                    'method' => 'parseDate',
                    'parameters' => [
                        'function' => 'date',
                        'format' => 'U',
                    ],
                ],
                'records' => [
                    0 => [
                        'foo' => '2017-10-11T18:29:01+02:00',
                        'bar' => 4,
                    ],
                ],
                'expected' => [
                    0 => [
                        'foo' => '1507739341',
                        'bar' => 4,
                    ],
                ],
            ],
        ];
    }

    /**
     * @throws \Cobweb\ExternalImport\Exception\CriticalFailureException
     */
    #[Test] #[DataProvider('userFunctionDataProvider')]
    public function applyUserFunctionTransformsDataIfValid(string $name, array $configuration, array $records, array $expected): void
    {
        $result = $this->subject->applyUserFunction(
            $name,
            $configuration,
            $records
        );
        self::assertSame($expected, $result);
    }

    public static function isEmptyDataProvider(): array
    {
        $emptyArray = [
            0 => [
                'foo' => 'Hello world',
                'bar' => 'Foo is not empty',
            ],
            1 => [
                'bar' => 'Foo is empty',
            ],
            2 => [
                'foo' => '',
                'bar' => 'Foo is empty',
            ],
            3 => [
                'foo' => 0,
                'bar' => 'Foo is empty',
            ],
            4 => [
                'foo' => false,
                'bar' => 'Foo is empty',
            ],
            5 => [
                'foo' => null,
                'bar' => 'Foo is empty',
            ],
        ];
        return [
            'No empty records - no expression - nothing happens' => [
                'name' => 'foo',
                'configuration' => [
                    'invalidate' => true,
                ],
                'records' => [
                    0 => [
                        'foo' => 'Hello world',
                        'bar' => 'Foo is not empty',
                    ],
                    1 => [
                        'foo' => 'This is me!',
                        'bar' => 'Foo is not empty',
                    ],
                ],
                'expected' => [
                    0 => [
                        'foo' => 'Hello world',
                        'bar' => 'Foo is not empty',
                    ],
                    1 => [
                        'foo' => 'This is me!',
                        'bar' => 'Foo is not empty',
                    ],
                ],
            ],
            'Empty records - no expression - invalidate' => [
                'name' => 'foo',
                'configuration' => [
                    'invalidate' => true,
                ],
                'records' => $emptyArray,
                'expected' => [
                    0 => [
                        'foo' => 'Hello world',
                        'bar' => 'Foo is not empty',
                    ],
                ],
            ],
            'Empty records - no expression - default value' => [
                'name' => 'foo',
                'configuration' => [
                    'default' => 'Foo is foo',
                ],
                'records' => $emptyArray,
                'expected' => [
                    0 => [
                        'foo' => 'Hello world',
                        'bar' => 'Foo is not empty',
                    ],
                    1 => [
                        'bar' => 'Foo is empty',
                        'foo' => 'Foo is foo',
                    ],
                    2 => [
                        'foo' => 'Foo is foo',
                        'bar' => 'Foo is empty',
                    ],
                    3 => [
                        'foo' => 'Foo is foo',
                        'bar' => 'Foo is empty',
                    ],
                    4 => [
                        'foo' => 'Foo is foo',
                        'bar' => 'Foo is empty',
                    ],
                    5 => [
                        'foo' => 'Foo is foo',
                        'bar' => 'Foo is empty',
                    ],
                ],
            ],
            'Empty records - expression (null) - invalidate' => [
                'name' => 'foo',
                'configuration' => [
                    'expression' => 'foo === null',
                    'invalidate' => true,
                ],
                'records' => [
                    0 => [
                        'foo' => 'Hello world',
                        'bar' => 'Foo is not empty',
                    ],
                    1 => [
                        'bar' => 'Foo is empty',
                    ],
                    2 => [
                        'foo' => 'Me again :-)',
                        'bar' => 'Foo is not empty',
                    ],
                ],
                'expected' => [
                    0 => [
                        'foo' => 'Hello world',
                        'bar' => 'Foo is not empty',
                    ],
                    1 => [
                        'foo' => 'Me again :-)',
                        'bar' => 'Foo is not empty',
                    ],
                ],
            ],
            'Empty records - expression (empty string) - invalidate' => [
                'name' => 'foo',
                'configuration' => [
                    'expression' => 'foo === ""',
                    'invalidate' => true,
                ],
                'records' => [
                    0 => [
                        'foo' => 'Hello world',
                        'bar' => 'Foo is not empty',
                    ],
                    1 => [
                        'foo' => '',
                        'bar' => 'Foo is empty',
                    ],
                    2 => [
                        'foo' => 'Me again :-)',
                        'bar' => 'Foo is not empty',
                    ],
                ],
                'expected' => [
                    0 => [
                        'foo' => 'Hello world',
                        'bar' => 'Foo is not empty',
                    ],
                    1 => [
                        'foo' => 'Me again :-)',
                        'bar' => 'Foo is not empty',
                    ],
                ],
            ],
            'Expression not testing emptiness' => [
                'name' => 'foo',
                'configuration' => [
                    'expression' => 'foo + bar',
                    'invalidate' => true,
                ],
                'records' => [
                    // This first record will be removed, because 2 + 5 = 7, which is equivalent to true when casting to boolean
                    0 => [
                        'foo' => 2,
                        'bar' => 5,
                    ],
                    1 => [
                        'foo' => 1,
                        'bar' => -1,
                    ],
                ],
                'expected' => [
                    0 => [
                        'foo' => 1,
                        'bar' => -1,
                    ],
                ],
            ],
        ];
    }

    #[Test] #[DataProvider('isEmptyDataProvider')]
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
