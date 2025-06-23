<?php

declare(strict_types=1);

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

use Cobweb\ExternalImport\Domain\Model\Dto\ChildrenSorting;
use Cobweb\ExternalImport\Domain\Repository\TcaRepositoryInterface;
use Cobweb\ExternalImport\Step\StoreDataStep;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Unit test suite for the Store Data step class.
 */
class StoreDataStepTest extends UnitTestCase
{
    protected StoreDataStep $subject;

    public function setUp(): void
    {
        parent::setUp();
        $this->subject = GeneralUtility::makeInstance(
            StoreDataStep::class,
            $this->getAccessibleMock(
                EventDispatcher::class,
                null,
                [],
                '',
                false
            ),
            $this->getAccessibleMock(
                ChildrenSorting::class,
                null,
                [],
                '',
                false
            ),
            self::createStub(TcaRepositoryInterface::class)
        );
    }

    public static function sortPagesProvider(): array
    {
        return [
            'No new pages - no sorting needed' => [
                'input' => [
                    34 => [
                        'title' => 'Foo',
                        'pid' => 23,
                    ],
                    42 => [
                        'title' => 'Bar',
                        'pid' => 34,
                    ],
                ],
                'expected' => [
                    34 => [
                        'title' => 'Foo',
                        'pid' => 23,
                    ],
                    42 => [
                        'title' => 'Bar',
                        'pid' => 34,
                    ],
                ],
            ],
            'New pages, not nested - no sorting needed' => [
                'input' => [
                    'NEW1' => [
                        'title' => 'Parent 1',
                        'pid' => 4,
                    ],
                    'NEW2' => [
                        'title' => 'Parent 2',
                        'pid' => 4,
                    ],
                    'NEW3' => [
                        'title' => 'Parent 3',
                        'pid' => 10,
                    ],
                ],
                'expected' => [
                    'NEW1' => [
                        'title' => 'Parent 1',
                        'pid' => 4,
                    ],
                    'NEW2' => [
                        'title' => 'Parent 2',
                        'pid' => 4,
                    ],
                    'NEW3' => [
                        'title' => 'Parent 3',
                        'pid' => 10,
                    ],
                ],
            ],
            'New pages, nested' => [
                'input' => [
                    'NEW1' => [
                        'title' => 'Parent 1',
                        'pid' => 0,
                    ],
                    'NEW2' => [
                        'title' => 'Child 1.1',
                        'pid' => 'NEW1',
                    ],
                    'NEW3' => [
                        'title' => 'Child 1.2',
                        'pid' => 'NEW1',
                    ],
                    'NEW4' => [
                        'title' => 'Parent 2',
                        'pid' => 0,
                    ],
                    'NEW5' => [
                        'title' => 'Child 2.1',
                        'pid' => 'NEW4',
                    ],
                    'NEW6' => [
                        'title' => 'Child 2.1.1',
                        'pid' => 'NEW5',
                    ],
                    'NEW7' => [
                        'title' => 'Child 2.2',
                        'pid' => 'NEW4',
                    ],
                ],
                'expected' => [
                    'NEW6' => [
                        'title' => 'Child 2.1.1',
                        'pid' => 'NEW5',
                    ],
                    'NEW2' => [
                        'title' => 'Child 1.1',
                        'pid' => 'NEW1',
                    ],
                    'NEW3' => [
                        'title' => 'Child 1.2',
                        'pid' => 'NEW1',
                    ],
                    'NEW5' => [
                        'title' => 'Child 2.1',
                        'pid' => 'NEW4',
                    ],
                    'NEW7' => [
                        'title' => 'Child 2.2',
                        'pid' => 'NEW4',
                    ],
                    'NEW1' => [
                        'title' => 'Parent 1',
                        'pid' => 0,
                    ],
                    'NEW4' => [
                        'title' => 'Parent 2',
                        'pid' => 0,
                    ],
                ],
            ],
            'Page updated to new parent' => [
                'input' => [
                    24 => [
                        'title' => 'Existing page',
                        'pid' => 'NEW1',
                    ],
                    'NEW1' => [
                        'title' => 'Parent 1',
                        'pid' => 0,
                    ],
                ],
                'expected' => [
                    24 => [
                        'title' => 'Existing page',
                        'pid' => 'NEW1',
                    ],
                    'NEW1' => [
                        'title' => 'Parent 1',
                        'pid' => 0,
                    ],
                ],
            ],
        ];
    }

    #[Test] #[DataProvider('sortPagesProvider')]
    public function sortPagesDataSortsParentAndChildren(array $input, array $expected): void
    {
        $sortedData = $this->subject->sortPagesData($input);
        self::assertSame(
            $sortedData,
            $expected
        );
    }
}
