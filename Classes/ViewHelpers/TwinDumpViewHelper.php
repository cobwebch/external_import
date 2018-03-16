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

use Cobweb\ExternalImport\Utility\DebugUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Dumps an array in a formatted way, comparing it with a second, modified array.
 *
 * @package Cobweb\ExternalImport\ViewHelpers
 */
class TwinDumpViewHelper extends AbstractViewHelper
{
    /**
     * Do not escape output of child nodes.
     *
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * Dumps the original array and its differences.
     *
     * @param array $originalArray The original array to dump
     * @param array $modifiedArray The modifiedarray to dump
     * @return string
     */
    public function render(array $originalArray, array $modifiedArray)
    {
        return static::renderStatic(
            array(
                'originalArray' => $originalArray,
                'modifiedArray' => $modifiedArray
            ),
            $this->buildRenderChildrenClosure(),
            $this->renderingContext
        );
    }

    /**
     * Dumps the original array and its differences.
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        return DebugUtility::dumpTwinArrays(
                $arguments['originalArray'],
                $arguments['modifiedArray']
        );
    }
}
