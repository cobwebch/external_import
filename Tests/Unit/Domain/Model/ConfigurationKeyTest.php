<?php

declare(strict_types=1);

namespace Cobweb\ExternalImport\Tests\Unit\Domain\Model;

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

use Cobweb\ExternalImport\Domain\Model\ConfigurationKey;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test suite for the ConfigurationKey model
 */
class ConfigurationKeyTest extends UnitTestCase
{
    protected ConfigurationKey $subject;

    public function setUp(): void
    {
        parent::setUp();
        $this->subject = new ConfigurationKey();
    }

    #[Test]
    public function getConfigurationKeyInitiallyReturnsEmptyString(): void
    {
        self::assertEquals('', $this->subject->getConfigurationKey());
    }

    #[Test]
    public function getIndexInitiallyReturnsEmptyString(): void
    {
        self::assertEquals('', $this->subject->getIndex());
    }

    #[Test]
    public function getTableInitiallyReturnsEmptyString(): void
    {
        self::assertEquals('', $this->subject->getTable());
    }

    public static function configurationProvider(): array
    {
        return [
            'standard table name, numerical index' => [
                'table' => 'tx_foo',
                'index' => 1,
                'key' => 'tx_foo***1',
            ],
            'standard table name, numerical index with value 0' => [
                'table' => 'tx_foo',
                'index' => 0,
                'key' => 'tx_foo***0',
            ],
            'standard table name, string index' => [
                'table' => 'tx_foo',
                'index' => 'bar',
                'key' => 'tx_foo***bar',
            ],
            'table name with hyphen, string index' => [
                'table' => 'tx-foo',
                'index' => 'bar',
                'key' => 'tx-foo***bar',
            ],
            'group name' => [
                'table' => 'group:foo',
                'index' => '',
                'key' => 'group:foo',
            ],
            'all tables' => [
                'table' => 'all',
                'index' => '',
                'key' => 'all',
            ],
        ];
    }

    #[Test] #[DataProvider('configurationProvider')]
    public function setConfigurationKeySetsTableAndIndex(string $table, $index, string $key): void
    {
        $this->subject->setConfigurationKey($key);
        self::assertEquals($table, $this->subject->getTable());
        self::assertEquals($index, $this->subject->getIndex());
    }

    #[Test] #[DataProvider('configurationProvider')]
    public function setTableAndIndexSetsConfigurationKey(string $table, $index, string $key): void
    {
        $this->subject->setTableAndIndex($table, (string)$index);
        self::assertEquals($key, $this->subject->getConfigurationKey());
    }
}
