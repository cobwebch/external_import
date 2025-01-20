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
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test class for the SlugUtility.
 */
class SlugUtilityTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'scheduler',
    ];

    protected array $testExtensionsToLoad = [
        'cobweb/svconnector',
        'cobweb/svconnector_csv',
        'cobweb/svconnector_feed',
        'cobweb/svconnector_json',
        'cobweb/external_import',
        'cobweb/externalimport_test',
    ];

    protected SlugUtility $subject;

    public function setUp(): void
    {
        parent::setUp();
        $this->subject = new SlugUtility(
            $this->getAccessibleMock(
                Importer::class,
                callOriginalConstructor: false
            ),
        );
    }

    #[Test]
    public function resolveSlugFieldNamesFindsListOfSlugFields(): void
    {
        self::assertSame(
            ['path_segment'],
            $this->subject->resolveSlugFieldNames('tx_externalimporttest_product')
        );
    }
}
