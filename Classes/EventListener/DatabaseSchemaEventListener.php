<?php

declare(strict_types=1);

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

namespace Cobweb\ExternalImport\EventListener;

use TYPO3\CMS\Core\Database\Event\AlterTableDefinitionStatementsEvent;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * If the "reactions" system extension is loaded, add custom field to it
 *
 * TODO: once compatibility with TYPO3 11 is dropped, sys_reaction can be made a requirement
 * and this can be moved to the ext_tables.sql file
 */
class DatabaseSchemaEventListener
{
    public function __invoke(AlterTableDefinitionStatementsEvent $event): void
    {
        if (ExtensionManagementUtility::isLoaded('reactions')) {
            $sql = (string)file_get_contents(
                ExtensionManagementUtility::extPath('external_import') .
                'Resources/Private/Sql/Reactions.sql'
            );
            $event->addSqlData($sql);
        }
    }
}
