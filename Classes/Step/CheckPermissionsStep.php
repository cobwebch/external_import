<?php

declare(strict_types=1);

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

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class ensure that permissions are enough to allow the import process to run.
 */
class CheckPermissionsStep extends AbstractStep
{
    /**
     * Checks that the current user has enough permissions to run the import process to the end.
     */
    public function run(): void
    {
        $table = $this->importer->getExternalConfiguration()->getTable();
        if (!$this->getBackendUser()->check('tables_modify', $table)) {
            $this->abortFlag = true;
            $context = GeneralUtility::makeInstance(Context::class);
            try {
                $userName = $context->getPropertyFromAspect('backend.user', 'username');
            } catch (AspectNotFoundException) {
                $userName = 'Unknown';
            }
            $this->importer->addMessage(
                sprintf(
                    $this->importer->getLanguageService()->sL('LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:no_rights_for_sync'),
                    $userName,
                    $table
                )
            );
        }
    }

    /**
     * Returns the BE user object.
     *
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
