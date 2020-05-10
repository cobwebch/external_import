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
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
    protected $subject;

    protected function setUp()
    {
        // Note: the Importer class normally needs to be instantiated via the ObjectManager,
        // but we don't need all the dependency injection for unit tests.
        $this->subject = GeneralUtility::makeInstance(Importer::class);
    }

    /**
     * @test
     */
    public function getExtensionConfigurationInitiallyReturnsDefaultConfiguration(): void
    {
        self::assertSame(
                [
                        'storagePID' => '0',
                        'logStorage' => '0',
                        'timelimit' => '-1',
                        'reportEmail' => '',
                        'reportSubject' => '',
                        'debug' => '0',
                        'disableLog' => '0',
                ],
                $this->subject->getExtensionConfiguration()
        );
    }

    /**
     * @test
     */
    public function getExternalConfigurationInitiallyReturnsNull(): void
    {
        self::assertNull(
                $this->subject->getExternalConfiguration()
        );
    }

    /**
     * @test
     */
    public function getMessagesInitiallyReturnsEmptyStructure(): void
    {
        self::assertSame(
                [
                        AbstractMessage::ERROR => [],
                        AbstractMessage::WARNING => [],
                        AbstractMessage::OK => []
                ],
                $this->subject->getMessages()
        );
    }

    /**
     * @test
     */
    public function addMessagesAddsMessage(): void
    {
        $this->subject->addMessage('foo', AbstractMessage::WARNING);
        self::assertCount(
                1,
                $this->subject->getMessages()[AbstractMessage::WARNING]
        );
    }

    /**
     * @test
     */
    public function resetMessagesInitiallyPreparesEmptyStructure(): void
    {
        $this->subject->addMessage('foo', AbstractMessage::WARNING);
        $this->subject->resetMessages();
        self::assertSame(
                [
                        AbstractMessage::ERROR => [],
                        AbstractMessage::WARNING => [],
                        AbstractMessage::OK => []
                ],
                $this->subject->getMessages()
        );
    }

    /**
     * @test
     */
    public function getContextInitiallyReturnsManualContext(): void
    {
        self::assertSame(
                'manual',
                $this->subject->getContext()
        );
    }

    /**
     * @test
     */
    public function setContextSetsContext(): void
    {
        $this->subject->setContext('cli');
        self::assertSame(
                'cli',
                $this->subject->getContext()
        );
    }

    /**
     * @test
     */
    public function isDebugInitiallyReturnsFalse(): void
    {
        self::assertFalse($this->subject->isDebug());
    }

    /**
     * @test
     */
    public function setDebugSetsDebugFlag(): void
    {
        $this->subject->setDebug(true);
        self::assertTrue($this->subject->isDebug());
    }

    /**
     * @test
     */
    public function isVerboseInitiallyReturnsFalse(): void
    {
        self::assertFalse($this->subject->isVerbose());
    }

    /**
     * @test
     */
    public function setVerboseSetsVerboseFlag(): void
    {
        $this->subject->setVerbose(true);
        self::assertTrue($this->subject->isVerbose());
    }

    /**
     * @test
     */
    public function isTestModeInitiallyReturnsFalse(): void
    {
        self::assertFalse(
                $this->subject->isTestMode()
        );
    }

    /**
     * @test
     */
    public function setTestModeSetsTestMode(): void
    {
        $this->subject->setTestMode(true);
        self::assertTrue(
                $this->subject->isTestMode()
        );
    }

    /**
     * @test
     */
    public function isPreviewInitiallyReturnsFalse(): void
    {
        self::assertFalse($this->subject->isPreview());
    }

    /**
     * @test
     */
    public function getPreviewStepInitiallyReturnsEmptyString(): void
    {
        self::assertSame(
                '',
                $this->subject->getPreviewStep()
        );
    }

    /**
     * @test
     */
    public function setPreviewStepSetsPreviewStep(): void
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
    public function getPreviewDataInitiallyReturnsNull(): void
    {
        self::assertNull($this->subject->getPreviewData());
    }

    public function previewDataProvider(): array
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
    public function setPreviewDataSetsPreviewData($data): void
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
    public function resetPreviewDataSetsPreviewDataToNull(): void
    {
        $this->subject->resetPreviewData();
        self::assertNull(
                $this->subject->getPreviewData()
        );
    }

    /**
     * @test
     */
    public function generateTemporaryKeyInTestModeGeneratesPredictableKey(): void
    {
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
    public function getStartTimeInitiallyReturnsZero(): void
    {
        self::assertEquals(
                0,
                $this->subject->getStartTime()
        );
    }

    /**
     * @test
     */
    public function setStartTimeSetsStartTime(): void
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
    public function getEndTimeInitiallyReturnsZero(): void
    {
        self::assertEquals(
                0,
                $this->subject->getEndTime()
        );
    }

    /**
     * @test
     */
    public function setEndTimeSetsEndTime(): void
    {
        $now = time();
        $this->subject->setEndTime($now);
        self::assertEquals(
                $now,
                $this->subject->getEndTime()
        );
    }
}