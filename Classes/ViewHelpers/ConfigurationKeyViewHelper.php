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

use Cobweb\ExternalImport\Domain\Model\ConfigurationKey;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Dumps an array in a formatted way.
 */
class ConfigurationKeyViewHelper extends AbstractViewHelper
{
    /**
     * Initializes the arguments of the ViewHelper.
     */
    public function initializeArguments(): void
    {
        $this->registerArgument('table', 'string', 'The name of the table', true);
        $this->registerArgument('index', 'string', 'The external import configuration index', false, '');
    }

    public function render(): string
    {
        $configurationKey = new ConfigurationKey();
        $configurationKey->setTableAndIndex(
            $this->arguments['table'],
            $this->arguments['index']
        );
        return $configurationKey->getConfigurationKey();
    }
}
