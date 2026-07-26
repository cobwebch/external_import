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

namespace Cobweb\ExternalImport\Processors;

use Cobweb\ExternalImport\Domain\Repository\ConfigurationRepository;
use TYPO3\CMS\Core\DataHandling\ItemsProcessorContext;
use TYPO3\CMS\Core\DataHandling\ItemsProcessorInterface;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Schema\Struct\SelectItem;
use TYPO3\CMS\Core\Schema\Struct\SelectItemCollection;

class ConfigurationsProcessor implements ItemsProcessorInterface
{
    public function __construct(
        protected ConfigurationRepository $configurationRepository
    ) {}

    public function processItems(
        SelectItemCollection $items,
        ItemsProcessorContext $context,
    ): SelectItemCollection {
        // Add all configuration groups
        $groups = $this->configurationRepository->findAllGroups(true);
        if (count($groups) > 0) {
            $itemGroup = $this->getLanguageService()->translate('tx_scheduler_task.sync_item.groups', 'external_import.db');
            foreach ($groups as $group) {
                $items->add(
                    new SelectItem(
                        type: 'select',
                        label: $group,
                        value: 'group:' . $group,
                        group: $itemGroup,
                    )
                );
            }
        }

        $configurations = $this->configurationRepository->findBySync(true);
        if (count($configurations) > 0) {
            $itemGroup = $this->getLanguageService()->translate('tx_scheduler_task.sync_item.configurations', 'external_import.db');
            foreach ($configurations as $configuration) {
                $label = sprintf(
                    $this->getLanguageService()->translate('tx_scheduler_task.sync_item.configuration.label', 'external_import.db'),
                    $configuration['table'],
                    $configuration['index'],
                    $configuration['priority']
                );
                $items->add(
                    new SelectItem(
                        type: 'select',
                        label: $label,
                        value: $configuration['id'],
                        group: $itemGroup,
                    )
                );
            }
        }

        return $items;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
