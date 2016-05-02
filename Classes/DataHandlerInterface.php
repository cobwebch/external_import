<?php
namespace Cobweb\ExternalImport;

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

/**
 * Interface which defines the method to implement when creating a custom data handler for External Import
 *
 * @author Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package TYPO3
 * @subpackage tx_externalimport
 */
interface DataHandlerInterface
{
    /**
     * Handles the raw data passed to it and returns it as a simple, indexed PHP array
     *
     * @param mixed $rawData Data to handle. Could be of any type, as suited for the data handler.
     * @param Importer $importerObject The calling importer object
     * @return array The handled data, as PHP array
     */
    public function handleData($rawData, Importer $importerObject);
}
