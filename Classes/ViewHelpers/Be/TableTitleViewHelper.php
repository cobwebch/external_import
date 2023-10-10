<?php

declare(strict_types=1);

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
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Returns the name of a table, as per its TCA definition
 *
 * @package Cobweb\ExternalImport\ViewHelpers\Be
 */
class TableTitleViewHelper extends AbstractViewHelper
{

    /**
     * Initializes the arguments of the ViewHelper.
     *
     * @return void
     */
    public function initializeArguments(): void
    {
        $this->registerArgument('table', 'string', 'Name of the table', true);
    }

    /**
     * Prints out the name of the given table.
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ): string {
        try {
            $title = LocalizationUtility::translate(
                $GLOBALS['TCA'][$arguments['table']]['ctrl']['title']??'',
                ''
            );
            if ($title === null) {
                $title = $GLOBALS['TCA'][$arguments['table']]['ctrl']['title']??$arguments['table']??'Unkown';
            }
        } catch (\Exception $e) {
            $title = $GLOBALS['TCA'][$arguments['table']]['ctrl']['title']??$arguments['table']??'Unkown';
        }
        return $title;
    }
}
