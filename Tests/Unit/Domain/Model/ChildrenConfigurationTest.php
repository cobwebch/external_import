<?php

declare(strict_types=1);

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

namespace Cobweb\ExternalImport\Tests\Unit\Domain\Model;

use Cobweb\ExternalImport\Domain\Model\ChildrenConfiguration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ChildrenConfigurationTest extends UnitTestCase
{
    protected ChildrenConfiguration $subject;

    public function setUp(): void
    {
        parent::setUp();
        $this->subject = GeneralUtility::makeInstance(ChildrenConfiguration::class);
    }

    #[Test]
    public function getBaseDataInitiallyReturnsEmptyArray(): void
    {
        self::assertSame(
            [],
            $this->subject->getBaseData()
        );
    }

    #[Test]
    public function setBaseDataSetsDataArray(): void
    {
        $this->subject->setBaseData(['table' => 'foo']);
        self::assertSame(
            ['table' => 'foo'],
            $this->subject->getBaseData()
        );
    }

    public static function propertyProvider(): array
    {
        return [
            'empty base data' => [
                'baseData' => [],
                'property' => 'table',
                'result' => null,
            ],
            'matching base data' => [
                'baseData' => [
                    'table' => 'foo',
                ],
                'property' => 'table',
                'result' => 'foo',
            ],
            'non-matching base data' => [
                'baseData' => [
                    'table' => 'foo',
                ],
                'property' => 'bar',
                'result' => null,
            ],
        ];
    }

    #[Test] #[DataProvider('propertyProvider')]
    public function getBaseDataPropertyReturnsValueOrNull(array $baseData, string $property, mixed $result): void
    {
        $this->subject->setBaseData($baseData);
        self::assertSame(
            $result,
            $this->subject->getBaseDataProperty($property)
        );
    }

    #[Test]
    public function getControlColumnsForDeleteInitiallyReturnsEmptyArray(): void
    {
        self::assertSame(
            [],
            $this->subject->getControlColumnsForDelete()
        );
    }

    #[Test]
    public function setControlColumnsForDeleteSetsArray(): void
    {
        $this->subject->setControlColumnsForDelete(['foo', 'bar']);
        self::assertSame(
            ['foo', 'bar'],
            $this->subject->getControlColumnsForDelete()
        );
    }

    #[Test]
    public function getControlColumnsForUpdateInitiallyReturnsEmptyArray(): void
    {
        self::assertSame(
            [],
            $this->subject->getControlColumnsForUpdate()
        );
    }

    #[Test]
    public function setControlColumnsForUpdateSetsArray(): void
    {
        $this->subject->setControlColumnsForUpdate(['foo', 'bar']);
        self::assertSame(
            ['foo', 'bar'],
            $this->subject->getControlColumnsForUpdate()
        );
    }

    #[Test]
    public function isDeleteAllowedInitiallyReturnsTrue(): void
    {
        self::assertTrue(
            $this->subject->isDeleteAllowed()
        );
    }

    #[Test]
    public function setDeleteAllowedInitiallySetsBoolean(): void
    {
        $this->subject->setDeleteAllowed(false);
        self::assertFalse(
            $this->subject->isDeleteAllowed()
        );
    }

    #[Test]
    public function isInsertAllowedInitiallyReturnsTrue(): void
    {
        self::assertTrue(
            $this->subject->isInsertAllowed()
        );
    }

    #[Test]
    public function setInsertAllowedInitiallySetsBoolean(): void
    {
        $this->subject->setInsertAllowed(false);
        self::assertFalse(
            $this->subject->isInsertAllowed()
        );
    }

    #[Test]
    public function isUpdateAllowedInitiallyReturnsTrue(): void
    {
        self::assertTrue(
            $this->subject->isUpdateAllowed()
        );
    }

    #[Test]
    public function setUpdateAllowedInitiallySetsBoolean(): void
    {
        $this->subject->setUpdateAllowed(false);
        self::assertFalse(
            $this->subject->isUpdateAllowed()
        );
    }

    public static function allowedOperationsProvider(): array
    {
        return [
            'set delete to true' => [
                'operation' => 'delete',
                'flag' => true,
            ],
            'set insert to true' => [
                'operation' => 'insert',
                'flag' => true,
            ],
            'set update to true' => [
                'operation' => 'update',
                'flag' => true,
            ],
        ];
    }

    #[Test] #[DataProvider('allowedOperationsProvider')]
    public function setAllowedOperationSetExpectedFlag(string $operation, bool $flag): void
    {
        $this->subject->setAllowedOperation($operation, $flag);
        switch ($operation) {
            case 'delete':
                $value = $this->subject->isDeleteAllowed();
                break;
            case 'insert':
                $value = $this->subject->isInsertAllowed();
                break;
            case 'update':
                $value = $this->subject->isUpdateAllowed();
                break;
        }
        self::assertEquals(
            $flag,
            $value
        );
    }

    #[Test]
    public function getSortingInitiallyReturnsEmptyArray(): void
    {
        self::assertSame(
            [],
            $this->subject->getSorting()
        );
    }

    #[Test]
    public function setSortingSetsArray(): void
    {
        $this->subject->setSorting(
            [
                'source' => 'foo',
                'target' => 'bar',
            ]
        );
        self::assertSame(
            [
                'source' => 'foo',
                'target' => 'bar',
            ],
            $this->subject->getSorting()
        );
    }
}
