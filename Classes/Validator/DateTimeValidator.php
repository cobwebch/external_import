<?php
namespace Cobweb\ExternalImport\Validator;

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

use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

/**
 * Validator for a date and time.
 *
 * @package Cobweb\ExternalImport\Validator
 */
class DateTimeValidator extends AbstractValidator
{
    /**
     * Validates the date and time, which is expected to be a Unix timestamp.
     *
     * @param int $timestamp The timestamp to validate
     * @return bool
     */
    public function isValid($timestamp)
    {
        $timestamp = (int)$timestamp;
        try {
            $date = new \DateTime('@' . $timestamp);
            if ($date === false) {
                $this->addError(
                        LocalizationUtility::translate(
                                'error_invalid_start_date',
                                'external_import'
                        ),
                        1463495807
                );
                return false;
            } else {
                return true;
            }
        }
        catch (\Exception $e) {
            $this->addError(
                    LocalizationUtility::translate(
                            'error_invalid_start_date',
                            'external_import'
                    ),
                    1463495807
            );
            return false;
        }
    }
}