<?php

declare(strict_types=1);

namespace Cobweb\ExternalImport\Transformation;

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

use Cobweb\ExternalImport\ImporterAwareInterface;
use Cobweb\ExternalImport\ImporterAwareTrait;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Example transformation functions for the 'external_import' extension
 *
 * @package Cobweb\ExternalImport\Transformation
 */
class DateTimeTransformation implements SingletonInterface, ImporterAwareInterface
{
    use ImporterAwareTrait;

    /**
     * This an example method to be called by external import for transforming data
     * It receives the full record and the index of the field it is expected to transform
     * It also receives any additional parameters that might have been set in the TCA
     * It is expected to return the transformed field only
     *
     * In particular, this method will parse a date field using strtotime() to extract a timestamp
     * and will return a formatted string using either date() or strftime() and a format parameter
     * or simply the Unix timestamp if no formatting function was defined.
     *
     * @param array $record The full record that is being transformed
     * @param string $index The index of the field to transform
     * @param array $params Additional parameters from the TCA
     * @return mixed Timestamp or formatted date string
     */
    public function parseDate(array $record, string $index, array $params)
    {
        // Keep null as output value
        if (($record[$index] ?? null) === null) {
            return null;
        }

        $value = strtotime((string)$record[$index]);
        // Consider time zone offset
        // This is necessary because TCEmain will subtract the time zone offset upon saving the data,
        // so the offset must be added first to compensate for this
        // NOTE: this correction was made dependent on a parameter (enforceTimeZone), but it might
        // make sense to execute it all the time, given the context in which this method is called
        if (!empty($params['enforceTimeZone'] ?? null) && !empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['serverTimeZone'] ?? null)) {
            $value += ($GLOBALS['TYPO3_CONF_VARS']['SYS']['serverTimeZone'] * 3600);
        }
        // Format value only if a function was defined
        if (isset($params['function'])) {
            // Use strftime for formatting
            if ($params['function'] === 'strftime') {
                $value = strftime($params['format'], $value);
                // Otherwise use date
            } else {
                $value = date($params['format'], $value);
            }
        }
        return $value;
    }
}
