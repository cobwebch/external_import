<?php

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
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

/**
 * Test case for the QueryParameters model object.
 *
 * @package Cobweb\ExternalImport\Tests\Unit\Utility
 */
class QueryParametersTest extends UnitTestCase
{
    /**
     * @var QueryParameters
     */
    protected $subject;

    public function setUp(): void
    {
        parent::setUp();
        $this->subject = GeneralUtility::makeInstance(QueryParameters::class);
    }

    /**
     * @test
     */
    public function getDrawInitiallyReturnsZero(): void
    {
        self::assertSame(
                0,
                $this->subject->getDraw()
        );
    }

    /**
     * @test
     */
    public function getSearchInitiallyReturnsEmptyString(): void
    {
        self::assertSame(
                '',
                $this->subject->getSearch()
        );
    }

    /**
     * @test
     */
    public function getSearchColumnsInitiallyReturnsEmptyArray(): void
    {
        self::assertSame(
                [],
                $this->subject->getSearchColumns()
        );
    }

    /**
     * @test
     */
    public function getLimitInitiallyReturnsZero(): void
    {
        self::assertSame(
                0,
                $this->subject->getLimit()
        );
    }

    /**
     * @test
     */
    public function getOffsetInitiallyReturnsZero(): void
    {
        self::assertSame(
                0,
                $this->subject->getOffset()
        );
    }

    /**
     * @test
     */
    public function getOrderInitiallyReturnsEmptyString(): void
    {
        self::assertSame(
                '',
                $this->subject->getOrder()
        );
    }

    /**
     * @test
     */
    public function getDirectionInitiallyReturnsDesc(): void
    {
        self::assertSame(
                QueryInterface::ORDER_DESCENDING,
                $this->subject->getDirection()
        );
    }

    /**
     * @test
     */
    public function setDrawSetsValue(): void
    {
        $this->subject->setDraw(2);
        self::assertSame(
                2,
                $this->subject->getDraw()
        );
    }

    /**
     * @test
     */
    public function setSearchSetsValue(): void
    {
        $this->subject->setSearch('foo');
        self::assertSame(
                'foo',
                $this->subject->getSearch()
        );
    }

    /**
     * @test
     */
    public function setSearchColumnsKeepsOnlySearchableColumns(): void
    {
        $this->subject->setSearchColumns(
                [
                        [
                                'searchable' => 'true',
                                'name' => 'foo'
                        ],
                        [
                                'searchable' => 'false',
                                'name' => 'not me'
                        ],
                        [
                                'searchable' => 'true',
                                'name' => 'bar'
                        ]
                ]
        );
        self::assertSame(
                ['foo', 'bar'],
                $this->subject->getSearchColumns()
        );
    }

    /**
     * @test
     */
    public function setLimitSetsValue(): void
    {
        $this->subject->setLimit(2);
        self::assertSame(
                2,
                $this->subject->getLimit()
        );
    }

    /**
     * @test
     */
    public function setOffsetSetsValue(): void
    {
        $this->subject->setOffset(2);
        self::assertSame(
                2,
                $this->subject->getOffset()
        );
    }

    /**
     * @test
     */
    public function setOrderSetsValue(): void
    {
        $this->subject->setOrder('foo');
        self::assertSame(
                'foo',
                $this->subject->getOrder()
        );
    }

    public function directionValueProvider(): array
    {
        return [
                'ASC is ASC' => [
                        'ASC',
                        QueryInterface::ORDER_ASCENDING
                ],
                'DESC is DESC' => [
                        'DESC',
                        QueryInterface::ORDER_DESCENDING
                ],
                'asc is DESC' => [
                        'asc',
                        QueryInterface::ORDER_DESCENDING
                ],
                'wathever is DESC' => [
                        'foo',
                        QueryInterface::ORDER_DESCENDING
                ]
        ];
    }

    /**
     * @test
     * @dataProvider directionValueProvider
     * @param string $value
     * @param string $expected
     */
    public function setDirectionSetsSanitizedValue($value, $expected): void
    {
        $this->subject->setDirection($value);
        self::assertSame(
                $expected,
                $this->subject->getDirection()
        );
    }
}