<?php

declare(strict_types=1);

namespace Cobweb\ExternalImport\Tests\Unit\Step;

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
use Cobweb\ExternalImport\Step\CheckPermissionsStep;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Unit test suite for the Check Permissions Data step class. Actually this is mostly for testing
 * the base methods from the AbstractStep class.
 */
class CheckPermissionsStepTest extends UnitTestCase
{
    protected CheckPermissionsStep $subject;

    public function setUp(): void
    {
        parent::setUp();
        $this->subject = GeneralUtility::makeInstance(CheckPermissionsStep::class);
    }

    #[Test]
    public function getDataInitiallyReturnsNull(): void
    {
        self::assertNull(
            $this->subject->getData()
        );
    }

    #[Test]
    public function setDataSetsDataObject(): void
    {
        $data = new Data();
        $this->subject->setData($data);
        self::assertSame(
            $data,
            $this->subject->getData()
        );
    }

    #[Test]
    public function isAbortFlagInitiallyReturnsFalse(): void
    {
        self::assertFalse(
            $this->subject->isAbortFlag()
        );
    }

    #[Test]
    public function setAbortFlagSetsBooleanFlag(): void
    {
        $this->subject->setAbortFlag(true);
        self::assertTrue(
            $this->subject->isAbortFlag()
        );
    }

    #[Test]
    public function getParametersInitiallyReturnsEmptyArray(): void
    {
        self::assertSame(
            [],
            $this->subject->getParameters()
        );
    }

    #[Test]
    public function setParametersSetsArray(): void
    {
        $this->subject->setParameters(
            ['foo' => 'bar']
        );
        self::assertSame(
            ['foo' => 'bar'],
            $this->subject->getParameters()
        );
    }

    public static function parametersProvider(): array
    {
        return [
            'initially returns null - no matter what key' => [
                'parameters' => null,
                'key' => 'foo',
                'expected' => null,
            ],
            'returns expected value with valid key' => [
                'parameters' => ['foo' => 'bar'],
                'key' => 'foo',
                'expected' => 'bar',
            ],
            'returns null with invalid key' => [
                'parameters' => ['foo' => 'bar'],
                'key' => 'baz',
                'expected' => null,
            ],
        ];
    }

    #[Test] #[DataProvider('parametersProvider')]
    public function getParameterReturnsExpectedValueForKey($parameters, $key, $expected): void
    {
        if (is_array($parameters)) {
            $this->subject->setParameters($parameters);
        }
        self::assertSame(
            $expected,
            $this->subject->getParameter($key)
        );
    }
}
