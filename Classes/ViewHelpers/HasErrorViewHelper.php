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

use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Checks for errors for a given property.
 *
 * @package Cobweb\ExternalImport\ViewHelpers
 */
class HasErrorViewHelper extends AbstractViewHelper
{
    /**
     * Renders some string or other depending on there being errors for a given property.
     *
     * @param string $for Name of the property to check
     * @param string $then Output if there are any errors
     * @param string $else Output if there are no errors
     * @return string
     */
    public function render($for, $then, $else)
    {
        $errors = $this->controllerContext->getRequest()->getOriginalRequestMappingResults()->getFlattenedErrors();
        if (count($errors[$for]) > 0) {
            return $then;
        } else {
            return $else;
        }
    }
}