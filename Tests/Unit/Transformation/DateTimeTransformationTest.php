<?php

declare(strict_types=1);

namespace Cobweb\ExternalImport\Tests\Unit\Transformation;

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

use Cobweb\ExternalImport\Transformation\DateTimeTransformation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test suite for the DateTimeTransformation class
 */
class DateTimeTransformationTest extends UnitTestCase
{
    protected DateTimeTransformation $subject;

    public function setUp(): void
    {
        parent::setUp();
        $this->subject = new DateTimeTransformation();
    }

    #[Test]
    public function parseDateReturnsNullForNullValue(): void
    {
        self::assertNull(
            $this->subject->parseDate(['date' => null], 'date', [])
        );
    }

    #[Test]
    public function parseDateReturnsTimestampWithoutFunction(): void
    {
        self::assertSame(
            strtotime('2026-10-26 12:00:00'),
            $this->subject->parseDate(['date' => '2026-10-26 12:00:00'], 'date', [])
        );
    }

    #[Test]
    public function parseDateReturnsFormattedDateWithDateFunction(): void
    {
        self::assertSame(
            '26.10.2026',
            $this->subject->parseDate(['date' => '2026-10-26 12:00:00'], 'date', ['function' => 'date', 'format' => 'd.m.Y'])
        );
    }

    public static function invalidDateProvider(): array
    {
        return [
            'without function' => [
                'parameters' => [],
            ],
            'with date function' => [
                'parameters' => [
                    'function' => 'date',
                    'format' => 'd.m.Y',
                ],
            ],
        ];
    }

    #[Test] #[DataProvider('invalidDateProvider')]
    public function parseDateThrowsExceptionForInvalidDate(array $parameters): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->subject->parseDate(['date' => 'not a date'], 'date', $parameters);
    }
}
