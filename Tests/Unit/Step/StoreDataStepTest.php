<?php

namespace Cobweb\ExternalImport\Tests\Unit\Step;

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

use Cobweb\ExternalImport\Step\StoreDataStep;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Unit test suite for the Store Data step class.
 *
 * @package Cobweb\ExternalImport\Tests\Unit\Step
 */
class StoreDataStepTest extends UnitTestCase
{
    /**
     * @var StoreDataStep
     */
    protected $subject;

    public function setUp(): void
    {
        parent::setUp();
        $this->subject = GeneralUtility::makeInstance(
            StoreDataStep::class,
            $this->getAccessibleMock(
                \TYPO3\CMS\Core\EventDispatcher\EventDispatcher::class,
                null,
                [],
                '',
                false
            )
        );
    }

    /**
     * @test
     */
    public function getFieldsExcludedFromInsertsInitiallyReturnsEmptyArray(): void
    {
        self::assertSame(
                [],
                $this->subject->getFieldsExcludedFromInserts()
        );
    }

    /**
     * @test
     */
    public function getFieldsExcludedFromUpdatesInitiallyReturnsEmptyArray(): void
    {
        self::assertSame(
                [],
                $this->subject->getFieldsExcludedFromUpdates()
        );
    }

    public function sortPagesProvider(): array
    {
        return [
                'No new pages - no sorting needed' => [
                        [
                                34 => [
                                        'title' => 'Foo',
                                        'pid' => 23
                                ],
                                42 => [
                                        'title' => 'Bar',
                                        'pid' => 34
                                ],
                        ],
                        [
                                34 => [
                                        'title' => 'Foo',
                                        'pid' => 23
                                ],
                                42 => [
                                        'title' => 'Bar',
                                        'pid' => 34
                                ],
                        ],
                ],
                'New pages, not nested - no sorting needed' => [
                        [
                                'NEW1' => [
                                        'title' => 'Parent 1',
                                        'pid' => 4
                                ],
                                'NEW2' => [
                                        'title' => 'Parent 2',
                                        'pid' => 4
                                ],
                                'NEW3' => [
                                        'title' => 'Parent 3',
                                        'pid' => 10
                                ],
                        ],
                        [
                                'NEW1' => [
                                        'title' => 'Parent 1',
                                        'pid' => 4
                                ],
                                'NEW2' => [
                                        'title' => 'Parent 2',
                                        'pid' => 4
                                ],
                                'NEW3' => [
                                        'title' => 'Parent 3',
                                        'pid' => 10
                                ],
                        ]
                ],
                'New pages, nested' => [
                        [
                                'NEW1' => [
                                        'title' => 'Parent 1',
                                        'pid' => 0
                                ],
                                'NEW2' => [
                                        'title' => 'Child 1.1',
                                        'pid' => 'NEW1'
                                ],
                                'NEW3' => [
                                        'title' => 'Child 1.2',
                                        'pid' => 'NEW1'
                                ],
                                'NEW4' => [
                                        'title' => 'Parent 2',
                                        'pid' => 0
                                ],
                                'NEW5' => [
                                        'title' => 'Child 2.1',
                                        'pid' => 'NEW4'
                                ],
                                'NEW6' => [
                                        'title' => 'Child 2.1.1',
                                        'pid' => 'NEW5'
                                ],
                                'NEW7' => [
                                        'title' => 'Child 2.2',
                                        'pid' => 'NEW4'
                                ],
                        ],
                        [
                                'NEW6' => [
                                        'title' => 'Child 2.1.1',
                                        'pid' => 'NEW5'
                                ],
                                'NEW2' => [
                                        'title' => 'Child 1.1',
                                        'pid' => 'NEW1'
                                ],
                                'NEW3' => [
                                        'title' => 'Child 1.2',
                                        'pid' => 'NEW1'
                                ],
                                'NEW5' => [
                                        'title' => 'Child 2.1',
                                        'pid' => 'NEW4'
                                ],
                                'NEW7' => [
                                        'title' => 'Child 2.2',
                                        'pid' => 'NEW4'
                                ],
                                'NEW1' => [
                                        'title' => 'Parent 1',
                                        'pid' => 0
                                ],
                                'NEW4' => [
                                        'title' => 'Parent 2',
                                        'pid' => 0
                                ],
                        ]
                ],
                'Page updated to new parent' => [
                        [
                                24 => [
                                        'title' => 'Existing page',
                                        'pid' => 'NEW1'
                                ],
                                'NEW1' => [
                                        'title' => 'Parent 1',
                                        'pid' => 0
                                ]
                        ],
                        [
                                24 => [
                                        'title' => 'Existing page',
                                        'pid' => 'NEW1'
                                ],
                                'NEW1' => [
                                        'title' => 'Parent 1',
                                        'pid' => 0
                                ]
                        ]
                ]
        ];
    }

    /**
     * @param array $input
     * @param array $expected
     * @test
     * @dataProvider sortPagesProvider
     */
    public function sortPagesDataSortsParentAndChildren($input, $expected): void
    {
        $sortedData = $this->subject->sortPagesData($input);
        self::assertSame(
                $sortedData,
                $expected
        );
    }
}