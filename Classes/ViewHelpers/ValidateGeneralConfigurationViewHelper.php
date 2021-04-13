<?php

declare(strict_types=1);

namespace Cobweb\ExternalImport\ViewHelpers;

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

use Cobweb\ExternalImport\Domain\Model\Configuration;
use Cobweb\ExternalImport\Validator\GeneralConfigurationValidator;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Validates the general part of a configuration and loads the validation results as a container variable.
 *
 * @package Cobweb\ExternalImport\ViewHelpers
 */
class ValidateGeneralConfigurationViewHelper extends AbstractViewHelper
{
    /**
     * Do not escape output of child nodes.
     *
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * Initializes the arguments of the ViewHelper.
     *
     * @return void
     */
    public function initializeArguments(): void
    {
        $this->registerArgument(
            'configuration',
            Configuration::class,
            'General external import configuration object',
            true
        );
        $this->registerArgument('as', 'string', 'Name of the variable in which to store the validation results', true);
    }

    /**
     * Runs the validation and loads the results.
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return string
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ): string {
        $configurationValidator = GeneralUtility::makeInstance(GeneralConfigurationValidator::class);
        $configurationValidator->isValid($arguments['configuration']);
        $templateVariableContainer = $renderingContext->getVariableProvider();
        $templateVariableContainer->add(
            $arguments['as'],
            $configurationValidator->getResults()->getAll()
        );
        $output = $renderChildrenClosure();
        $templateVariableContainer->remove($arguments['as']);
        return $output;
    }
}
