<?php

declare(strict_types=1);

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

use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;
use TYPO3\CMS\Scheduler\CronCommand\NormalizeCommand;

/**
 * Validator for a scheduler frequency, which can be a simple number of seconds
 * or a cron format.
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
     * @param string $value The frequency to validate
     */
    public function isValid($value): void
    {
        // Frequency is mandatory
        if ($value === '') {
            $this->addError(
                $GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xlf:error_empty_frequency'),
                1463494395
            );
        }

        // Try interpreting the frequency as a cron command
        try {
            NormalizeCommand::normalize($value);
        }
        // If the cron command was invalid, we may still have a valid frequency in seconds
        catch (\Exception $e) {
            // Check if the frequency is a valid number
            // If yes, assume it is a frequency in seconds, else issue error
            if (!is_numeric($value)) {
                $this->addError(
                    sprintf(
                        $GLOBALS['LANG']->sL('LLL:EXT:external_import/Resources/Private/Language/locallang.xlf:error_wrong_frequency'),
                        $e->getMessage(),
                    ),
                    1463495019
                );
            }
        }
    }
}
