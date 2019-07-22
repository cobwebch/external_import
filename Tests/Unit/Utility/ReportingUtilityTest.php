<?php
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

use Cobweb\ExternalImport\Utility\ReportingUtility;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test suite for simple methods from the the reporting utility.
 *
 * @package Cobweb\ExternalImport\Tests\Unit\Utility
 */
class ReportingUtilityTest extends UnitTestCase
{
    /**
     * @var ReportingUtility
     */
    protected $subject;

    public function setUp()
    {
        parent::setUp();
        $this->subject = GeneralUtility::makeInstance(ReportingUtility::class);
    }

    /**
     * @test
     * @expectedException \Cobweb\ExternalImport\Exception\UnknownReportingKeyException
     */
    public function getValueForStepForUnknownKeyThrowsException(): void
    {
        $this->subject->getValueForStep('foo', 'bar');
    }

    /**
     * @test
     */
    public function getValueForStepReturnsExpectedValue(): void
    {
        $this->subject->setValueForStep(
                \Cobweb\ExternalImport\Step\StoreDataStep::class,
                'inserts',
                10
        );
        try {
            self::assertEquals(
                    10,
                    $this->subject->getValueForStep(
                            \Cobweb\ExternalImport\Step\StoreDataStep::class,
                            'inserts'
                    )
            );
        }
        catch (\Exception $e) {
            self::fail($e->getMessage());
        }
    }
}