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

use Cobweb\ExternalImport\Domain\Model\BackendUser;
use Cobweb\ExternalImport\Domain\Model\Log;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Messaging\AbstractMessage;

/**
 * Test suite for the Log model class.
 */
class LogTest extends UnitTestCase
{
    /**
     * @var Log
     */
    protected Log $subject;

    public function setUp(): void
    {
        parent::setUp();
        $this->subject = new Log();
    }

    /**
     * @test
     */
    public function getStatusInitiallyReturnsNoticeLevel(): void
    {
        self::assertEquals(
            AbstractMessage::NOTICE,
            $this->subject->getStatus()
        );
    }

    /**
     * @test
     */
    public function setStatusSetsStatus(): void
    {
        $this->subject->setStatus(3);
        self::assertEquals(
            3,
            $this->subject->getStatus()
        );
    }

    /**
     * @test
     */
    public function getCrdateInitiallyReturnsNull(): void
    {
        self::assertNull($this->subject->getCrdate());
    }

    /**
     * @test
     */
    public function setCrdateSetsCrdate(): void
    {
        $now = new \DateTime();
        $this->subject->setCrdate(
            $now
        );
        self::assertEquals(
            $now->getTimestamp(),
            $this->subject->getCrdate()->getTimestamp()
        );
    }

    /**
     * @test
     */
    public function getCruserIdInitiallyReturnsNull(): void
    {
        self::assertNull($this->subject->getCruserId());
    }

    /**
     * @test
     */
    public function setCruserIdSetsCruserId(): void
    {
        $user = new BackendUser();
        $user->setUserName('ford.escort');
        $this->subject->setCruserId(
            $user
        );
        self::assertEquals(
            'ford.escort',
            $this->subject->getCruserId()->getUserName()
        );
    }

    /**
     * @test
     */
    public function getConfigurationInitiallyReturnsEmptyString(): void
    {
        self::assertSame(
            '',
            $this->subject->getConfiguration()
        );
    }

    /**
     * @test
     */
    public function setConfigurationSetsConfiguration(): void
    {
        $this->subject->setConfiguration('foo');
        self::assertSame(
            'foo',
            $this->subject->getConfiguration()
        );
    }

    /**
     * @test
     */
    public function getContextInitiallyReturnsEmptyString(): void
    {
        self::assertSame(
            '',
            $this->subject->getContext()
        );
    }

    /**
     * @test
     */
    public function setContextSetsContext(): void
    {
        $this->subject->setContext('foo');
        self::assertSame(
            'foo',
            $this->subject->getContext()
        );
    }

    /**
     * @test
     */
    public function getMessageInitiallyReturnsEmptyString(): void
    {
        self::assertSame(
            '',
            $this->subject->getMessage()
        );
    }

    /**
     * @test
     */
    public function setMessageSetsMessage(): void
    {
        $this->subject->setMessage('foo');
        self::assertSame(
            'foo',
            $this->subject->getMessage()
        );
    }
}
