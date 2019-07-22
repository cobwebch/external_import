<?php
namespace Cobweb\ExternalImport\Tests\Unit\Domain\Model;

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


use Cobweb\ExternalImport\Domain\Model\Data;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * Test suite for the Data model class.
 *
 * @package Cobweb\ExternalImport\Tests\Unit\Domain\Model
 */
class DataTest extends UnitTestCase
{
    /**
     * @var Data
     */
    protected $subject;

    public function setUp()
    {
        parent::setUp();
        $this->subject = new Data();
    }

    /**
     * @test
     */
    public function getRawDataInitiallyReturnsNull(): void
    {
        self::assertNull($this->subject->getRawData());
    }

    /**
     * @test
     */
    public function setRawDataSetsRawData(): void
    {
        $this->subject->setRawData('foo');
        self::assertSame(
                'foo',
                $this->subject->getRawData()
        );
    }

    /**
     * @test
     */
    public function getRecordsInitiallyReturnsEmptyArray(): void
    {
        self::assertSame(
                [],
                $this->subject->getRecords()
        );
    }

    /**
     * @test
     */
    public function setRecordsSetsRecords(): void
    {
        $this->subject->setRecords([1, 2]);
        self::assertSame(
                [1, 2],
                $this->subject->getRecords()
        );
    }
}