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

use Cobweb\ExternalImport\Domain\Repository\SchedulerRepository;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Domain\Repository\SchedulerTaskRepository;
use TYPO3\CMS\Scheduler\Task\TaskSerializer;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test class for SchedulerRepository
 */
class SchedulerRepositoryTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'scheduler',
    ];

    protected SchedulerRepository $subject;

    public function setUp(): void
    {
        parent::setUp();
        $this->subject = new SchedulerRepository(
            new SchedulerTaskRepository(
                new TaskSerializer()
            ),
            new TaskSerializer()
        );
    }

    #[Test]
    public function fetchAllGroupsReturnsAllExistingGroups(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Scheduler.csv');
        $groups = $this->subject->fetchAllGroups();
        self::assertSame(
            [
                0 => '',
                5 => 'Group 0',
                1 => 'Group 1',
                3 => 'Group 3',
            ],
            $groups
        );
    }
}
