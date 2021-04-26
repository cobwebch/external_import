<?php

declare(strict_types=1);

namespace Cobweb\ExternalImport\Tests\Unit\Event;

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

use Cobweb\ExternalImport\Event\DeleteRecordsPreprocessEvent;
use Cobweb\ExternalImport\Importer;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * Test suite for the InsertRecordPreprocessEvent class
 *
 * @package Cobweb\ExternalImport\Tests\Unit\Event
 */
class DeleteRecordPreprocessEventTest extends UnitTestCase
{
    /**
     * @var DeleteRecordsPreprocessEvent
     */
    protected $subject;

    /**
     * @var Importer
     */
    protected $importer;

    public function setUp(): void
    {
        parent::setUp();
        $this->importer = $this->getAccessibleMock(
            Importer::class,
            null,
            [],
            '',
            false
        );
        $this->subject = new DeleteRecordsPreprocessEvent(
            [],
            $this->importer
        );
    }

    /**
     * @test
     */
    public function getRecordsInitiallyReturnsEmptyArray() :void
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
        $record = ['foo', 'bar'];
        $this->subject->setRecords($record);
        self::assertSame(
            $record,
            $this->subject->getRecords()
        );
    }

    /**
     * @test
     */
    public function getImporterInitiallyReturnsOriginalObject(): void
    {
        self::assertSame(
            $this->importer,
            $this->subject->getImporter()
        );
    }
}