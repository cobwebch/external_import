<?php
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

/**
 * Small class for encapsulating methods related to backwards-compatibility.
 *
 * @package Cobweb\ExternalImport\Utility
 */
class CompatibilityUtility
{
    /**
     * Checks whether we are running TYPO3 v8 or not (i.e. more, TYPO3 v9).
     *
     * @return bool
     */
    public static function isV8()
    {
        if (VersionNumberUtility::convertVersionNumberToInteger(TYPO3_branch) >= VersionNumberUtility::convertVersionNumberToInteger('9.0.0')) {
            return false;
        }
        return true;
    }
}