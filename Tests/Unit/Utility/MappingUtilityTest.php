<?php

declare(strict_types=1);

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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case for the External Import mapping utility.
 */
class MappingUtilityTest extends UnitTestCase
{
    protected MappingUtility $mappingUtility;

    public function setUp(): void
    {
        parent::setUp();
        $this->mappingUtility = GeneralUtility::makeInstance(MappingUtility::class);
    }

    /**
     * Provide data for soft matching with strpos
     * (currently provides only one data set, but could provide more in the future)
     */
    public static function mappingTestWithStrposProvider(): array
    {
        return [
            'australia' => [
                'inputData' => 'Australia',
                'mappingTable' => [
                    'Commonwealth of Australia' => 'AU',
                    'Kingdom of Spain' => 'ES',
                ],
                'mappingConfiguration' => [
                    'matchMethod' => 'strpos',
                    'matchSymmetric' => false,
                ],
                'expectedResult' => 'AU',
            ],
        ];
    }

    #[Test] #[DataProvider('mappingTestWithStrposProvider')]
    public function matchWordsWithStrposNotSymmetric(string $inputData, array $mappingTable, array $mappingConfiguration, string $expectedResult): void
    {
        $actualResult = $this->mappingUtility->matchSingleField($inputData, $mappingConfiguration, $mappingTable);
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * Provide data for soft matching with strpos with symmetric matching
     * (currently provides only one data set, but could provide more in the future)
     */
    public static function mappingTestWithStrposSymmetricProvider(): array
    {
        return [
            'australia' => [
                'inputData' => 'Commonwealth of Australia',
                'mappingTable' => [
                    'Australia' => 'AU',
                    'Spain' => 'ES',
                ],
                'mappingConfiguration' => [
                    'matchMethod' => 'strpos',
                    'matchSymmetric' => true,
                ],
                'expectedResult' => 'AU',
            ],
        ];
    }

    #[Test] #[DataProvider('mappingTestWithStrposSymmetricProvider')]
    public function matchWordsWithStrposSymmetric(string $inputData, array $mappingTable, array $mappingConfiguration, string $expectedResult): void
    {
        $actualResult = $this->mappingUtility->matchSingleField($inputData, $mappingConfiguration, $mappingTable);
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * Provide data for soft matching with strpos that will not match
     */
    public static function mappingTestWithStrposWithBadMappingTableProvider(): array
    {
        return [
            // Case doesn't match in this data set
            'wrong case' => [
                'inputData' => 'australia',
                'mappingTable' => [
                    'Commonwealth of Australia' => 'AU',
                    'Kingdom of Spain' => 'ES',
                ],
                'mappingConfiguration' => [
                    'matchMethod' => 'strpos',
                    'matchSymmetric' => false,
                ],
            ],
            'no matching data' => [
                'inputData' => 'Swaziland',
                'mappingTable' => [
                    'Commonwealth of Australia' => 'AU',
                    'Kingdom of Spain' => 'ES',
                ],
                'mappingConfiguration' => [
                    'matchMethod' => 'strpos',
                    'matchSymmetric' => false,
                ],
            ],
        ];
    }

    #[Test] #[DataProvider('mappingTestWithStrposWithBadMappingTableProvider')]
    public function failMatchWordsWithStrposNotSymmetric(string $inputData, array $mappingTable, array $mappingConfiguration): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->mappingUtility->matchSingleField($inputData, $mappingConfiguration, $mappingTable);
    }

    /**
     * Provide data that will not match for stripos
     */
    public static function mappingTestWithStriposProvider(): array
    {
        return [
            'australia' => [
                'inputData' => 'australia',
                'mappingTable' => [
                    'Commonwealth of Australia' => 'AU',
                    'Kingdom of Spain' => 'ES',
                ],
                'mappingConfiguration' => [
                    'matchMethod' => 'stripos',
                    'matchSymmetric' => false,
                ],
                'expectedResult' => 'AU',
            ],
        ];
    }

    #[Test] #[DataProvider('mappingTestWithStriposProvider')]
    public function matchWordsWithStirposNotSymmetric(string $inputData, array $mappingTable, array $mappingConfiguration, string $expectedResult): void
    {
        $actualResult = $this->mappingUtility->matchSingleField($inputData, $mappingConfiguration, $mappingTable);
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * Provide data for soft matching with stripos that will not match
     * (currently provides only one data set, but could provide more in the future)
     */
    public static function mappingTestWithStriposWithBadMappingTableProvider(): array
    {
        return [
            // Case doesn't match in this data set
            'no matching data' => [
                'inputData' => 'Swaziland',
                'mappingTable' => [
                    'Commonwealth of Australia' => 'AU',
                    'Kingdom of Spain' => 'ES',
                ],
                'mappingConfiguration' => [
                    'matchMethod' => 'strpos',
                    'matchSymmetric' => false,
                ],
            ],
        ];
    }

    #[Test] #[DataProvider('mappingTestWithStriposWithBadMappingTableProvider')]
    public function failMatchWordsWithStriposNotSymmetric(string $inputData, array $mappingTable, array $mappingConfiguration): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->mappingUtility->matchSingleField($inputData, $mappingConfiguration, $mappingTable);
    }
}
