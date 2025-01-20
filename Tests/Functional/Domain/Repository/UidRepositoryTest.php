<?php

declare(strict_types=1);

namespace Cobweb\ExternalImport\Tests\Domain\Repository;

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
use Cobweb\ExternalImport\Domain\Repository\UidRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test class for the UidRepository
 */
class UidRepositoryTest extends FunctionalTestCase
{
    protected UidRepository $subject;

    public function setUp(): void
    {
        parent::setUp();
        $this->subject = new UidRepository();
    }

    public static function configurationDataProvider(): array
    {
        return [
            'No special configuration, no pid restriction' => [
                'configuration' => [
                    'referenceUid' => 'tstamp',
                ],
                'listOfUids' => [
                    1520788063 => 2,
                    1520788087 => 3,
                ],
                'listOfPids' => [
                    1520788063 => 1,
                    1520788087 => 2,
                ],
            ],
            'Pid restriction true' => [
                'configuration' => [
                    'referenceUid' => 'tstamp',
                    'enforcePid' => true,
                ],
                'listOfUids' => [
                    1520788063 => 2,
                ],
                'listOfPids' => [
                    1520788063 => 1,
                ],
            ],
            'Pid restriction true-ish' => [
                'configuration' => [
                    'referenceUid' => 'tstamp',
                    'enforcePid' => 1,
                ],
                'listOfUids' => [
                    1520788063 => 2,
                ],
                'listOfPids' => [
                    1520788063 => 1,
                ],
            ],
            'Pid restriction other than true' => [
                'configuration' => [
                    'referenceUid' => 'tstamp',
                    'enforcePid' => false,
                ],
                'listOfUids' => [
                    1520788063 => 2,
                    1520788087 => 3,
                ],
                'listOfPids' => [
                    1520788063 => 1,
                    1520788087 => 2,
                ],
            ],
            'Where clause' => [
                'configuration' => [
                    'referenceUid' => 'tstamp',
                    'whereClause' => 'header like \'%deleted%\'',
                ],
                'listOfUids' => [
                    1520788087 => 3,
                ],
                'listOfPids' => [
                    1520788087 => 2,
                ],
            ],
        ];
    }

    #[Test] #[DataProvider('configurationDataProvider')]
    public function getExistingUidsTriggersFetchingOfUidsAndPids(array $configuration, array $listOfUids, array $listOfPids): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/UidRepository.csv');
        $configurationObject = GeneralUtility::makeInstance(Configuration::class);
        $configurationObject->setTable('tt_content');
        $configurationObject->setGeneralConfiguration($configuration);
        if ($configuration['enforcePid'] ?? false) {
            $configurationObject->setStoragePid(1);
        }
        $this->subject->setConfiguration($configurationObject);
        self::assertSame(
            $listOfUids,
            $this->subject->getExistingUids()
        );
        self::assertSame(
            $listOfPids,
            $this->subject->getCurrentPids()
        );
    }

    /**
     * @test
     */
    public function getExistingUidsWithoutConfigurationThrowsException(): void
    {
        $this->expectException(\Cobweb\ExternalImport\Exception\MissingConfigurationException::class);
        $this->subject->getExistingUids();
    }
}
