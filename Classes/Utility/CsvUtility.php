<?php

declare(strict_types=1);

namespace Cobweb\ExternalImport\Utility;

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
 * Utility class to turn an array into a CSV-formatted string, building on what exists in the Core.
 */
class CsvUtility
{
    /**
     * Write the incoming array to CSV-formatted string, with the array keys in the first row
     * @param array $array
     * @return string
     */
    public function prepareCsvData(array $array): string
    {
        $array = $this->ensureCompleteStructure($array);
        $lines = [];
        foreach ($array as $index => $item) {
            // Header row with array keys
            if ($index === 0) {
                $keys = array_keys($item);
                $lines[] = \TYPO3\CMS\Core\Utility\CsvUtility::csvValues($keys, ';');
            }
            $lines[] = \TYPO3\CMS\Core\Utility\CsvUtility::csvValues($item, ';');
        }
        return implode("\n", $lines);
    }

    /**
     * Ensure that each row of the incoming array comes out with a complete set of fields
     *
     * @param array $array
     * @return array
     */
    public function ensureCompleteStructure(array $array): array
    {
        $headerRow = [];
        // Loop on all rows to ensure that we have all possible array keys, since some rows
        // may miss various keys
        foreach ($array as $item) {
            $arrayKeys = array_keys($item);
            // Check which items may be missing from the list of columns
            foreach ($arrayKeys as $index => $key) {
                if (!in_array($key, $headerRow, true)) {
                    array_splice($headerRow, $index, 0, $key);
                }
            }
        }
        // Loop again on all items and fill any missing "cells"
        $restructuredRecords = [];
        foreach ($array as $item) {
            $record = [];
            foreach ($headerRow as $key) {
                if (array_key_exists($key, $item)) {
                    $record[$key] = $item[$key];
                } else {
                    $record[$key] = '';
                }
            }
            $restructuredRecords[] = $record;
        }
        return $restructuredRecords;
    }
}
