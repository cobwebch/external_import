<?php
namespace Cobweb\ExternalImport\Step;

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

/**
 * This class ensure that permissions are enough to allow the import process to run.
 *
 * @package Cobweb\ExternalImport\Step
 */
class CheckPermissionsStep extends AbstractStep
{
    /**
     * Checks the current user has enough permissions to run the import process to the end.
     *
     * @return void
     */
    public function run()
    {
        $table = $this->importer->getExternalConfiguration()->getTable();
        if (!$GLOBALS['BE_USER']->check('tables_modify', $table)) {
            $this->abortFlag = true;
            $userName = $GLOBALS['BE_USER']->user['username'];
            $this->importer->addMessage(
                    LocalizationUtility::translate(
                            'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:no_rights_for_sync',
                            'external_import',
                            array($userName, $table)
                    )
            );
        }
    }
}