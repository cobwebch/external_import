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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Creates a link to the given page in Web > List view.
 *
 * @package Cobweb\ExternalImport\ViewHelpers
 */
class PageLinkViewHelper extends AbstractViewHelper
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
        $this->registerArgument('page', 'int', 'Uid of the page to create the link to', true);
    }

    /**
     * Creates the link.
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
        $uid = (int)$arguments['page'];
        if ($uid === 0) {
            $string = '0';
        } else {
            $page = BackendUtility::getRecord('pages', $uid);
            // If the page doesn't exist, the result is null, but we need rather an empty array
            if ($page === null) {
                $page = array();
            }
            $pageTitle = BackendUtility::getRecordTitle('pages', $page, 1);

            // Create icon for record
            $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
            $elementIcon = $iconFactory->getIconForRecord(
                'pages',
                $page,
                Icon::SIZE_SMALL
            );

            // Return item with link to Web > List
            $editOnClick = "top.goToModule('web_list', '', '&id=" . $uid . "')";
            $linkTitle = LocalizationUtility::translate('jump_to_page', 'external_import');
            $string = '<a href="#" onclick="' . htmlspecialchars($editOnClick) .
                '" title="' . $linkTitle . '">' . $elementIcon . $pageTitle . '</a>';
        }
        return $string;
    }
}
