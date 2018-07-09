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
use Cobweb\ExternalImport\Domain\Model\Log;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * Test suite for the Log model class.
 *
 * @package Cobweb\ExternalImport\Tests\Unit\Domain\Model
 */
class LogTest extends UnitTestCase
{
    /**
     * @var Log
     */
    protected $subject;

    public function setUp()
    {
        parent::setUp();
        $this->subject = new Log();
    }

    /**
     * @test
     */
    public function getStatusInitiallyReturnsNull()
    {
        self::assertNull($this->subject->getStatus());
    }

    /**
     * @test
     */
    public function setStatusSetsStatus()
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
    public function getCrdateInitiallyReturnsNull()
    {
        self::assertNull($this->subject->getCrdate());
    }

    /**
     * @test
     */
    public function setCrdateSetsCrdate()
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
    public function getCruserIdInitiallyReturnsNull()
    {
        self::assertNull($this->subject->getCruserId());
    }

    /**
     * @test
     */
    public function setCruserIdSetsCruserId()
    {
        $user = new \TYPO3\CMS\Extbase\Domain\Model\BackendUser();
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
    public function getConfigurationInitiallyReturnsNull()
    {
        self::assertNull($this->subject->getConfiguration());
    }

    /**
     * @test
     */
    public function setConfigurationSetsConfiguration()
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
    public function getContextInitiallyReturnsNull()
    {
        self::assertNull($this->subject->getContext());
    }

    /**
     * @test
     */
    public function setContextSetsContext()
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
    public function getMessageInitiallyReturnsNull()
    {
        self::assertNull($this->subject->getMessage());
    }

    /**
     * @test
     */
    public function setMessageSetsMessage()
    {
        $this->subject->setMessage('foo');
        self::assertSame(
                'foo',
                $this->subject->getMessage()
        );
    }
}