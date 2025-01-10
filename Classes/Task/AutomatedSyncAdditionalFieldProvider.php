<?php

declare(strict_types=1);

namespace Cobweb\ExternalImport\Task;

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
use Cobweb\ExternalImport\Domain\Repository\ConfigurationRepository;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\Task\AbstractTask;
use TYPO3\CMS\Scheduler\Task\Enumeration\Action;

/**
 * Additional fields provider class for the Scheduler.
 */
class AutomatedSyncAdditionalFieldProvider implements AdditionalFieldProviderInterface
{
    // Name of the additional fields
    protected static string $itemFieldName = 'syncItem';
    protected static string $storageFieldName = 'syncStorage';

    /**
     * This method is used to define new fields for adding or editing a task
     * In this case, it adds several options related to External Import usage.
     *
     * @param array $taskInfo Reference to the array containing the info used in the add/edit form
     * @param AbstractTask $task When editing, reference to the current task object. Null when adding.
     * @param SchedulerModuleController $schedulerModule Reference to the calling object (Scheduler's BE module)
     * @return array Array containing all the information pertaining to the additional fields
     *               The array is multidimensional, keyed to the task class name and each field's id
     *               For each field it provides an associative sub-array with the following:
     *                   ['code']        => The HTML code for the field
     *                   ['label']        => The label of the field (possibly localized)
     *                   ['cshKey']        => The CSH key for the field
     *                   ['cshLabel']    => The code of the CSH label
     * @throws \TYPO3\CMS\Extbase\Object\Exception
     */
    public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $schedulerModule): array
    {
        $additionalFields = [];

        // Add field for items to synchronize
        if (empty($taskInfo[self::$itemFieldName])) {
            if ($schedulerModule->getCurrentAction()->equals(Action::ADD)) {
                $taskInfo[self::$itemFieldName] = 'all';
            } elseif ($schedulerModule->getCurrentAction()->equals(Action::EDIT)) {
                // In case of edit, set to internal value if no data was submitted already
                $configurationKey = GeneralUtility::makeInstance(ConfigurationKey::class);
                $configurationKey->setTableAndIndex($task->table, (string)$task->index);
                $taskInfo[self::$itemFieldName] = $configurationKey->getConfigurationKey();
            }
        }

        // Write the code for the field
        $fieldID = 'task_' . self::$itemFieldName;
        $fieldCode = '<select name="tx_scheduler[' . self::$itemFieldName . ']" id="' . $fieldID . '" class="form-control">';
        $selected = '';
        if ($taskInfo[self::$itemFieldName] === 'all') {
            $selected = ' selected="selected"';
        }
        // Add "all" selector
        $fieldCode .= '<option value="all"' . $selected . '>' .
            $this->getLanguageService()->sL('LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:all') .
            '</option>';
        // Get configuration repository for fetching values
        $configurationRepository = GeneralUtility::makeInstance(ConfigurationRepository::class);

        // Add groups selection
        $groups = $configurationRepository->findAllGroups();
        if (count($groups) > 0) {
            $fieldCode .= '<optgroup label="' .
                $this->getLanguageService()->sL('LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:options.groups') .
                '">';
            foreach ($groups as $group) {
                $id = 'group:' . $group;
                $selected = '';
                if ($taskInfo[self::$itemFieldName] === $id) {
                    $selected = ' selected="selected"';
                }
                $fieldCode .= '<option value="' . $id . '"' . $selected . '>' . $group . '</option>';
            }
            $fieldCode .= '</optgroup>';
        }

        // Add individual configurations
        $configurations = $configurationRepository->findBySync(true);
        if (count($configurations) > 0) {
            $fieldCode .= '<optgroup label="' .
                $this->getLanguageService()->sL('LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:options.configurations') .
                '">';
            foreach ($configurations as $configuration) {
                $id = $configuration['id'];
                $selected = '';
                if ($taskInfo[self::$itemFieldName] === $id) {
                    $selected = ' selected="selected"';
                }
                $label = $this->getLanguageService()->sL('LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:table') .
                    ': ' . $configuration['table'];
                $label .= ', ' . $this->getLanguageService()->sL('LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:index') .
                    ': ' . $configuration['index'];
                $label .= ', ' . $this->getLanguageService()->sL('LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:priority') .
                    ': ' . $configuration['priority'];
                $fieldCode .= '<option value="' . $id . '"' . $selected . '>' . $label . '</option>';
            }
            $fieldCode .= '</optgroup>';
        }
        $fieldCode .= '</select>';
        $additionalFields[$fieldID] = [
            'code' => $fieldCode,
            'label' => 'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:field.syncItem',
            'cshKey' => '_MOD_user_txexternalimportM1',
            'cshLabel' => $fieldID,
        ];

        // Add field for storage page override
        if (empty($taskInfo[self::$storageFieldName])) {
            if ($schedulerModule->getCurrentAction()->equals(Action::ADD)) {
                $taskInfo[self::$storageFieldName] = '';
            } elseif ($schedulerModule->getCurrentAction()->equals(Action::EDIT)) {
                // In case of edit, set to internal value if no data was submitted already
                $taskInfo[self::$storageFieldName] = $task->storage;
            }
        }
        $fieldID = 'task_' . self::$storageFieldName;
        $fieldCode = '<input type="text" class="form-control" name="tx_scheduler[' . self::$storageFieldName . ']" id="' . $fieldID . '" value="' . ($taskInfo[self::$storageFieldName] ?? '') . '" size="10">';
        $additionalFields[$fieldID] = [
            'code' => $fieldCode,
            'label' => 'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:field.syncStorage',
        ];

        return $additionalFields;
    }

    /**
     * This method checks any additional data that is relevant to the specific task
     * If the task class is not relevant, the method is expected to return true
     *
     * @param array $submittedData Reference to the array containing the data submitted by the user
     * @param SchedulerModuleController $schedulerModule Reference to the calling object (Scheduler's BE module)
     * @return bool True if validation was ok (or selected class is not relevant), false otherwise
     */
    public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $schedulerModule): bool
    {
        // Since only a valid value could be chosen from the selector, always return true
        return true;
    }

    /**
     * This method is used to save any additional input into the current task object
     * if the task class matches
     *
     * @param array $submittedData Array containing the data submitted by the user
     * @param AbstractTask $task Reference to the current task object
     */
    public function saveAdditionalFields(array $submittedData, AbstractTask $task): void
    {
        $fieldValue = $submittedData[self::$itemFieldName];
        if ($fieldValue === 'all' || str_starts_with($fieldValue, 'group:')) {
            $task->table = $fieldValue;
            $task->index = 0;
        } else {
            $configurationKey = GeneralUtility::makeInstance(ConfigurationKey::class);
            $configurationKey->setConfigurationKey($fieldValue);
            $task->table = $configurationKey->getTable();
            $task->index = $configurationKey->getIndex();
        }
        $task->storage = (int)$submittedData[self::$storageFieldName];
    }

    protected function getLanguageService(): ?LanguageService
    {
        return $GLOBALS['LANG'] ?? null;
    }
}
