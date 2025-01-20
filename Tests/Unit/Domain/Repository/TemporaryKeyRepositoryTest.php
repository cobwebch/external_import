<?php

declare(strict_types = 1);

namespace Cobweb\ExternalImport\Tests\Unit\Domain\Repository;

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

use Cobweb\ExternalImport\Domain\Repository\TemporaryKeyRepository;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test suite for the TemporaryKeyRepository class
 */
class TemporaryKeyRepositoryTest extends UnitTestCase
{
    protected TemporaryKeyRepository $subject;

    public function setUp(): void
    {
        parent::setUp();
        $this->subject = GeneralUtility::makeInstance(TemporaryKeyRepository::class);
        $this->subject->setTestMode(true);
    }

    #[Test]
    public function hasTemporaryKeyInitiallyReturnsFalse(): void
    {
        self::assertFalse(
            $this->subject->hasTemporaryKey('foo', 'bar')
        );
    }

    #[Test]
    public function hasTemporaryKeyReturnsTrueIfKeyExists(): void
    {
        $this->subject->addTemporaryKey(
            'foo',
            $this->subject->generateTemporaryKey(),
            'bar'
        );
        self::assertTrue(
            $this->subject->hasTemporaryKey('foo', 'bar')
        );
    }

    #[Test]
    public function getTemporaryKeysInitiallyReturnsEmptyArray(): void
    {
        self::assertSame(
            [],
            $this->subject->getTemporaryKeys()
        );
    }

    #[Test]
    public function getTemporaryKeysReturnsArrayOfKeys(): void
    {
        $this->subject->resetForcedTemporaryKeySerial();
        $this->subject->addTemporaryKey(
            1,
            $this->subject->generateTemporaryKey(),
            'foo'
        );
        $this->subject->addTemporaryKey(
            2,
            $this->subject->generateTemporaryKey(),
            'bar'
        );
        self::assertSame(
            [
                'foo' => [
                    1 => 'NEW1',
                ],
                'bar' => [
                    2 => 'NEW2',
                ],
            ],
            $this->subject->getTemporaryKeys()
        );
    }

    #[Test]
    public function generateTemporaryKeyGeneratesSequenceInTestMode(): void
    {
        $this->subject->resetForcedTemporaryKeySerial();
        self::assertEquals(
            'NEW1',
            $this->subject->generateTemporaryKey()
        );
        self::assertEquals(
            'NEW2',
            $this->subject->generateTemporaryKey()
        );
    }

    #[Test]
    public function getTemporaryKeyForValueInitiallyReturnsNull(): void
    {
        self::assertNull(
            $this->subject->getTemporaryKeyForValue('foo', 'bar')
        );
    }

    #[Test]
    public function getTemporaryKeyForValueReturnsExpectedKeyForValueAndTable(): void
    {
        $this->subject->resetForcedTemporaryKeySerial();
        $this->subject->addTemporaryKey(
            1,
            $this->subject->generateTemporaryKey(),
            'foo'
        );
        $this->subject->addTemporaryKey(
            2,
            $this->subject->generateTemporaryKey(),
            'bar'
        );
        self::assertEquals(
            'NEW1',
            $this->subject->getTemporaryKeyForValue(1, 'foo')
        );
        self::assertEquals(
            'NEW2',
            $this->subject->getTemporaryKeyForValue(2, 'bar')
        );
    }
}
