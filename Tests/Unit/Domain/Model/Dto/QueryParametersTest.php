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

use Cobweb\ExternalImport\Domain\Model\Dto\QueryParameters;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case for the QueryParameters model object.
 */
class QueryParametersTest extends UnitTestCase
{
    protected QueryParameters $subject;

    public function setUp(): void
    {
        parent::setUp();
        $this->subject = GeneralUtility::makeInstance(QueryParameters::class);
    }

    #[Test]
    public function getDrawInitiallyReturnsZero(): void
    {
        self::assertSame(
            0,
            $this->subject->getDraw()
        );
    }

    #[Test]
    public function getSearchInitiallyReturnsEmptyString(): void
    {
        self::assertSame(
            '',
            $this->subject->getSearch()
        );
    }

    #[Test]
    public function getSearchColumnsInitiallyReturnsEmptyArray(): void
    {
        self::assertSame(
            [],
            $this->subject->getSearchColumns()
        );
    }

    #[Test]
    public function getLimitInitiallyReturnsZero(): void
    {
        self::assertSame(
            0,
            $this->subject->getLimit()
        );
    }

    #[Test]
    public function getOffsetInitiallyReturnsZero(): void
    {
        self::assertSame(
            0,
            $this->subject->getOffset()
        );
    }

    #[Test]
    public function getOrderInitiallyReturnsEmptyString(): void
    {
        self::assertSame(
            '',
            $this->subject->getOrder()
        );
    }

    #[Test]
    public function getDirectionInitiallyReturnsDesc(): void
    {
        self::assertSame(
            QueryInterface::ORDER_DESCENDING,
            $this->subject->getDirection()
        );
    }

    #[Test]
    public function setDrawSetsValue(): void
    {
        $this->subject->setDraw(2);
        self::assertSame(
            2,
            $this->subject->getDraw()
        );
    }

    #[Test]
    public function setSearchSetsValue(): void
    {
        $this->subject->setSearch('foo');
        self::assertSame(
            'foo',
            $this->subject->getSearch()
        );
    }

    #[Test]
    public function setSearchColumnsKeepsOnlySearchableColumns(): void
    {
        $this->subject->setSearchColumns(
            [
                [
                    'searchable' => 'true',
                    'name' => 'foo',
                ],
                [
                    'searchable' => 'false',
                    'name' => 'not me',
                ],
                [
                    'searchable' => 'true',
                    'name' => 'bar',
                ],
            ]
        );
        self::assertSame(
            ['foo', 'bar'],
            $this->subject->getSearchColumns()
        );
    }

    #[Test]
    public function setLimitSetsValue(): void
    {
        $this->subject->setLimit(2);
        self::assertSame(
            2,
            $this->subject->getLimit()
        );
    }

    #[Test]
    public function setOffsetSetsValue(): void
    {
        $this->subject->setOffset(2);
        self::assertSame(
            2,
            $this->subject->getOffset()
        );
    }

    #[Test]
    public function setOrderSetsValue(): void
    {
        $this->subject->setOrder('foo');
        self::assertSame(
            'foo',
            $this->subject->getOrder()
        );
    }

    public static function directionValueProvider(): array
    {
        return [
            'ASC is ASC' => [
                'value' => 'ASC',
                'expected' => QueryInterface::ORDER_ASCENDING,
            ],
            'DESC is DESC' => [
                'value' => 'DESC',
                'expected' => QueryInterface::ORDER_DESCENDING,
            ],
            'asc is DESC' => [
                'value' => 'asc',
                'expected' => QueryInterface::ORDER_DESCENDING,
            ],
            'wathever is DESC' => [
                'value' => 'foo',
                'expected' => QueryInterface::ORDER_DESCENDING,
            ],
        ];
    }

    #[Test] #[DataProvider('directionValueProvider')]
    public function setDirectionSetsSanitizedValue(string $value, string $expected): void
    {
        $this->subject->setDirection($value);
        self::assertSame(
            $expected,
            $this->subject->getDirection()
        );
    }
}
