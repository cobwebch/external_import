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

use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use Cobweb\ExternalImport\Domain\Repository\ConfigurationRepository;
use Cobweb\ExternalImport\Domain\Repository\TemporaryKeyRepository;
use Cobweb\ExternalImport\Domain\Repository\UidRepository;
use Cobweb\ExternalImport\Importer;
use Cobweb\ExternalImport\Utility\ReportingUtility;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test case for the External Import importer.
 */
class ImporterTest extends UnitTestCase
{
    /**
     * @var Importer
     */
    protected $subject;

    protected function setUp(): void
    {
        // For unit testing, don't inject all dependencies
        $this->subject = GeneralUtility::makeInstance(
            Importer::class,
            $this->getAccessibleMock(ConfigurationRepository::class),
            $this->getAccessibleMock(
                ReportingUtility::class,
                [],
                [],
                '',
                // Don't call the original constructor to avoid a cascade of dependencies
                false
            ),
            $this->getAccessibleMock(UidRepository::class),
            $this->getAccessibleMock(TemporaryKeyRepository::class),
            new ExtensionConfiguration()
        );
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
                ContextualFeedbackSeverity::ERROR->value => [],
                ContextualFeedbackSeverity::WARNING->value => [],
                ContextualFeedbackSeverity::OK->value => [],
            ],
            $this->subject->getMessages()
        );
    }

    /**
     * @test
     */
    public function addMessagesAddsMessage(): void
    {
        $this->subject->addMessage('foo', ContextualFeedbackSeverity::WARNING);
        self::assertCount(
            1,
            $this->subject->getMessages()[ContextualFeedbackSeverity::WARNING->value]
        );
    }

    /**
     * @test
     */
    public function resetMessagesInitiallyPreparesEmptyStructure(): void
    {
        $this->subject->addMessage('foo', ContextualFeedbackSeverity::WARNING);
        $this->subject->resetMessages();
        self::assertSame(
            [
                ContextualFeedbackSeverity::ERROR->value => [],
                ContextualFeedbackSeverity::WARNING->value => [],
                ContextualFeedbackSeverity::INFO->value => [],
                ContextualFeedbackSeverity::NOTICE->value => [],
                ContextualFeedbackSeverity::OK->value => [],
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
                '<?xml version="1.0" encoding="utf-8" standalone="yes" ?><node>foo</node>',
            ],
            'array' => [
                [
                    'name' => 'Foo',
                    'title' => 'Bar',
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider previewDataProvider
     */
    public function setPreviewDataSetsPreviewData(mixed $data): void
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
