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

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractConditionViewHelper;

/**
 * Checks for errors for a given property.
 *
 * @package Cobweb\ExternalImport\ViewHelpers
 */
class HasErrorViewHelper extends AbstractConditionViewHelper
{

    /**
     * Initializes the arguments of the ViewHelper.
     *
     * @return void
     */
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('errors', 'array', 'Validation error results', true);
        $this->registerArgument('for', 'string', 'Name of the field to fetch the errors for', true);
    }

    /**
     * Returns true if there's at least one error for the given field.
     *
     * @param array|NULL $arguments
     * @return bool
     */
    protected static function evaluateCondition($arguments = null): bool
    {
        if (isset($arguments['errors'][$arguments['for']])) {
            return count($arguments['errors'][$arguments['for']]) > 0;
        }
        return false;
    }
}