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

use Cobweb\ExternalImport\Domain\Model\Dto\QueryParameters;
use Cobweb\ExternalImport\Domain\Repository\LogRepository;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test case for the LogRepository class.
 */
class LogRepositoryTest extends FunctionalTestCase
{
    protected $testExtensionsToLoad = [
        'typo3conf/ext/svconnector',
        'typo3conf/ext/external_import',
    ];

    /**
     * @var LogRepository
     */
    protected $subject;

    /**
     * @var QueryParameters
     */
    protected $queryParameters;

    public function setUp(): void
    {
        parent::setUp();
        try {
            $this->subject = GeneralUtility::makeInstance(LogRepository::class);
            $this->queryParameters = GeneralUtility::makeInstance(QueryParameters::class);
            $this->importDataSet(__DIR__ . '/../../Fixtures/Logs.xml');
        } catch (\Exception $e) {
            self::markTestSkipped(
                sprintf(
                    'Some initializations could not be performed (Exception: %s [%d])',
                    $e->getMessage(),
                    $e->getCode()
                )
            );
        }
    }

    public function queryDataProvider(): array
    {
        $searchColumns = [
            0 => [
                'searchable' => 'true',
                'name' => 'configuration',
            ],
            1 => [
                'searchable' => 'true',
                'name' => 'message',
            ],
            2 => [
                'searchable' => 'true',
                'name' => 'context',
            ],
            3 => [
                'searchable' => 'false',
                'name' => 'crdate',
            ],
        ];
        $order = [
            0 => [
                'column' => 3,
                'dir' => 'desc',
            ],
        ];
        return [
            'No search, no limit' => [
                [
                    'search' => [
                        'value' => '',
                    ],
                    'columns' => $searchColumns,
                    'order' => $order,
                ],
                4,
                4,
            ],
            'No search, limit 2' => [
                [
                    'search' => [
                        'value' => '',
                    ],
                    'columns' => $searchColumns,
                    'length' => 2,
                    'order' => $order,
                ],
                4,
                2,
            ],
            'Search for "cli"' => [
                [
                    'search' => [
                        'value' => 'cli',
                    ],
                    'columns' => $searchColumns,
                    'order' => $order,
                ],
                3,
                3,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     * @param array $parameters
     * @param int $fullCount The total number of records
     * @param int $filteredCount The filtered number of records
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     */
    public function countBySearchReturnsExpectedNumberOfRecords(
        array $parameters,
        int $fullCount,
        int $filteredCount
    ): void {
        $this->queryParameters->setAllParameters($parameters);
        $count = $this->subject->countBySearch($this->queryParameters);
        // Compare with the full count, as limit is not applied to the count query
        self::assertEquals(
            $fullCount,
            $count
        );
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     * @param array $parameters
     * @param int $fullCount The total number of records
     * @param int $filteredCount The filtered number of records
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     */
    public function findBySearchReturnsExpectedRecords(array $parameters, int $fullCount, int $filteredCount): void
    {
        $this->queryParameters->setAllParameters($parameters);
        $records = $this->subject->findBySearch($this->queryParameters);
        self::assertEquals(
            $filteredCount,
            $records->count()
        );
        // To go one step further, test ordering (which should be by descending creation date)
        // by accessing the first record and checking the creation timestamp
        /** @var \Cobweb\ExternalImport\Domain\Model\Log $firstRecord */
        $firstRecord = $records->getFirst();
        self::assertEquals(
            1529789282,
            $firstRecord->getCrdate()->getTimestamp()
        );
    }
}
