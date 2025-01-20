<?php

declare(strict_types = 1);

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

use Cobweb\ExternalImport\Domain\Repository\ConfigurationRepository;
use Cobweb\ExternalImport\Domain\Repository\TemporaryKeyRepository;
use Cobweb\ExternalImport\Domain\Repository\UidRepository;
use Cobweb\ExternalImport\Importer;
use Cobweb\ExternalImport\Utility\ReportingUtility;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case for the External Import importer.
 */
class ImporterTest extends UnitTestCase
{
    protected Importer $subject;

    protected function setUp(): void
    {
        parent::setUp();
        // For unit testing, don't inject all dependencies
        $extensionConfiguration = $this->createMock(ExtensionConfiguration::class);
        $extensionConfiguration->method('get')
            ->willReturn(
                [
                    'debug' => 0,
                ]
            );
        $this->subject = GeneralUtility::makeInstance(
            Importer::class,
            $this->getAccessibleMock(
                ConfigurationRepository::class,
                [],
                [],
                '',
                // Don't call the original constructor to avoid a cascade of dependencies
                false
            ),
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
            $extensionConfiguration
        );
        $this->resetSingletonInstances = true;
    }

    #[Test]
    public function getExternalConfigurationInitiallyReturnsNull(): void
    {
        self::assertNull(
            $this->subject->getExternalConfiguration()
        );
    }

    #[Test]
    public function getMessagesInitiallyReturnsEmptyStructure(): void
    {
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

    #[Test]
    public function addMessagesAddsMessage(): void
    {
        $this->subject->addMessage('foo', ContextualFeedbackSeverity::WARNING);
        self::assertCount(
            1,
            $this->subject->getMessages()[ContextualFeedbackSeverity::WARNING->value]
        );
    }

    #[Test]
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

    #[Test]
    public function getContextInitiallyReturnsManualContext(): void
    {
        self::assertSame(
            'manual',
            $this->subject->getContext()
        );
    }

    #[Test]
    public function setContextSetsContext(): void
    {
        $this->subject->setContext('cli');
        self::assertSame(
            'cli',
            $this->subject->getContext()
        );
    }

    #[Test]
    public function isDebugInitiallyReturnsFalse(): void
    {
        self::assertFalse($this->subject->isDebug());
    }

    #[Test]
    public function setDebugSetsDebugFlag(): void
    {
        $this->subject->setDebug(true);
        self::assertTrue($this->subject->isDebug());
    }

    #[Test]
    public function isVerboseInitiallyReturnsFalse(): void
    {
        self::assertFalse($this->subject->isVerbose());
    }

    #[Test]
    public function setVerboseSetsVerboseFlag(): void
    {
        $this->subject->setVerbose(true);
        self::assertTrue($this->subject->isVerbose());
    }

    #[Test]
    public function isTestModeInitiallyReturnsFalse(): void
    {
        self::assertFalse(
            $this->subject->isTestMode()
        );
    }

    #[Test]
    public function setTestModeSetsTestMode(): void
    {
        $this->subject->setTestMode(true);
        self::assertTrue(
            $this->subject->isTestMode()
        );
    }

    #[Test]
    public function isPreviewInitiallyReturnsFalse(): void
    {
        self::assertFalse($this->subject->isPreview());
    }

    #[Test]
    public function getPreviewStepInitiallyReturnsEmptyString(): void
    {
        self::assertSame(
            '',
            $this->subject->getPreviewStep()
        );
    }

    #[Test]
    public function setPreviewStepSetsPreviewStep(): void
    {
        $this->subject->setPreviewStep('foo');
        self::assertSame(
            'foo',
            $this->subject->getPreviewStep()
        );
    }

    #[Test]
    public function getPreviewDataInitiallyReturnsNull(): void
    {
        self::assertNull($this->subject->getPreviewData());
    }

    public static function previewDataProvider(): array
    {
        return [
            'string' => [
                'data' => '<?xml version="1.0" encoding="utf-8" standalone="yes" ?><node>foo</node>',
            ],
            'array' => [
                'data' => [
                    'name' => 'Foo',
                    'title' => 'Bar',
                ],
            ],
        ];
    }

    #[Test] #[DataProvider('previewDataProvider')]
    public function setPreviewDataSetsPreviewData(mixed $data): void
    {
        $this->subject->setPreviewData($data);
        self::assertSame(
            $data,
            $this->subject->getPreviewData()
        );
    }

    #[Test]
    public function resetPreviewDataSetsPreviewDataToNull(): void
    {
        $this->subject->resetPreviewData();
        self::assertNull(
            $this->subject->getPreviewData()
        );
    }

    #[Test]
    public function getStartTimeInitiallyReturnsZero(): void
    {
        self::assertEquals(
            0,
            $this->subject->getStartTime()
        );
    }

    #[Test]
    public function setStartTimeSetsStartTime(): void
    {
        $now = time();
        $this->subject->setStartTime($now);
        self::assertEquals(
            $now,
            $this->subject->getStartTime()
        );
    }

    #[Test]
    public function getEndTimeInitiallyReturnsZero(): void
    {
        self::assertEquals(
            0,
            $this->subject->getEndTime()
        );
    }

    #[Test]
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
