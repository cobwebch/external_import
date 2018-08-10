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
    public function getExtensionConfigurationInitiallyReturnsEmptyArray()
    {
        self::assertSame(
                [],
                $this->subject->getExtensionConfiguration()
        );
    }

    /**
     * @test
     */
    public function getExternalConfigurationInitiallyReturnsNull()
    {
        self::assertNull(
                $this->subject->getExternalConfiguration()
        );
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

    /**
     * @test
     */
    public function isTestModeInitiallyReturnsFalse()
    {
        self::assertFalse(
                $this->subject->isTestMode()
        );
    }

    /**
     * @test
     */
    public function setTestModeSetsTestMode()
    {
        $this->subject->setTestMode(true);
        self::assertTrue(
                $this->subject->isTestMode()
        );
    }

    /**
     * @test
     */
    public function isPreviewInitiallyReturnsFalse() {
        self::assertFalse($this->subject->isPreview());
    }

    /**
     * @test
     */
    public function getPreviewStepInitiallyReturnsEmptyString()
    {
        self::assertSame(
                '',
                $this->subject->getPreviewStep()
        );
    }

    /**
     * @test
     */
    public function setPreviewStepSetsPreviewStep()
    {
        $this->subject->setPreviewStep('foo');
        self::assertSame(
                'foo',
                $this->subject->getPreviewStep()
        );
    }

    /**
     * @test
     */
    public function getPreviewDataInitiallyReturnsNull()
    {
        self::assertNull($this->subject->getPreviewData());
    }

    public function previewDataProvider()
    {
        return [
                'string' => [
                        '<?xml version="1.0" encoding="utf-8" standalone="yes" ?><node>foo</node>'
                ],
                'array' => [
                        [
                                'name' => 'Foo',
                                'title' => 'Bar'
                        ]
                ]
        ];
    }

    /**
     * @test
     * @dataProvider previewDataProvider
     * @param mixed $data
     */
    public function setPreviewDataSetsPreviewData($data)
    {
        $this->subject->setPreviewData($data);
        self::assertSame(
                $data,
                $this->subject->getPreviewData()
        );
    }

    /**
     * @test
     */
    public function resetPreviewDataSetsPreviewDataToNull()
    {
        $this->subject->resetPreviewData();
        self::assertNull(
                $this->subject->getPreviewData()
        );
    }

    /**
     * @test
     */
    public function generateTemporaryKeyInTestModeGeneratesPredictableKey() {
        // NOTE: any step will do
        $this->subject->setTestMode(true);
        self::assertEquals(
                'NEW1',
                $this->subject->generateTemporaryKey()
        );
    }

    /**
     * @test
     */
    public function getStartTimeInitiallyReturnsZero()
    {
        self::assertEquals(
                0,
                $this->subject->getStartTime()
        );
    }

    /**
     * @test
     */
    public function setStartTimeSetsStartTime()
    {
        $now = time();
        $this->subject->setStartTime($now);
        self::assertEquals(
                $now,
                $this->subject->getStartTime()
        );
    }

    /**
     * @test
     */
    public function getEndTimeInitiallyReturnsZero()
    {
        self::assertEquals(
                0,
                $this->subject->getEndTime()
        );
    }

    /**
     * @test
     */
    public function setEndTimeSetsEndTime()
    {
        $now = time();
        $this->subject->setEndTime($now);
        self::assertEquals(
                $now,
                $this->subject->getEndTime()
        );
    }
}