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

use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Displays the validation result for the current property.
 *
 * @package Cobweb\ExternalImport\ViewHelpers
 */
class DisplayValidationResultViewHelper extends AbstractViewHelper
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
    public function initializeArguments()
    {
        $this->registerArgument('result', 'array', 'Validation result (contains message and severity)', true);
    }

    /**
     * Renders the validation result.
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $results = $arguments['result'];
        $classes = [
                AbstractMessage::INFO => 'alert-info',
                AbstractMessage::NOTICE => 'alert-notice',
                AbstractMessage::WARNING => 'alert-warning',
                AbstractMessage::ERROR => 'alert-danger'
        ];
        $message = '<div><ul class="typo3-messages external-import-messages"><li class="alert %1$s">%2$s</li></ul></div>';
        $output = '';
        foreach ($results as $result) {
            $output .= sprintf(
                    $message,
                    $classes[$result['severity']],
                    $result['message']
            );
        }
        return $output;
    }
}