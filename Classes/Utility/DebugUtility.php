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
 * Class with some utilities for dumping an array as a HTML table.
 *
 * @package Cobweb\ExternalImport\Utility
 */
class DebugUtility
{
    /**
     * Dumps a PHP array to a HTML table.
     *
     * @param array $array Array to display
     * @return string HTML table assembled from array
     */
    public static function dumpArray(array $array): string
    {
        $table = '<table>';
        foreach ($array as $key => $value) {
            $table .= '<tr>';
            $table .= '<td>' . htmlspecialchars((string)$key) . '</td>';
            $table .= '<td>';
            if (is_array($value)) {
                $table .= self::dumpArray($value);
            } else {
                $table .= htmlspecialchars((string)$value);
            }
            $table .= '</td>';
            $table .= '</tr>';
        }
        $table .= '</table>';
        return $table;
    }

    /**
     * Dumps two related PHP arrays to a HTML table.
     *
     * This is similar to dumpArray(), but using two arrays with the same keys.
     * Data from the second array is written alongside data from the first array,
     * with some highlighting in the markup.
     *
     * @param array $referenceArray Array to display
     * @param array $copyArray Related array
     * @return string HTML table assembled from array
     */
    public static function dumpTwinArrays(array $referenceArray, array $copyArray): string
    {
        $table = '<table>';
        foreach ($referenceArray as $key => $value) {
            $table .= '<tr>';
            $table .= '<td>' . htmlspecialchars((string)$key) . '</td>';
            $table .= '<td>';
            if (is_array($value)) {
                $table .= self::dumpTwinArrays(
                    $value,
                    array_key_exists($key, $copyArray) ? $copyArray[$key] : array()
                );
            } else {
                $cellContent = htmlspecialchars((string)$value);
                // Add related value, if it exists and only if it is different (avoid cluttering display)
                if (array_key_exists($key, $copyArray) && $copyArray[$key] != $value) {
                    $cellContent .= '<br><em>' . htmlspecialchars((string)$copyArray[$key]) . '</em>';
                }
                $table .= $cellContent;
            }
            $table .= '</td>';
            $table .= '</tr>';
        }
        $table .= '</table>';
        return $table;
    }

}