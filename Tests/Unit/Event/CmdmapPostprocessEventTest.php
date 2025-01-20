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

use Cobweb\ExternalImport\Event\CmdmapPostprocessEvent;
use Cobweb\ExternalImport\Importer;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test suite for the InsertRecordPreprocessEvent class
 */
class CmdmapPostprocessEventTest extends UnitTestCase
{
    protected CmdmapPostprocessEvent $subject;
    protected Importer $importer;

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
        $this->subject = new CmdmapPostprocessEvent(
            [],
            $this->importer
        );
    }

    #[Test]
    public function getRecordsInitiallyReturnsEmptyArray(): void
    {
        self::assertSame(
            [],
            $this->subject->getRecords()
        );
    }

    #[Test]
    public function getImporterInitiallyReturnsOriginalObject(): void
    {
        self::assertSame(
            $this->importer,
            $this->subject->getImporter()
        );
    }
}
