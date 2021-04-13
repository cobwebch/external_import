<?php

declare(strict_types=1);

namespace Cobweb\ExternalImport\Domain\Repository;

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

use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class for managing the generation and storage of temporary keys
 *
 * @package Cobweb\ExternalImport\Domain\Repository
 */
class TemporaryKeyRepository
{

    /**
     * @var int Incremental number to be used for temporary keys during test mode (used for unit testing)
     */
    protected static $forcedTemporaryKeySerial = 0;

    /**
     * @var array List of temporary keys created on the fly for new records
     */
    protected $temporaryKeys = [];

    /**
     * @var Random
     */
    protected $randomGenerator;

    /**
     * @var bool Set to true to trigger testing mode (used only for unit testing)
     */
    protected $testMode = false;

    public function __construct()
    {
        $this->randomGenerator = GeneralUtility::makeInstance(Random::class);
    }

    /**
     * Resets the forced serial sequence.
     *
     * NOTE: don't use in your own code. This is used only for testing.
     */
    public function resetForcedTemporaryKeySerial(): void
    {
        self::$forcedTemporaryKeySerial = 0;
    }

    /**
     * Generates a random key and returns it.
     *
     * The keys are used for new records in the TCE structures used for storing new records.
     * A random key is recommended. Controlled keys are generated in test mode in order
     * to have predictable results for functional testing.
     *
     * @return string
     */
    public function generateTemporaryKey(): string
    {
        if ($this->isTestMode()) {
            self::$forcedTemporaryKeySerial++;
            return 'NEW' . self::$forcedTemporaryKeySerial;
        }
        return 'NEW' . $this->randomGenerator->generateRandomHexString(20);
    }

    /**
     * Returns the list of all temporary keys.
     *
     * @return array
     */
    public function getTemporaryKeys(): array
    {
        return $this->temporaryKeys;
    }

    /**
     * Checks whether a temporary key exists for the given value.
     *
     * @param mixed $value Value for which we want to find a key
     * @param string $table Name of the table for which the key is used
     * @return bool
     */
    public function hasTemporaryKey($value, string $table): bool
    {
        return isset($this->temporaryKeys[$table][$value]);
    }

    /**
     * Gets the temporary key for the given value.
     *
     * @param mixed $value Value for which we want to find a key
     * @param string $table Name of the table for which the key is used
     * @return string
     */
    public function getTemporaryKeyForValue($value, string $table): ?string
    {
        if (isset($this->temporaryKeys[$table][$value])) {
            return $this->temporaryKeys[$table][$value];
        }
        return null;
    }

    /**
     * Adds a temporary key for the given value.
     *
     * @param mixed $value Value for which we want to add the key
     * @param string $key Value of the key
     * @param string $table Name of the table for which the key is used
     */
    public function addTemporaryKey($value, string $key, string $table): void
    {
        if (!isset($this->temporaryKeys[$table])) {
            $this->temporaryKeys[$table] = [];
        }
        $this->temporaryKeys[$table][$value] = $key;
    }

    /**
     * Sets the test mode flag.
     *
     * Don't use this unless you are really sure that it is what you want.
     * This is meant for unit testing only.
     *
     * @param bool $mode Set to true for test mode
     * @return void
     */
    public function setTestMode(bool $mode): void
    {
        $this->testMode = $mode;
    }

    /**
     * Returns the value of the test mode flag.
     *
     * @return bool
     */
    public function isTestMode(): bool
    {
        return $this->testMode;
    }

}