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
    /**
     * Maps the incoming data to an associative array with TCA column names as keys.
     *
     * @param mixed $rawData Data to handle. Could be of any type, as suited for the data handler.
     * @param Importer $importer Back-reference to the current Importer instance
     * @return array
     */
    public function handleData($rawData, Importer $importer)
    {
        $data = array();
        $configuration = $importer->getExternalConfiguration();
        $columnConfiguration = $configuration->getColumnConfiguration();

        // Loop on all entries
        if (is_array($rawData) && count($rawData) > 0) {
            foreach ($rawData as $theRecord) {
                $theData = array();

                // Loop on the database columns and get the corresponding value from the import data
                foreach ($columnConfiguration as $columnName => $columnData) {
                    if (isset($columnData['arrayPath'])) {
                        $theData[$columnName] = ArrayUtility::getValueByPath(
                                $theRecord,
                                $columnData['arrayPath'],
                                $columnData['arrayPathSeparator'] ?? '/'
                        );
                    } elseif (isset($columnData['field'], $theRecord[$columnData['field']])) {
                        $theData[$columnName] = $theRecord[$columnData['field']];
                    }
                }

                // Get additional fields data, if any
                if ($configuration->getCountAdditionalFields() > 0) {
                    foreach ($configuration->getAdditionalFields() as $fieldName) {
                        if (isset($theRecord[$fieldName])) {
                            $theData[$fieldName] = $theRecord[$fieldName];
                        }
                    }
                }

                $data[] = $theData;
            }
        }
        return $data;
    }
}