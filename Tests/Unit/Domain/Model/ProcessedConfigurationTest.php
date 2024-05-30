<?php

declare(strict_types=1);

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

namespace Cobweb\ExternalImport\Tests\Unit\Domain\Model;

use Cobweb\ExternalImport\Domain\Model\ChildrenConfiguration;
use Cobweb\ExternalImport\Domain\Model\ProcessedConfiguration;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ProcessedConfigurationTest extends UnitTestCase
{
    protected ProcessedConfiguration $subject;

    public function setUp(): void
    {
        parent::setUp();
        $this->subject = GeneralUtility::makeInstance(ProcessedConfiguration::class);
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
    public function addFieldExcludedFromInsertsAddsColumn(): void
    {
        $this->subject->addFieldExcludedFromInserts('foo');
        self::assertSame(
            ['foo'],
            $this->subject->getFieldsExcludedFromInserts()
        );
        $this->subject->addFieldExcludedFromInserts('bar');
        self::assertSame(
            ['foo', 'bar'],
            $this->subject->getFieldsExcludedFromInserts()
        );
    }

    /**
     * @test
     */
    public function setFieldsExcludedFromInsertsSetsArray(): void
    {
        $this->subject->setFieldsExcludedFromInserts(['foo', 'bar']);
        self::assertSame(
            ['foo', 'bar'],
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

    /**
     * @test
     */
    public function addFieldExcludedFromUpdatesAddsColumn(): void
    {
        $this->subject->addFieldExcludedFromUpdates('foo');
        self::assertSame(
            ['foo'],
            $this->subject->getFieldsExcludedFromUpdates()
        );
        $this->subject->addFieldExcludedFromUpdates('bar');
        self::assertSame(
            ['foo', 'bar'],
            $this->subject->getFieldsExcludedFromUpdates()
        );
    }

    /**
     * @test
     */
    public function setFieldsExcludedFromUpdatesSetsArray(): void
    {
        $this->subject->setFieldsExcludedFromUpdates(['foo', 'bar']);
        self::assertSame(
            ['foo', 'bar'],
            $this->subject->getFieldsExcludedFromUpdates()
        );
    }

    /**
     * @test
     */
    public function getChildColumnsInitiallyReturnsEmptyArray(): void
    {
        self::assertSame(
            [],
            $this->subject->getChildColumns()
        );
    }

    /**
     * @test
     */
    public function addChildColumnAddsChildrenConfigurationObject(): void
    {
        $childA = new ChildrenConfiguration();
        $childA->setUpdateAllowed(false);
        $childB = new ChildrenConfiguration();
        $childB->setControlColumnsForDelete(['foo', 'bar']);
        $this->subject->addChildColumn('a', $childA);
        self::assertSame(
            ['a' => $childA],
            $this->subject->getChildColumns()
        );
        $this->subject->addChildColumn('b', $childB);
        self::assertSame(
            ['a' => $childA, 'b' => $childB],
            $this->subject->getChildColumns()
        );
    }

    /**
     * @test
     */
    public function setChildColumnsSetsChildrenConfigurationArrayt(): void
    {
        $childA = new ChildrenConfiguration();
        $childA->setUpdateAllowed(false);
        $childB = new ChildrenConfiguration();
        $childB->setControlColumnsForDelete(['foo', 'bar']);
        $this->subject->setChildColumns(['a' => $childA, 'b' => $childB]);
        self::assertSame(
            ['a' => $childA, 'b' => $childB],
            $this->subject->getChildColumns()
        );
    }

    /**
     * @test
     */
    public function hasChildColumnsInitiallyReturnsFalse(): void
    {
        self::assertFalse(
            $this->subject->hasChildColumns()
        );
    }

    /**
     * @test
     */
    public function hasChildColumnsReturnsTrueAfterAddingColumn(): void
    {
        $this->subject->addChildColumn(
            'foo',
            new ChildrenConfiguration()
        );
        self::assertTrue(
            $this->subject->hasChildColumns()
        );
    }

    /**
     * @test
     */
    public function getNullableColumnsInitiallyReturnsEmptyArray(): void
    {
        self::assertSame(
            [],
            $this->subject->getNullableColumns()
        );
    }

    /**
     * @test
     */
    public function setNullableColumnsSetsColumnsList(): void
    {
        $this->subject->setNullableColumns(['foo', 'bar']);
        self::assertSame(
            ['foo', 'bar'],
            $this->subject->getNullableColumns()
        );
    }

    /**
     * @test
     */
    public function addNullableColumnAddsColumnToList(): void
    {
        $this->subject->setNullableColumns(['foo']);
        $this->subject->addNullableColumn('bar');
        self::assertSame(
            ['foo', 'bar'],
            $this->subject->getNullableColumns()
        );
    }

    /**
     * @test
     */
    public function isNullableColumnReturnsBoolean(): void
    {
        $this->subject->setNullableColumns(['foo', 'bar']);
        self::assertTrue(
            $this->subject->isNullableColumn('foo')
        );
        self::assertFalse(
            $this->subject->isNullableColumn('baz')
        );
    }
}
