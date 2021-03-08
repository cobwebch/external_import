<?php
namespace Cobweb\ExternalImport\Handler;

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

use Cobweb\ExternalImport\DataHandlerInterface;
use Cobweb\ExternalImport\Importer;
use TYPO3\CMS\Core\Utility\ArrayUtility;

/**
 * Remaps data from a "raw" PHP array to an array mapped to TCA columns.
 *
 * @package Cobweb\ExternalImport\Handler
 */
class ArrayHandler implements DataHandlerInterface
{

    public function __toString()
    {
        return self::class;
    }

    /**
     * Maps the incoming data to an associative array with TCA column names as keys.
     *
     * @param mixed $rawData Data to handle. Could be of any type, as suited for the data handler.
     * @param Importer $importer Back-reference to the current Importer instance
     * @return array
     */
    public function handleData($rawData, Importer $importer): array
    {
        $data = [];
        $counter = 0;
        $configuration = $importer->getExternalConfiguration();
        $columnConfiguration = $configuration->getColumnConfiguration();

        // Loop on all entries
        if (is_array($rawData) && count($rawData) > 0) {
            foreach ($rawData as $theRecord) {
                $referenceCounter = $counter;
                $data[$referenceCounter] = [];

                // Loop on the database columns and get the corresponding value from the import data
                $rows = [];
                foreach ($columnConfiguration as $columnName => $columnData) {
                    try {
                        $theValue = $this->getValue($theRecord, $columnData);
                        if (isset($columnData['substructureFields'])) {
                            $rows[$columnName] = $this->getSubstructureValues(
                                    $theValue,
                                    $columnData['substructureFields']
                            );
                            // Prepare for the case where no substructure was found
                            // If one was found, it is added later
                            $data[$referenceCounter][$columnName] = null;
                        } else {
                            $data[$referenceCounter][$columnName] = $theValue;
                        }
                    }
                    catch (\Exception $e) {
                        // Nothing to do, we ignore values that were not found
                    }
                }

                // If values were found in substructures, denormalize the data
                if (count($rows) > 0) {
                    // First find the longest substructure result
                    $maxItems = 0;
                    foreach ($rows as $column => $items) {
                        $maxItems = max($maxItems, count($items));
                    }
                    // If no additional row needs to be created, increase the counter to move on to the next record
                    if ($maxItems === 0) {
                        $counter++;
                    } else {
                        // Add as many records to the import data as the highest count, while filling in with the values found in each substructure
                        // NOTE: this is not equivalent to a full denormalization, but is enough for the needs of External Import
                        for ($i = 0; $i < $maxItems; $i++) {
                            // Base data is the first entry of the $theData array
                            // NOTE: the first pass is a neutral operation
                            $data[$counter] = $data[$referenceCounter];
                            // Add a value from each structure field to each row, if it exists
                            foreach ($rows as $column => $items) {
                                if (isset($items[$i])) {
                                    foreach ($items[$i] as $key => $item) {
                                        $data[$counter][$key] = $item;
                                    }
                                }
                            }
                            $counter++;
                        }
                    }
                // No substructure data, increase the counter to move on to the next record
                } else {
                    $counter++;
                }
            }
        }
        // Filter out empty entries (may happen if no value could be found)
        foreach ($data as $index => $item) {
            if (count($item) === 0) {
                unset($data[$index]);
            }
        }
        // Compact array
        $data = array_values($data);
        return $data;
    }

    /**
     * Searches for a value inside the record using the given configuration.
     *
     * @param array $record Data record
     * @param array $columnConfiguration External Import configuration for a single column
     * @return mixed
     */
    public function getValue($record, $columnConfiguration)
    {
        if (isset($columnConfiguration['arrayPath'])) {
            $value = ArrayUtility::getValueByPath(
                    $record,
                    $columnConfiguration['arrayPath'],
                    $columnConfiguration['arrayPathSeparator'] ?? '/'
            );
        } elseif (isset($columnConfiguration['field'], $record[$columnConfiguration['field']])) {
            $value = $record[$columnConfiguration['field']];
        } else {
            throw new \InvalidArgumentException(
                    'No value found',
                    1534149806
            );
        }
        return $value;
    }

    /**
     * Extracts data from a substructure, i.e. when a value is not just a simple type but contains
     * related data.
     *
     * @param array $structure Data structure
     * @param array $columnConfiguration External Import configuration for a single column
     * @return array
     */
    public function getSubstructureValues($structure, $columnConfiguration): array
    {
        $rows = [];
        foreach ($structure as $item) {
            $row = [];
            foreach ($columnConfiguration as $key => $configuration) {
                try {
                    $value = $this->getValue($item, $configuration);
                    $row[$key] = $value;
                }
                catch (\Exception $e) {
                    // Nothing to do, we ignore values that were not found
                }
            }
            $rows[] = $row;
        }
        return $rows;
    }
}