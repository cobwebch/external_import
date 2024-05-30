<?php

declare(strict_types=1);

namespace Cobweb\ExternalImport\Tests\Functional\Utility;

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

use Cobweb\ExternalImport\Importer;
use Cobweb\ExternalImport\Utility\SlugUtility;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test class for the SlugUtility.
 */
class SlugUtilityTest extends FunctionalTestCase
{
    protected $testExtensionsToLoad = [
        'typo3conf/ext/svconnector',
        'typo3conf/ext/external_import',
        'typo3conf/ext/externalimport_test',
    ];

    /**
     * @var SlugUtility
     */
    protected $subject;

    public function setUp(): void
    {
        parent::setUp();
        $this->subject = GeneralUtility::makeInstance(
            SlugUtility::class,
            GeneralUtility::makeInstance(Importer::class)
        );
    }

    /**
     * @test
     */
    public function resolveSlugFieldNamesFindsListOfSlugFields(): void
    {
        self::assertSame(
            ['path_segment'],
            $this->subject->resolveSlugFieldNames('tx_externalimporttest_product')
        );
    }
}
