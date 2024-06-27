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

use Cobweb\ExternalImport\Importer;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\Model\RecordStateFactory;
use TYPO3\CMS\Core\DataHandling\SlugHelper;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Handles the update of slug fields.
 *
 * NOTE: I know I have taken parts of this code from somewhere, but I don't remember where.
 * If you recognize your baby, please raise you hand so that I can duly credit you.
 */
class SlugUtility
{
    /**
     * @var Importer
     */
    protected Importer $importer;

    /**
     * @var array
     */
    protected array $slugFieldNamesPerTable = [];

    public function __construct(Importer $importer)
    {
        $this->importer = $importer;
    }

    /**
     * Generates and updates slugs for the given records of the given table.
     *
     * @param string $table Name of the affected table
     * @param array $uids List of primary keys of records to update
     */
    public function updateAll(string $table, array $uids): void
    {
        // Get the list of slug fields for the given table
        $fieldsToUpdate = $this->resolveSlugFieldNames($table);
        // Get full data for each record to update
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $records = $queryBuilder->select('*')
            ->from($table)
            ->where(
                $queryBuilder->expr()->in('uid', $uids)
            )->executeQuery()
            ->fetchAllAssociative();
        // Generate the new slug for each record
        $newSlugs = [];
        foreach ($fieldsToUpdate as $field) {
            $newSlugs[$field] = [];
            $fieldConfiguration = $GLOBALS['TCA'][$table]['columns'][$field]['config'];
            foreach ($records as $record) {
                $slug = $this->generateSlug($table, $field, $fieldConfiguration, $record);
                // Check that the slug is really different, if yes save it to database
                if ($slug !== $record[$field]) {
                    $queryBuilder->update($table)
                        ->set($field, $slug)
                        ->where(
                            $queryBuilder->expr()->eq('uid', $record['uid'])
                        )->executeStatement();
                }
            }
        }
    }

    /**
     * Generates a slug for the given table, field and record.
     *
     * @param string $table
     * @param string $field
     * @param array $fieldConfiguration
     * @param array $record
     * @return string
     */
    public function generateSlug(string $table, string $field, array $fieldConfiguration, array $record): string
    {
        $slug = '';
        $pid = $record['pid'];
        $slugHelper = GeneralUtility::makeInstance(
            SlugHelper::class,
            $table,
            $field,
            $fieldConfiguration
        );
        $slugCandidate = $slugHelper->generate($record, $pid);
        // Take care of the various unicity conditions, if any
        if (GeneralUtility::inList('uniqueInSite', $fieldConfiguration['eval'] ?? '')) {
            $state = RecordStateFactory::forName($table)
                ->fromArray(
                    $record,
                    $pid
                );
            try {
                $slug = $slugHelper->buildSlugForUniqueInSite($slugCandidate, $state);
            } catch (\Exception $e) {
                // Let the slug be empty and log the problem
                $this->importer->addMessage(
                    sprintf(
                        'Could not generate slug for record %1$d in table %2$s (reason: %3$s [%4$d])',
                        $record['uid'],
                        $table,
                        $e->getMessage(),
                        $e->getCode()
                    ),
                    AbstractMessage::NOTICE
                );
            }
        } elseif (GeneralUtility::inList('uniqueInPid', $fieldConfiguration['eval'] ?? '')) {
            $state = RecordStateFactory::forName($table)
                ->fromArray(
                    $record,
                    $pid,
                    $record['uid']
                );
            $slug = $slugHelper->buildSlugForUniqueInPid($slugCandidate, $state);
        } elseif (GeneralUtility::inList('unique', $fieldConfiguration['eval'] ?? '')) {
            $state = RecordStateFactory::forName($table)
                ->fromArray(
                    $record,
                    $pid
                );
            try {
                $slug = $slugHelper->buildSlugForUniqueInTable($slugCandidate, $state);
            } catch (\Exception $e) {
                // Let the slug be empty and log the problem
                $this->importer->addMessage(
                    sprintf(
                        'Could not generate slug for record %1$d in table %2$s (reason: %3$s [%4$d])',
                        $record['uid'],
                        $table,
                        $e->getMessage(),
                        $e->getCode()
                    ),
                    AbstractMessage::NOTICE
                );
            }
        } else {
            $slug = $slugCandidate;
        }
        return $slug;
    }

    /**
     * Returns the list of all slug fields for the given table.
     *
     * It may seem odd that there be more than one, but you never know...
     *
     * @param string $tableName
     * @return string[]
     */
    public function resolveSlugFieldNames(string $tableName): array
    {
        if (isset($this->slugFieldNamesPerTable[$tableName])) {
            return $this->slugFieldNamesPerTable[$tableName];
        }

        return $this->slugFieldNamesPerTable[$tableName] = array_keys(
            array_filter(
                $GLOBALS['TCA'][$tableName]['columns'] ?? [],
                function (array $settings) {
                    return ($settings['config']['type'] ?? null) === 'slug';
                }
            )
        );
    }
}
