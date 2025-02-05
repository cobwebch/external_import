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

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Dumps an array in a formatted way.
 */
class ImplodeViewHelper extends AbstractViewHelper
{
    /**
     * Initializes the arguments of the ViewHelper.
     */
    public function initializeArguments(): void
    {
        $this->registerArgument('array', 'array', 'The array to implode', true);
    }

    public function render(): string
    {
        return implode(', ', $this->arguments['array']);
    }
}
