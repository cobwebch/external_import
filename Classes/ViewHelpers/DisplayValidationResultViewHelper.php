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

use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Displays the validation result for the current property.
 *
 * @package Cobweb\ExternalImport\ViewHelpers
 */
class DisplayValidationResultViewHelper extends AbstractViewHelper
{
    /**
     * Renders the validation result.
     *
     * @param array $result Validation result (contains message and severity)
     * @return string
     */
    public function render($result)
    {
        $classes = array(
                FlashMessage::NOTICE => 'alert-notice',
                FlashMessage::WARNING => 'alert-warning',
                FlashMessage::ERROR => 'alert-danger'
        );
        $message = '<div><ul class="typo3-messages external-import-messages"><li class="alert %1$s">%2$s</li></ul></div>';
        return sprintf(
                $message,
                $classes[$result['severity']],
                $result['message']
        );
    }
}