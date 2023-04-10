<?php

declare(strict_types=1);

namespace Cobweb\ExternalImport\Tests\Unit\Utility;

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

use Cobweb\ExternalImport\Domain\Repository\BackendUserRepository;
use Cobweb\ExternalImport\Domain\Repository\LogRepository;
use Cobweb\ExternalImport\Step\StoreDataStep;
use Cobweb\ExternalImport\Utility\ReportingUtility;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test suite for simple methods from the reporting utility.
 *
 * @package Cobweb\ExternalImport\Tests\Unit\Utility
 */
class ReportingUtilityTest extends UnitTestCase
{
    /**
     * @var ReportingUtility
     */
    protected $subject;

    public function setUp(): void
    {
        parent::setUp();
        $this->subject = GeneralUtility::makeInstance(
            ReportingUtility::class,
            $this->getAccessibleMock(
                LogRepository::class,
                [],
                [],
                '',
                // Don't call the original constructor to avoid a cascade of dependencies
                false
            ),
            $this->getAccessibleMock(Context::class),
            $this->getAccessibleMock(
                BackendUserRepository::class,
                [],
                [],
                '',
                // Don't call the original constructor to avoid a cascade of dependencies
                false
            )
        );
    }

    /**
     * @test
     */
    public function getValueForStepForUnknownKeyThrowsException(): void
    {
        $this->expectException(\Cobweb\ExternalImport\Exception\UnknownReportingKeyException::class);
        $this->subject->getValueForStep('foo', 'bar');
    }

    /**
     * @test
     */
    public function getValueForStepReturnsExpectedValue(): void
    {
        $this->subject->setValueForStep(
            StoreDataStep::class,
            'inserts',
            10
        );
        try {
            self::assertEquals(
                10,
                $this->subject->getValueForStep(
                    StoreDataStep::class,
                    'inserts'
                )
            );
        } catch (\Exception $e) {
            self::fail($e->getMessage());
        }
    }
}