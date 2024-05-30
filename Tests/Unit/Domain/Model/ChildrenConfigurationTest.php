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
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ChildrenConfigurationTest extends UnitTestCase
{
    protected ChildrenConfiguration $subject;

    public function setUp(): void
    {
        parent::setUp();
        $this->subject = GeneralUtility::makeInstance(ChildrenConfiguration::class);
    }

    /**
     * @test
     */
    public function getBaseDataInitiallyReturnsEmptyArray(): void
    {
        self::assertSame(
            [],
            $this->subject->getBaseData()
        );
    }

    /**
     * @test
     */
    public function setBaseDataSetsDataArray(): void
    {
        $this->subject->setBaseData(['table' => 'foo']);
        self::assertSame(
            ['table' => 'foo'],
            $this->subject->getBaseData()
        );
    }

    public function propertyProvider(): array
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

    /**
     * @test
     * @dataProvider propertyProvider
     * @param array $baseData
     * @param string $property
     * @param $result
     */
    public function getBaseDataPropertyReturnsValueOrNull(array $baseData, string $property, $result): void
    {
        $this->subject->setBaseData($baseData);
        self::assertSame(
            $result,
            $this->subject->getBaseDataProperty($property)
        );
    }

    /**
     * @test
     */
    public function getControlColumnsForDeleteInitiallyReturnsEmptyArray(): void
    {
        self::assertSame(
            [],
            $this->subject->getControlColumnsForDelete()
        );
    }

    /**
     * @test
     */
    public function setControlColumnsForDeleteSetsArray(): void
    {
        $this->subject->setControlColumnsForDelete(['foo', 'bar']);
        self::assertSame(
            ['foo', 'bar'],
            $this->subject->getControlColumnsForDelete()
        );
    }

    /**
     * @test
     */
    public function getControlColumnsForUpdateInitiallyReturnsEmptyArray(): void
    {
        self::assertSame(
            [],
            $this->subject->getControlColumnsForUpdate()
        );
    }

    /**
     * @test
     */
    public function setControlColumnsForUpdateSetsArray(): void
    {
        $this->subject->setControlColumnsForUpdate(['foo', 'bar']);
        self::assertSame(
            ['foo', 'bar'],
            $this->subject->getControlColumnsForUpdate()
        );
    }

    /**
     * @test
     */
    public function isDeleteAllowedInitiallyReturnsTrue(): void
    {
        self::assertTrue(
            $this->subject->isDeleteAllowed()
        );
    }

    /**
     * @test
     */
    public function setDeleteAllowedInitiallySetsBoolean(): void
    {
        $this->subject->setDeleteAllowed(false);
        self::assertFalse(
            $this->subject->isDeleteAllowed()
        );
    }

    /**
     * @test
     */
    public function isInsertAllowedInitiallyReturnsTrue(): void
    {
        self::assertTrue(
            $this->subject->isInsertAllowed()
        );
    }

    /**
     * @test
     */
    public function setInsertAllowedInitiallySetsBoolean(): void
    {
        $this->subject->setInsertAllowed(false);
        self::assertFalse(
            $this->subject->isInsertAllowed()
        );
    }

    /**
     * @test
     */
    public function isUpdateAllowedInitiallyReturnsTrue(): void
    {
        self::assertTrue(
            $this->subject->isUpdateAllowed()
        );
    }

    /**
     * @test
     */
    public function setUpdateAllowedInitiallySetsBoolean(): void
    {
        $this->subject->setUpdateAllowed(false);
        self::assertFalse(
            $this->subject->isUpdateAllowed()
        );
    }

    public function allowedOperationsProvider(): array
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

    /**
     * @test
     * @dataProvider allowedOperationsProvider
     * @param string $operation
     * @param bool $flag
     */
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

    /**
     * @test
     */
    public function getSortingInitiallyReturnsEmptyArray(): void
    {
        self::assertSame(
            [],
            $this->subject->getSorting()
        );
    }

    /**
     * @test
     */
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
