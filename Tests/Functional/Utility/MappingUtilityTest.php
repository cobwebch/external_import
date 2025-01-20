<?php

declare(strict_types=1);

namespace Cobweb\ExternalImport\Tests\Functional\Utility;

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

use Cobweb\ExternalImport\Utility\MappingUtility;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test class for the MappingUtility.
 *
 * NOTE: the MappingUtility is also covered by unit tests.
 */
class MappingUtilityTest extends FunctionalTestCase
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

    protected MappingUtility $subject;

    public function setUp(): void
    {
        parent::setUp();
        $this->subject = new MappingUtility();
    }

    public static function mappingInformationProvider(): array
    {
        return [
            'Value map takes precedence' => [
                'mappingInformation' => [
                    'valueMap' => [
                        'foo' => 1,
                        'bar' => 2,
                    ],
                    'table' => 'sys_category',
                    'referenceField' => 'external_key',
                ],
                'results' => [
                    'foo' => 1,
                    'bar' => 2,
                ],
            ],
            'All records (no valueField property)' => [
                'mappingInformation' => [
                    'table' => 'sys_category',
                    'referenceField' => 'external_key',
                ],
                'results' => [
                    'CAT1' => 1,
                    'CAT2' => 2,
                    '0' => 4,
                ],
            ],
            'All records (with valueField property)' => [
                'mappingInformation' => [
                    'table' => 'sys_category',
                    'referenceField' => 'external_key',
                    'valueField' => 'uid',
                ],
                'results' => [
                    'CAT1' => 1,
                    'CAT2' => 2,
                    '0' => 4,
                ],
            ],
            'All records (with non-uid valueField property)' => [
                'mappingInformation' => [
                    'table' => 'sys_category',
                    'referenceField' => 'external_key',
                    'valueField' => 'title',
                ],
                'results' => [
                    'CAT1' => 'Category 1',
                    'CAT2' => 'Category 2',
                    '0' => 'Category 4',
                ],
            ],
            'Filtered records' => [
                'mappingInformation' => [
                    'table' => 'sys_category',
                    'referenceField' => 'external_key',
                    'whereClause' => 'pid = 1',
                ],
                'results' => [
                    'CAT1' => 1,
                ],
            ],
        ];
    }

    #[Test] #[DataProvider('mappingInformationProvider')]
    public function getMappingReturnsRecordsToMap(array $mappingInformation, array $results): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Mappings.csv');
        $mappings = $this->subject->getMapping($mappingInformation);
        self::assertSame(
            $results,
            $mappings
        );
    }

    public static function dataToMapProvider(): array
    {
        return [
            'Default value gets applied' => [
                'records' => [
                    0 => [
                        'title' => 'Page with matching category',
                        'categories' => 'CAT2',
                    ],
                    1 => [
                        'title' => 'Page with non-matching category',
                        'categories' => 'CATX',
                    ],
                    2 => [
                        'title' => 'Page with missing category',
                    ],
                ],
                'table' => 'pages',
                'columnName' => 'categories',
                'mappingInformation' => [
                    'default' => 1,
                    'table' => 'sys_category',
                    'referenceField' => 'external_key',
                ],
                'result' => [
                    0 => [
                        'title' => 'Page with matching category',
                        'categories' => '2',
                    ],
                    1 => [
                        'title' => 'Page with non-matching category',
                        'categories' => 1,
                    ],
                    2 => [
                        'title' => 'Page with missing category',
                        'categories' => 1,
                    ],
                ],
            ],
            'Field gets unset without default value' => [
                'records' => [
                    0 => [
                        'title' => 'Page with matching category',
                        'categories' => 'CAT2',
                    ],
                    1 => [
                        'title' => 'Page with non-matching category',
                        'categories' => 'CATX',
                    ],
                    2 => [
                        'title' => 'Page with missing category',
                    ],
                ],
                'table' => 'pages',
                'columnName' => 'categories',
                'mappingInformation' => [
                    'table' => 'sys_category',
                    'referenceField' => 'external_key',
                ],
                'result' => [
                    0 => [
                        'title' => 'Page with matching category',
                        'categories' => '2',
                    ],
                    1 => [
                        'title' => 'Page with non-matching category',
                    ],
                    2 => [
                        'title' => 'Page with missing category',
                    ],
                ],
            ],
            'Multiple values separator' => [
                'records' => [
                    0 => [
                        'title' => 'Page with two matching categories',
                        'categories' => 'CAT1,CAT2',
                    ],
                    1 => [
                        'title' => 'Page with one matching and one non-matching category',
                        'categories' => 'CAT1,CATX',
                    ],
                ],
                'table' => 'pages',
                'columnName' => 'categories',
                'mappingInformation' => [
                    'table' => 'sys_category',
                    'referenceField' => 'external_key',
                    'multipleValuesSeparator' => ',',
                ],
                'result' => [
                    0 => [
                        'title' => 'Page with two matching categories',
                        'categories' => '1,2',
                    ],
                    1 => [
                        'title' => 'Page with one matching and one non-matching category',
                        'categories' => '1',
                    ],
                ],
            ],
        ];
    }

    #[Test] #[DataProvider('dataToMapProvider')]
    public function mapDataMapsDataAndAppliesDefaultValueIfDefined(
        array $records,
        string $table,
        string $columnName,
        array $mappingInformation,
        array $result
    ): void {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Mappings.csv');
        $mappedRecords = $this->subject->mapData($records, $table, $columnName, $mappingInformation);
        self::assertSame(
            $result,
            $mappedRecords
        );
    }
}
