<?php
namespace Cobweb\ExternalImport\Domain\Repository;

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

use Cobweb\ExternalImport\Domain\Model\Configuration;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Class used for retrieving UIDs according to external configuration.
 *
 * @package Cobweb\ExternalImport\Domain\Repository
 */
class UidRepository implements SingletonInterface
{
    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * @var array List of retrieved UIDs
     */
    protected $existingUids = null;

    /**
     * @var array List of current PIDs
     */
    protected $currentPids = null;

    /**
     * Sets the Configuration object at run-time.
     *
     * @param Configuration $configuration
     */
    public function setConfiguration(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * Prepares a list of all existing primary keys in the table being synchronized.
     *
     * The result is a hash table of all external primary keys matched to internal primary keys.
     * PIDs are also retrieved.
     *
     * @return void
     */
    protected function retrieveExistingUids()
    {
        $table = $this->configuration->getTable();
        $ctrlConfiguration = $this->configuration->getCtrlConfiguration();
        $where = '1 = 1';
        if ($ctrlConfiguration['enforcePid']) {
            $where = 'pid = ' . (int)$this->configuration->getStoragePid();
        }
        if (!empty($ctrlConfiguration['whereClause'])) {
            $where .= ' AND ' . $ctrlConfiguration['whereClause'];
        }
        $where .= BackendUtility::deleteClause($table);
        $referenceUidField = $ctrlConfiguration['referenceUid'];
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                $referenceUidField . ',uid, pid',
                $table,
                $where
        );
        if ($res) {
            while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
                // Don't consider records with empty references, as they can't be matched
                // to external data anyway (but a real zero is acceptable)
                if (!empty($row[$referenceUidField]) || $row[$referenceUidField] === '0' || $row[$referenceUidField] === 0) {
                    $this->existingUids[$row[$referenceUidField]] = $row['uid'];
                    $this->currentPids[$row[$referenceUidField]] = $row['pid'];
                }
            }
            $GLOBALS['TYPO3_DB']->sql_free_result($res);
        }
    }

    /**
     * Returns the list of primary keys of existing records in the database.
     *
     * @return array
     */
    public function getExistingUids()
    {
        // If the list is UIDs is null, assume it wasn't fetched yet and do so
        if ($this->existingUids === null) {
            $this->retrieveExistingUids();
        }
        return $this->existingUids;
    }

    /**
     * Resets the list of primary keys.
     *
     * @return void
     */
    public function resetExistingUids()
    {
        $this->existingUids = null;
    }

    /**
     * Returns the list of storage PIDs of existing records in the database.
     *
     * @return array
     */
    public function getCurrentPids()
    {
        // If the list is UIDs is null, assume it wasn't fetched yet and do so
        if ($this->currentPids === null) {
            $this->retrieveExistingUids();
        }
        return $this->currentPids;
    }

    /**
     * Resets the list of primary keys.
     *
     * @return void
     */
    public function resetCurrentPids()
    {
        $this->currentPids = null;
    }
}