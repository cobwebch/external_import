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
use TYPO3\CMS\Scheduler\CronCommand\NormalizeCommand;

/**
 * Validator for a scheduler frequency, which can be a simple number of seconds
 * or a cron format.
 *
 * @package Cobweb\ExternalImport\Validator
 */
class FrequencyValidator extends AbstractValidator
{
    /**
     * This validator always needs to be executed even if the given value is empty.
     * See AbstractValidator::validate()
     *
     * @var bool
     */
    protected $acceptsEmptyValues = false;

    /**
     * Validates the frequency as a number of seconds or a cron syntax.
     *
     * @param string $frequency The frequency to validate
     * @return bool
     */
    public function isValid($frequency)
    {
        // Frequency is mandatory (NOTE: this does not work, empty string are not submitted for validation. Core bug or my mistake?)
        if ($frequency === '') {
            $this->addError(
                    LocalizationUtility::translate(
                            'error_empty_frequency',
                            'external_import'
                    ),
                    1463494395
            );
            return false;
        } else {
            // Try interpreting the frequency as a cron command
            try {
                NormalizeCommand::normalize($frequency);
                return true;
            } // If the cron command was invalid, we may still have a valid frequency in seconds
            catch (\Exception $e) {
                // Check if the frequency is a valid number
                // If yes, assume it is a frequency in seconds, else return error message
                if (is_numeric($frequency)) {
                    return true;
                } else {
                    $this->addError(
                            LocalizationUtility::translate(
                                    'error_wrong_frequency',
                                    'external_import',
                                    array(
                                        $e->getMessage()
                                    )
                            ),
                            1463495019
                    );
                    return false;
                }
            }
        }
    }
}