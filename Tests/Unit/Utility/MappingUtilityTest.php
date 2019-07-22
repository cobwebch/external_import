<?php

namespace Cobweb\ExternalImport\Tests\Unit\Utility;

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
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test case for the External Import mapping utility.
 *
 * @author Francois Suter <typo3@cobweb.ch>
 * @package TYPO3
 * @subpackage tx_externalimport
 */
class MappingUtilityTest extends UnitTestCase
{
    /**
     * Local instance for testing
     *
     * @var MappingUtility
     */
    protected $mappingUtility;

    public function setUp()
    {
        $this->mappingUtility = GeneralUtility::makeInstance(MappingUtility::class);
    }

    /**
     * Provide data for soft matching with strpos
     * (currently provides only one data set, but could provide more in the future)
     *
     * @return array
     */
    public function mappingTestWithStrposProvider(): array
    {
        return [
                'australia' => [
                        'inputData' => 'Australia',
                        'mappingTable' => [
                                'Commonwealth of Australia' => 'AU',
                                'Kingdom of Spain' => 'ES'
                        ],
                        'mappingConfiguration' => [
                                'matchMethod' => 'strpos',
                                'matchSymmetric' => false
                        ],
                        'result' => 'AU'
                ]
        ];
    }

    /**
     * Test soft-matching method with strpos
     *
     * @test
     * @dataProvider mappingTestWithStrposProvider
     * @param string $inputData
     * @param array $mappingTable
     * @param array $mappingConfiguration
     * @param string $expectedResult
     */
    public function matchWordsWithStrposNotSymmetric($inputData, $mappingTable, $mappingConfiguration, $expectedResult): void
    {
        $actualResult = $this->mappingUtility->matchSingleField($inputData, $mappingConfiguration, $mappingTable);
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * Provide data for soft matching with strpos
     * (currently provides only one data set, but could provide more in the future)
     *
     * @return array
     */
    public function mappingTestWithStrposSymmetricProvider(): array
    {
        return [
                'australia' => [
                        'inputData' => 'Commonwealth of Australia',
                        'mappingTable' => [
                                'Australia' => 'AU',
                                'Spain' => 'ES'
                        ],
                        'mappingConfiguration' => [
                                'matchMethod' => 'strpos',
                                'matchSymmetric' => true
                        ],
                        'result' => 'AU'
                ]
        ];
    }

    /**
     * Test soft-matching method with strpos and symmetric flag
     *
     * @test
     * @dataProvider mappingTestWithStrposSymmetricProvider
     * @param string $inputData
     * @param array $mappingTable
     * @param array $mappingConfiguration
     * @param string $expectedResult
     */
    public function matchWordsWithStrposSymmetric($inputData, $mappingTable, $mappingConfiguration, $expectedResult): void
    {
        $actualResult = $this->mappingUtility->matchSingleField($inputData, $mappingConfiguration, $mappingTable);
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * Provide data for soft matching with strpos
     *
     * @return array
     */
    public function mappingTestWithStrposWithBadMappingTableProvider(): array
    {
        return [
            // Case doesn't match in this data set
            'wrong case' => [
                    'inputData' => 'australia',
                    'mappingTable' => [
                            'Commonwealth of Australia' => 'AU',
                            'Kingdom of Spain' => 'ES'
                    ],
                    'mappingConfiguration' => [
                            'matchMethod' => 'strpos',
                            'matchSymmetric' => false
                    ]
            ],
            'no matching data' => [
                    'inputData' => 'Swaziland',
                    'mappingTable' => [
                            'Commonwealth of Australia' => 'AU',
                            'Kingdom of Spain' => 'ES'
                    ],
                    'mappingConfiguration' => [
                            'matchMethod' => 'strpos',
                            'matchSymmetric' => false
                    ]
            ]
        ];
    }

    /**
     * @test
     * @dataProvider mappingTestWithStrposWithBadMappingTableProvider
     * @expectedException \UnexpectedValueException
     * @param string $inputData
     * @param array $mappingTable
     * @param array $mappingConfiguration
     */
    public function failMatchWordsWithStrposNotSymmetric($inputData, $mappingTable, $mappingConfiguration): void
    {
        $this->mappingUtility->matchSingleField($inputData, $mappingConfiguration, $mappingTable);
    }

    /**
     * Provide data that will not match for strpos
     *
     * @return array
     */
    public function mappingTestWithStriposProvider(): array
    {
        $data = [
                'australia' => [
                        'inputData' => 'australia',
                        'mappingTable' => [
                                'Commonwealth of Australia' => 'AU',
                                'Kingdom of Spain' => 'ES'
                        ],
                        'mappingConfiguration' => [
                                'matchMethod' => 'stripos',
                                'matchSymmetric' => false
                        ],
                        'result' => 'AU'
                ]
        ];
        return $data;
    }

    /**
     * Test soft-matching method with stripos
     *
     * @test
     * @dataProvider mappingTestWithStriposProvider
     * @param string $inputData
     * @param array $mappingTable
     * @param array $mappingConfiguration
     * @param string $expectedResult
     */
    public function matchWordsWithStirposNotSymmetric($inputData, $mappingTable, $mappingConfiguration, $expectedResult): void
    {
        $actualResult = $this->mappingUtility->matchSingleField($inputData, $mappingConfiguration, $mappingTable);
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * Provide data for soft matching with strpos
     * (currently provides only one data set, but could provide more in the future)
     *
     * @return array
     */
    public function mappingTestWithStriposWithBadMappingTableProvider(): array
    {
        return [
            // Case doesn't match in this data set
            'no matching data' => [
                    'inputData' => 'Swaziland',
                    'mappingTable' => [
                            'Commonwealth of Australia' => 'AU',
                            'Kingdom of Spain' => 'ES'
                    ],
                    'mappingConfiguration' => [
                            'matchMethod' => 'strpos',
                            'matchSymmetric' => false
                    ]
            ]
        ];
    }

    /**
     * @test
     * @dataProvider mappingTestWithStriposWithBadMappingTableProvider
     * @expectedException \UnexpectedValueException
     * @param string $inputData
     * @param array $mappingTable
     * @param array $mappingConfiguration
     */
    public function failMatchWordsWithStriposNotSymmetric($inputData, $mappingTable, $mappingConfiguration): void
    {
        $this->mappingUtility->matchSingleField($inputData, $mappingConfiguration, $mappingTable);
    }
}
