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

namespace Cobweb\ExternalImport\UserFunction;

use Cobweb\ExternalImport\Domain\Repository\ConfigurationRepository;
use Cobweb\ExternalImport\Reaction\ImportReaction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Provides a list of configuration items for a Form Engine selector
 */
class ConfigurationItems
{
    public function listConfigurationItems(array &$parameters): void
    {
        $reactionType = array_shift($parameters['row']['reaction_type']);
        $configurationRepository = GeneralUtility::makeInstance(ConfigurationRepository::class);

        // Present group of synchronization groups (only for import reaction)
        if ($reactionType === ImportReaction::getType()) {
            $groups = $configurationRepository->findAllGroups();
            if (count($groups) > 0) {
                $parameters['items'][] = [
                    'label' => 'LLL:EXT:external_import/Resources/Private/Language/locallang_db.xlf:sys_reaction.external_import_configuration.groups',
                    'value' => '--div--',
                    'group' => 'groups',
                ];
                foreach ($groups as $group) {
                    $parameters['items'][] = [
                        'label' => $group,
                        'value' => 'group:' . $group,
                        'group' => 'groups',
                    ];
                }
            }
        }

        // Present group of non-synchronizable tables
        $nonSynchronizableItems = $configurationRepository->findBySync(false);
        if (count($nonSynchronizableItems) > 0) {
            $parameters['items'][] = [
                'label' => 'LLL:EXT:external_import/Resources/Private/Language/locallang_db.xlf:sys_reaction.external_import_configuration.nonsynchronizable_tables',
                'value' => '--div--',
                'group' => 'nosync',
            ];
            foreach ($nonSynchronizableItems as $item) {
                $parameters['items'][] = [
                    'label' => $item['table'] . ' - ' . $item['index'],
                    'value' => $item['id'],
                    'group' => 'nosync',
                ];
            }
        }

        // Present group of synchronizable tables
        $synchronizableItems = $configurationRepository->findBySync(true);
        if (count($synchronizableItems) > 0) {
            $parameters['items'][] = [
                'label' => 'LLL:EXT:external_import/Resources/Private/Language/locallang_db.xlf:sys_reaction.external_import_configuration.synchronizable_tables',
                'value' => '--div--',
                'group' => 'sync',
            ];
            foreach ($synchronizableItems as $item) {
                $parameters['items'][] = [
                    'label' => $item['table'] . ' - ' . $item['index'],
                    'value' => $item['id'],
                    'group' => 'sync',
                ];
            }
        }
    }
}
