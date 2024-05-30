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
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test class for the MappingUtility.
 *
 * NOTE: the MappingUtility is also covered by unit tests.
 */
class MappingUtilityTest extends FunctionalTestCase
{
    protected $testExtensionsToLoad = [
        'typo3conf/ext/svconnector',
        'typo3conf/ext/external_import',
        'typo3conf/ext/externalimport_test',
    ];

    /**
     * @var MappingUtility
     */
    protected $subject;

    public function setUp(): void
    {
        parent::setUp();
        $this->subject = GeneralUtility::makeInstance(MappingUtility::class);
    }

    public function mappingConfigurationProvider(): array
    {
        return [
            'Value map takes precedence' => [
                [
                    'valueMap' => [
                        'foo' => 1,
                        'bar' => 2,
                    ],
                    'table' => 'sys_category',
                    'referenceField' => 'external_key',
                ],
                [
                    'foo' => 1,
                    'bar' => 2,
                ],
            ],
            'All records (no valueField property)' => [
                [
                    'table' => 'sys_category',
                    'referenceField' => 'external_key',
                ],
                [
                    'CAT1' => 1,
                    'CAT2' => 2,
                    '0' => 4,
                ],
            ],
            'All records (with valueField property)' => [
                [
                    'table' => 'sys_category',
                    'referenceField' => 'external_key',
                    'valueField' => 'uid',
                ],
                [
                    'CAT1' => 1,
                    'CAT2' => 2,
                    '0' => 4,
                ],
            ],
            'All records (with non-uid valueField property)' => [
                [
                    'table' => 'sys_category',
                    'referenceField' => 'external_key',
                    'valueField' => 'title',
                ],
                [
                    'CAT1' => 'Category 1',
                    'CAT2' => 'Category 2',
                    '0' => 'Category 4',
                ],
            ],
            'Filtered records' => [
                [
                    'table' => 'sys_category',
                    'referenceField' => 'external_key',
                    'whereClause' => 'pid = 1',
                ],
                [
                    'CAT1' => 1,
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider mappingConfigurationProvider
     * @param array $mappingConfiguration
     * @param array $results
     * @throws \Nimut\TestingFramework\Exception\Exception
     */
    public function getMappingReturnsRecordsToMap(array $mappingConfiguration, array $results): void
    {
        $this->importDataSet(__DIR__ . '/../Fixtures/Mappings.xml');
        $mappings = $this->subject->getMapping($mappingConfiguration);
        self::assertSame(
            $results,
            $mappings
        );
    }

    public function dataToMapProvider(): array
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
                'field' => 'categories',
                'mappingConfiguration' => [
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
                'field' => 'categories',
                'mappingConfiguration' => [
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
                'field' => 'categories',
                'mappingConfiguration' => [
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

    /**
     * @test
     * @dataProvider dataToMapProvider
     * @param array $records Records to handle
     * @param string $table Name of the table the records belong to
     * @param string $columnName Name of the column whose values must be mapped
     * @param array $mappingInformation Mapping configuration
     * @param array $result Mapped records (expected result)
     * @throws \Nimut\TestingFramework\Exception\Exception
     */
    public function mapDataMapsDataAndAppliesDefaultValueIfDefined(
        array $records,
        string $table,
        string $columnName,
        array $mappingInformation,
        array $result
    ): void {
        $this->importDataSet(__DIR__ . '/../Fixtures/Mappings.xml');
        $mappedRecords = $this->subject->mapData($records, $table, $columnName, $mappingInformation);
        self::assertSame(
            $result,
            $mappedRecords
        );
    }
}
