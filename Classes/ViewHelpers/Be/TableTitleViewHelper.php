<?php
namespace Cobweb\ExternalImport\ViewHelpers\Be;

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

use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface;

class TableTitleViewHelper extends AbstractViewHelper implements CompilableInterface
{
    /**
     * Prints out the name of the given table.
     *
     * @param string $table Name of the table
     * @return string
     */
    public function render($table)
    {
        return static::renderStatic(
            array(
                'table' => $table
            ),
            $this->buildRenderChildrenClosure(),
            $this->renderingContext
        );
    }

    /**
     * Prints out the name of the given table.
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        try {
            $title = LocalizationUtility::translate(
                            $GLOBALS['TCA'][$arguments['table']]['ctrl']['title'],
                            ''
                    );
            if ($title === null) {
                $title = $GLOBALS['TCA'][$arguments['table']]['ctrl']['title'];
            }
        }
        catch (\Exception $e) {
            $title = $GLOBALS['TCA'][$arguments['table']]['ctrl']['title'];
        }
        return $title;
    }
}
