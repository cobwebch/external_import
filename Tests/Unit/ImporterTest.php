<?php

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
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test case for the External Import importer.
 *
 * @author Francois Suter <typo3@cobweb.ch>
 * @package TYPO3
 * @subpackage tx_externalimport
 */
class ImporterTest extends UnitTestCase
{

    /**
     * @var Importer
     */
    protected $subject;

    protected function setUp()
    {
        // For unit testing, don't inject all dependencies
        $this->subject = GeneralUtility::makeInstance(
                Importer::class,
                $this->getAccessibleMock(
                    ConfigurationRepository::class,
                    [],
                    [],
                    '',
                    // Don't call the constructor to avoid loading the extension configuration,
                    // which doesn't exist in unit tests setup
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
                $this->getAccessibleMock(TemporaryKeyRepository::class)
        );
    }
}