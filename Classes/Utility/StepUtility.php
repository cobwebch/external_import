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

use Cobweb\ExternalImport\Exception\InvalidCustomStepConfiguration;
use Cobweb\ExternalImport\Step\AbstractStep;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Manages the insertion of custom steps into the steps array.
 */
class StepUtility
{
    /**
     * Adds a custom step to the list of current steps.
     *
     * If the custom step configuration is invalid, the list of steps is returned unchanged.
     *
     * @param array $currentSteps List of current steps
     * @param array $configuration Configuration for the new step
     * @return array Modified list of steps
     */
    public function insertStep(array $currentSteps, array $configuration): ?array
    {
        try {
            $validatedConfiguration = $this->validateCustomStepConfiguration(
                $currentSteps,
                $configuration
            );
            $index = array_search(
                $validatedConfiguration['reference'],
                $currentSteps,
                true
            );
            if ($validatedConfiguration['position'] === 'after') {
                $index++;
            }
            array_splice(
                $currentSteps,
                $index,
                0,
                $validatedConfiguration['class']
            );
            return $currentSteps;
        } catch (InvalidCustomStepConfiguration $e) {
            return $currentSteps;
        }
    }

    /**
     * Validates a custom step configuration against the list of current steps.
     *
     * @param array $currentSteps List of current steps
     * @param array $configuration Configuration for the new step
     * @return array Validated configuration
     * @throws InvalidCustomStepConfiguration
     */
    public function validateCustomStepConfiguration(array $currentSteps, array $configuration): array
    {
        // Early exit if configuration is incomplete
        if (!isset($configuration['class'])) {
            throw new InvalidCustomStepConfiguration(
                'Missing class information in custom step configuration',
                1500829768
            );
        }
        if (!isset($configuration['position'])) {
            throw new InvalidCustomStepConfiguration(
                'Missing position information in custom step configuration',
                1500829792
            );
        }

        // Check position syntax
        $positionParts = GeneralUtility::trimExplode(
            ':',
            $configuration['position'],
            true
        );
        if (count($positionParts) !== 2) {
            throw new InvalidCustomStepConfiguration(
                sprintf(
                    'Position information must be comprised of two parts ("before" or "after" and a class name) separated by a colon (value received: %s)',
                    $configuration['position']
                ),
                1500829954
            );
        }
        $position = strtolower($positionParts[0]);
        if ($position !== 'before' && $position !== 'after') {
            throw new InvalidCustomStepConfiguration(
                sprintf(
                    'Position information must start with either "before" or "after" (value received: %s)',
                    $position
                ),
                1500830140
            );
        }
        $class = $positionParts[1];
        if (!class_exists($class)) {
            throw new InvalidCustomStepConfiguration(
                sprintf(
                    'Class given for position does not exist (value received: %s)',
                    $class
                ),
                1500830270
            );
        }
        if (!in_array($class, $currentSteps, true)) {
            throw new InvalidCustomStepConfiguration(
                sprintf(
                    'Class given for position is not part of current classes (value received: %s)',
                    $class
                ),
                1500830309
            );
        }

        // Check  if custom step class exists and try to instantiate it
        $stepClass = $configuration['class'];
        if (!class_exists($stepClass)) {
            throw new InvalidCustomStepConfiguration(
                sprintf(
                    'Custom step class does not exist (value received: %s)',
                    $stepClass
                ),
                1501357796
            );
        }
        try {
            $step = GeneralUtility::makeInstance($stepClass);
            if (!($step instanceof AbstractStep)) {
                throw new InvalidCustomStepConfiguration(
                    sprintf(
                        'Custom step class does not inherit from %1$s (value received: %2$s)',
                        AbstractStep::class,
                        $stepClass
                    ),
                    1500830527
                );
            }
        } catch (\InvalidArgumentException $e) {
            throw new InvalidCustomStepConfiguration(
                sprintf(
                    'Custom step class could not be instantiated (value received: %s)',
                    $stepClass
                ),
                1500830620
            );
        }

        // If everything passed, return validated configuration
        return [
            'class' => $stepClass,
            'position' => $position,
            'reference' => $class,
        ];
    }
}
