<?php
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
use Cobweb\ExternalImport\Validator\ColumnConfigurationValidator;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Validates the "column" part of a configuration and loads the validation results as a container variable.
 *
 * @package Cobweb\ExternalImport\ViewHelpers
 */
class ValidateColumnConfigurationViewHelper extends AbstractViewHelper
{
    /**
     * Do not escape output of child nodes.
     *
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * Runs the validation and loads the results.
     *
     * @param Configuration $configuration The general external import configuration
     * @param string $column The name of the column to check
     * @param string $as Name of the variable in which to store the validation results
     * @return string
     */
    public function render(Configuration $configuration, $column, $as)
    {
        $configurationValidator = $this->objectManager->get(ColumnConfigurationValidator::class);
        $configurationValidator->isValid($configuration, $column);
        $templateVariableContainer = $this->renderingContext->getTemplateVariableContainer();
        $templateVariableContainer->add($as, $configurationValidator->getResults());
        $output = $this->renderChildren();
        $templateVariableContainer->remove($as);
        return $output;
    }
}
