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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case for the LogRepository class.
 */
class LogRepositoryTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'scheduler',
    ];

    protected array $testExtensionsToLoad = [
        'cobweb/svconnector',
        'cobweb/external_import',
    ];

    protected LogRepository $subject;
    protected QueryParameters $queryParameters;

    public function setUp(): void
    {
        parent::setUp();
        try {
            $this->subject = new LogRepository();
            $this->queryParameters = new QueryParameters();
            $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Logs.csv');
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

    public static function queryDataProvider(): array
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
                'parameters' => [
                    'search' => [
                        'value' => '',
                    ],
                    'columns' => $searchColumns,
                    'order' => $order,
                ],
                'fullCount' => 4,
                'filteredCount' => 4,
            ],
            'No search, limit 2' => [
                'parameters' => [
                    'search' => [
                        'value' => '',
                    ],
                    'columns' => $searchColumns,
                    'length' => 2,
                    'order' => $order,
                ],
                'fullCount' => 4,
                'filteredCount' => 2,
            ],
            'Search for "cli"' => [
                'parameters' => [
                    'search' => [
                        'value' => 'cli',
                    ],
                    'columns' => $searchColumns,
                    'order' => $order,
                ],
                'fullCount' => 3,
                'filteredCount' => 3,
            ],
        ];
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    #[Test] #[DataProvider('queryDataProvider')]
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
     * @throws \Doctrine\DBAL\Exception
     */
    #[Test] #[DataProvider('queryDataProvider')]
    public function findBySearchReturnsExpectedRecords(array $parameters, int $fullCount, int $filteredCount): void
    {
        $this->queryParameters->setAllParameters($parameters);
        $records = $this->subject->findBySearch($this->queryParameters);
        self::assertCount(
            $filteredCount,
            $records
        );
        // To go one step further, test ordering (which should be by descending creation date)
        // by accessing the first record and checking the creation timestamp
        self::assertEquals(
            1529789282,
            $records[0]['crdate']
        );
    }
}
