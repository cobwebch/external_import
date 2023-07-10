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

use Cobweb\ExternalImport\Utility\CsvUtility;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CsvUtilityTest extends UnitTestCase
{
    protected CsvUtility $subject;

    public function setUp(): void
    {
        $this->subject = GeneralUtility::makeInstance(CsvUtility::class);
    }

    public function arrayDataProvider(): array
    {
        return [
            'simple case - all values for all indices' => [
                'input' => [
                    [
                        'a' => 1,
                        'b' => 2,
                        'c' => 3,
                    ],
                    [
                        'a' => 11,
                        'b' => 22,
                        'c' => 13,
                    ],
                ],
                'output' => [
                    [
                        'a' => 1,
                        'b' => 2,
                        'c' => 3,
                    ],
                    [
                        'a' => 11,
                        'b' => 22,
                        'c' => 13,
                    ],
                ],
            ],
            'complicated case - missing values in first record, in the middle' => [
                'input' => [
                    [
                        'a' => 1,
                        'c' => 3,
                    ],
                    [
                        'a' => 11,
                        'b' => 22,
                        'c' => 13,
                    ],
                ],
                'output' => [
                    [
                        'a' => 1,
                        'b' => '',
                        'c' => 3,
                    ],
                    [
                        'a' => 11,
                        'b' => 22,
                        'c' => 13,
                    ],
                ],
            ],
            'complicated case - missing values in second record, at the end' => [
                'input' => [
                    [
                        'a' => 1,
                        'b' => 2,
                        'c' => 3,
                    ],
                    [
                        'a' => 11,
                        'b' => 22,
                    ],
                ],
                'output' => [
                    [
                        'a' => 1,
                        'b' => 2,
                        'c' => 3,
                    ],
                    [
                        'a' => 11,
                        'b' => 22,
                        'c' => '',
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider arrayDataProvider
     */
    public function ensureCompleteStructureCompletesStructureAsNeeded(array $input, array $output): void
    {
        $result = $this->subject->ensureCompleteStructure($input);
        self::assertSame($output, $result);
    }
}