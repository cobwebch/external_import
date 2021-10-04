<?php

declare(strict_types=1);

namespace Cobweb\ExternalImport\Tests\Unit\Event;

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

use Cobweb\ExternalImport\Domain\Model\Configuration;
use Cobweb\ExternalImport\Event\ProcessConnectorParametersEvent;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * Test suite for the ProcessConnectorParametersEvent class
 *
 * @package Cobweb\ExternalImport\Tests\Unit\Event
 */
class ProcessConnectorParametersEventTest extends UnitTestCase
{
    /**
     * @var ProcessConnectorParametersEvent
     */
    protected $subject;

    /**
     * @var Configuration
     */
    protected $configuration;

    public function setUp(): void
    {
        parent::setUp();
        $this->configuration = new Configuration();
        $this->subject = new ProcessConnectorParametersEvent(
            [],
            $this->configuration
        );
    }

    /**
     * @test
     */
    public function getParametersInitiallyReturnsEmptyArray() :void
    {
        self::assertSame(
            [],
            $this->subject->getParameters()
        );
    }

    /**
     * @test
     */
    public function setParametersSetsParameters(): void
    {
        $parameters = ['foo' => 'bar'];
        $this->subject->setParameters($parameters);
        self::assertSame(
            $parameters,
            $this->subject->getParameters()
        );
    }

    /**
     * @test
     */
    public function getExternalConfigurationInitiallyReturnsOriginalConfiguration(): void
    {
        self::assertSame(
            $this->configuration,
            $this->subject->getExternalConfiguration()
        );
    }
}