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
     * Checks whether we are running TYPO3 v9 or not (i.e. more, TYPO3 v10).
     *
     * @return bool
     */
    public static function isV9(): bool
    {
        return !(VersionNumberUtility::convertVersionNumberToInteger(TYPO3_branch) >= VersionNumberUtility::convertVersionNumberToInteger('10.0.0'));
    }

    /**
     * Checks whether the Scheduler's module controller has issued the specified command.
     *
     * Compatibility for mechanism change between TYPO3 v9 and v10.
     *
     * @param SchedulerModuleController $controller
     * @param string $command Expected to be "add" or "edit". Other strings are unchecked and will return false.
     * @return bool
     */
    public static function isSchedulerCommand(SchedulerModuleController $controller, string $command): bool
    {
        $status = false;
        if (self::isV9()) {
            if ($command === 'add' && $controller->CMD === 'add') {
                $status = true;
            } elseif ($command === 'edit' && $controller->CMD === 'edit') {
                $status = true;
            }
        } else {
            if ($command === 'add' && $controller->getCurrentAction()->equals(Action::ADD)) {
                $status = true;
            } elseif ($command === 'edit' && $controller->getCurrentAction()->equals(Action::EDIT)) {
                $status = true;
            }
        }
        return $status;
    }
}