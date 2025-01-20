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
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test suite for the ProcessConnectorParametersEvent class
 */
class ProcessConnectorParametersEventTest extends UnitTestCase
{
    protected ProcessConnectorParametersEvent $subject;
    protected Configuration $configuration;

    public function setUp(): void
    {
        parent::setUp();
        $this->configuration = new Configuration();
        $this->subject = new ProcessConnectorParametersEvent(
            [],
            $this->configuration
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
    public function setParametersSetsParameters(): void
    {
        $parameters = ['foo' => 'bar'];
        $this->subject->setParameters($parameters);
        self::assertSame(
            $parameters,
            $this->subject->getParameters()
        );
    }

    #[Test]
    public function getExternalConfigurationInitiallyReturnsOriginalConfiguration(): void
    {
        self::assertSame(
            $this->configuration,
            $this->subject->getExternalConfiguration()
        );
    }
}
