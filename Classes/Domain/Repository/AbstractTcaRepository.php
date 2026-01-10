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

namespace Cobweb\ExternalImport\Domain\Repository;

/**
 * Base class for any TCA repository, providing default implementation for all methods
 * that are not related to actually retrieving the TCA.
 */
abstract class AbstractTcaRepository implements TcaRepositoryInterface
{
    abstract public function getTca(): array;

    public function getExternalGeneralConfigurationForTableAndIndex(string $table, int|string $index): ?array
    {
        return $this->getTca()[$table]['external']['general'][$index] ?? null;
    }

    public function getExternalAdditionalFieldsConfigurationForTableAndIndex(string $table, int|string $index): ?array
    {
        return $this->getTca()[$table]['external']['additionalFields'][$index] ?? null;
    }

    public function getExternalColumnsConfigurationForTableAndIndex(string $table, int|string $index, int|string|null $alternateIndex): array
    {
        $columnConfigurations = [];
        $columns = $this->getTca()[$table]['columns'];
        ksort($columns);
        foreach ($columns as $field => $configuration) {
            // If a configuration for the given column and index exists, it always takes precedence,
            // otherwise the alternate index is considered, if defined
            if (isset($configuration['external'][$index])) {
                $columnConfigurations[$field] = $configuration['external'][$index];
            } elseif ($alternateIndex !== null && isset($configuration['external'][$alternateIndex])) {
                $columnConfigurations[$field] = $configuration['external'][$alternateIndex];
            }
        }
        return $columnConfigurations;
    }

    public function getAllGeneralExternalConfigurations(): array
    {
        $configurations = [];
        foreach ($this->getTca() as $table => $sections) {
            if (isset($sections['external']['general'])) {
                $configurations[$table] = $sections['external']['general'];
            }
        }
        return $configurations;
    }
}
