<?php

namespace Cobweb\ExternalImport\Tests\Unit;

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

use Cobweb\ExternalImport\Importer;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * Test case for the External Import importer.
 *
 * @author Francois Suter <typo3@cobweb.ch>
 * @package TYPO3
 * @subpackage tx_externalimport
 */
class ImporterTest extends UnitTestCase
{

    /**
     * @var Importer
     */
    protected $subject = null;

    protected function setUp()
    {
        // Note: the Importer class normally needs to be instanciated via the ObjectManager,
        // but we don't need all the dependency injection for unit tests.
        $this->subject = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(Importer::class);
    }

    /**
     * @test
     */
    public function getContextInitiallyReturnsManualContext() {
        self::assertSame(
                'manual',
                $this->subject->getContext()
        );
    }

    /**
     * @test
     */
    public function setContextSetsContext() {
        $this->subject->setContext('cli');
        self::assertSame(
                'cli',
                $this->subject->getContext()
        );
    }

    /**
     * @test
     */
    public function isDebugInitiallyReturnsFalse() {
        self::assertFalse($this->subject->isDebug());
    }

    /**
     * @test
     */
    public function setDebugSetsDebugFlag() {
        $this->subject->setDebug(true);
        self::assertTrue($this->subject->isDebug());
    }

    /**
     * @test
     */
    public function isVerboseInitiallyReturnsFalse() {
        self::assertFalse($this->subject->isVerbose());
    }

    /**
     * @test
     */
    public function setVerboseSetsVerboseFlag() {
        $this->subject->setVerbose(true);
        self::assertTrue($this->subject->isVerbose());
    }
}