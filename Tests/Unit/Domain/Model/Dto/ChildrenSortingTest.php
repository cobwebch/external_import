<?php

declare(strict_types=1);

namespace Cobweb\ExternalImport\Tests\Unit\Domain\Model\Dto;

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

use Cobweb\ExternalImport\Domain\Model\Dto\ChildrenSorting;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case for the ChildrenSorting model object.
 */
class ChildrenSortingTest extends UnitTestCase
{
    protected ChildrenSorting $subject;

    public function setUp(): void
    {
        parent::setUp();
        $this->subject = GeneralUtility::makeInstance(ChildrenSorting::class);
    }

    #[Test]
    public function hasSortingInformationInitiallyReturnsFalse(): void
    {
        self::assertFalse(
            $this->subject->hasSortingInformation()
        );
    }

    public static function hasSortingInformationProvider(): array
    {
        return [
            'one table - empty' => [
                'structure' => [
                    'foo' => [],
                ],
                'expectedResult' => false,
            ],
            'one table - one item' => [
                'structure' => [
                    'foo' => [
                        [
                            3,
                            'sorting',
                            1,
                        ],
                    ],
                ],
                'expectedResult' => true,
            ],
            'two tables - one empty, one not' => [
                'structure' => [
                    'foo' => [
                        [
                            3,
                            'sorting',
                            1,
                        ],
                    ],
                    'bar' => [],
                ],
                'expectedResult' => true,
            ],
        ];
    }

    #[Test] #[DataProvider('hasSortingInformationProvider')]
    public function hasSortingInformationInitiallyReturnsBooleanValue(array $structure, bool $expectedResult): void
    {
        foreach ($structure as $table => $items) {
            foreach ($items as $item) {
                $this->subject->addSortingInformation($table, $item[0], $item[1], $item[2]);
            }
        }
        self::assertEquals(
            $this->subject->hasSortingInformation(),
            $expectedResult
        );
    }

    #[Test]
    public function getSortingInformationInitiallyReturnsEmptyArray(): void
    {
        self::assertSame(
            [],
            $this->subject->getSortingInformation()
        );
    }

    #[Test]
    public function getSortingInformationReturnsEmptyArrayAfterReset(): void
    {
        $this->subject->addSortingInformation('foo', 2, 'sorting', 1);
        $this->subject->resetSortingInformation();
        self::assertSame(
            [],
            $this->subject->getSortingInformation()
        );
    }

    #[Test]
    public function addSortingInformationExpandsArray(): void
    {
        $this->subject->addSortingInformation('foo', 2, 'sorting', 1);
        self::assertSame(
            [
                'foo' => [
                    2 => [
                        'sorting' => 1,
                    ],
                ],
            ],
            $this->subject->getSortingInformation()
        );
        // NOTE: sorting value is cast to int inside addSortingInformation()
        $this->subject->addSortingInformation('foo', 3, 'sorting', '2');
        self::assertSame(
            [
                'foo' => [
                    2 => [
                        'sorting' => 1,
                    ],
                    3 => [
                        'sorting' => 2,
                    ],
                ],
            ],
            $this->subject->getSortingInformation()
        );
        $this->subject->addSortingInformation('bar', 1, 'sorting', 1);
        self::assertSame(
            [
                'foo' => [
                    2 => [
                        'sorting' => 1,
                    ],
                    3 => [
                        'sorting' => 2,
                    ],
                ],
                'bar' => [
                    1 => [
                        'sorting' => 1,
                    ],
                ],
            ],
            $this->subject->getSortingInformation()
        );
    }

    #[Test]
    public function replaceIdReplacesChildId(): void
    {
        $this->subject->addSortingInformation('foo', 'tempKey', 'sorting', 1);
        $this->subject->addSortingInformation('foo', 3, 'sorting', 2);
        $this->subject->replaceId('foo', 'tempKey', 12);
        self::assertSame(
            [
                'foo' => [
                    3 => [
                        'sorting' => 2,
                    ],
                    12 => [
                        'sorting' => 1,
                    ],
                ],
            ],
            $this->subject->getSortingInformation()
        );
    }

    #[Test]
    public function replaceAllNewIdsReplacesNewMarkers(): void
    {
        // "NEW" id, will be replaced
        $this->subject->addSortingInformation('foo', 'NEW123.45', 'sorting', 1);
        // String not starting with "NEW", not replaced
        $this->subject->addSortingInformation('foo', 'tempKey', 'sorting', 4);
        // Number, not replaced
        $this->subject->addSortingInformation('foo', 3, 'sorting', 2);
        $this->subject->replaceAllNewIds(
            [
                'NEW123.45' => 17,
            ]
        );
        self::assertSame(
            [
                'foo' => [
                    'tempKey' => [
                        'sorting' => 4,
                    ],
                    3 => [
                        'sorting' => 2,
                    ],
                    17 => [
                        'sorting' => 1,
                    ],
                ],
            ],
            $this->subject->getSortingInformation()
        );
    }
}
