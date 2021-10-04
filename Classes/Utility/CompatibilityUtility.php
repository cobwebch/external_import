<?php

declare(strict_types=1);

namespace Cobweb\ExternalImport\Utility;

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

use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\Task\Enumeration\Action;

/**
 * Small class for encapsulating methods related to backwards-compatibility.
 *
 * @package Cobweb\ExternalImport\Utility
 */
class CompatibilityUtility
{
    /**
     * Checks whether we are running TYPO3 v10 or not (i.e. more, TYPO3 v11).
     *
     * @return bool
     */
    public static function isV10(): bool
    {
        return !(VersionNumberUtility::convertVersionNumberToInteger(
                TYPO3_branch
            ) >= VersionNumberUtility::convertVersionNumberToInteger('11.0.0'));
    }
}