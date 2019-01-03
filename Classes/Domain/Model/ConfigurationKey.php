<?php
declare(strict_types=1);

namespace Cobweb\ExternalImport\Domain\Model;

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

/**
 * Utility model class encapsulating the encoding/decoding of External Import configuration keys.
 *
 * An External Import configuration key is created by concatenating a table name with a configuration index.
 * The string separating the two must be particular so that it cannot be expected to happen in a table name
 * or in an index, otherwise the reverse operation (splitting the configuration key to extract table and index)
 * will fail.
 *
 * Not an Extbase model.
 *
 * @package Cobweb\ExternalImport\Domain\Model
 */
class ConfigurationKey
{
    const CONCATENATOR = '***';

    /**
     * @var string External Import configuration key
     */
    protected $configurationKey = '';

    /**
     * @var string Name of the table
     */
    protected $table = '';

    /**
     * @var string Configuration index
     */
    protected $index = '';

    /**
     * Returns the configuration key.
     *
     * @return string
     */
    public function getConfigurationKey(): string
    {
        return $this->configurationKey;
    }

    /**
     * Sets the configuration key, the table and the index.
     *
     * @param string $configurationKey
     */
    public function setConfigurationKey(string $configurationKey)
    {
        $this->configurationKey = $configurationKey;
        list($this->table, $this->index) = explode(self::CONCATENATOR, $configurationKey);
    }

    /**
     * Sets the table, the index and the configuration key.
     *
     * @param string $table
     * @param string $index
     */
    public function setTableAndIndex(string $table, string $index)
    {
        $this->table = $table;
        $this->index = $index;
        $this->configurationKey = $table . self::CONCATENATOR . $index;
    }

    /**
     * Returns the table name.
     *
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Returns the index.
     *
     * @return string
     */
    public function getIndex(): string
    {
        return $this->index;
    }
}