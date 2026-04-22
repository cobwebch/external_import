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
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Validates the general part of a configuration and loads the validation results as a container variable.
 */
class ValidateGeneralConfigurationViewHelper extends AbstractViewHelper
{
    public function __construct(
        protected GeneralConfigurationValidator $configurationValidator,
    ) {
    }

    /**
     * Do not escape output of child nodes.
     *
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * Initializes the arguments of the ViewHelper.
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
     */
    public function render(): string
    {
        $this->configurationValidator->isValid($this->arguments['configuration']);
        $templateVariableContainer = $this->renderingContext->getVariableProvider();
        $templateVariableContainer->add(
            $this->arguments['as'],
            $this->configurationValidator->getResults()->getAll()
        );
        $output = $this->renderChildren();
        $templateVariableContainer->remove($this->arguments['as']);
        return $output;
    }
}
