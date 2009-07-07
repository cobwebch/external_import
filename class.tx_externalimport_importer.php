<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2009 Francois Suter (Cobweb) <typo3@cobweb.ch>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
*
* $Id$
***************************************************************/

/**
 * This class performs the data update from the external sources
 *
 * @author	Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package	TYPO3
 * @subpackage	tx_externalimport
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   64: class tx_externalimport_importer
 *   83:     public function __construct()
 *  112:     public function synchronizeAllTables()
 *  159:     protected function initTCAData($table, $index)
 *  193:     public function synchronizeData($table, $index)
 *  261:     public function importData($table, $index, $rawData)
 *  280:     protected function handleData($rawData)
 *  324:     protected function handleArray($rawData)
 *  366:     protected function handleXML($rawData)
 *  415:     protected function transformData($records)
 *  468:     protected function preprocessRawData($records)
 *  485:     protected function preprocessData($records)
 *  502:     protected function storeData($records)
 *  783:     protected function getExistingUids()
 *  805:     protected function getMapping($mappingData)
 *  832:     protected function logMessages()
 *  855:     public function getTableName()
 *  864:     public function getIndex()
 *  874:     public function getExternalConfig()
 *  886:     public function addMessage($text, $status = 'error')
 *
 * TOTAL FUNCTIONS: 19
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */
class tx_externalimport_importer {
	public $extKey = 'external_import';
	protected $vars = array(); // Variables from the query string
	protected $extConf = array(); // Extension configuration
	protected $messages = array(); // List of result messages
	protected $table; // Name of the table being synchronised
	protected $index; // Index of the synchronisation configuration in use
	protected $tableTCA; // TCA of the table being synchronised
	protected $externalConfig; // Ctrl-section external config being used for synchronisation
	protected $pid = 0; // uid of the page where the records will be stored
	protected $additionalFields = array(); // List of fields to import, but not to save
	protected $numAdditionalFields = 0; // Number of such fields

	/**
	 * This is the constructor
	 * It initialises some properties and makes sure that a lang object is available
	 *
	 * @return	object		tx_externalimport_importer object
	 */
	public function __construct() {
		global $BE_USER;
		$this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extKey]);
		$this->messages = array('error' => array(), 'warning' => array(), 'success' => array());

// Make sure we have a language object
// If initialised, use existing, if not, initialise it

		if (!isset($GLOBALS['LANG'])) {
			require_once(PATH_typo3.'sysext/lang/lang.php');
			$GLOBALS['LANG'] = t3lib_div::makeInstance('language');
			$GLOBALS['LANG']->init($BE_USER->uc['lang']);
		}
		$GLOBALS['LANG']->includeLLFile('EXT:external_import/locallang.xml');

// Force php limit execution time if set
    if (isset($this->extConf['timelimit']) && ($this->extConf['timelimit'] > -1) ) {

      set_time_limit($this->extConf['timelimit']);
      if ($this->extConf['debug'] || TYPO3_DLOG) t3lib_div::devLog($GLOBALS['LANG']->getLL('timelimit'), $this->extKey, 0, $this->extConf['timelimit']);
    }

	}

	/**
	 * This method synchronises all the external tables, respecting the order of priority
	 *
	 * @return	void
	 */
	public function synchronizeAllTables() {

// Look in the TCA for tables with an "external" control section and a "connector"
// Tables without connectors cannot be synchronised

		$externalTables = array();
		foreach ($GLOBALS['TCA'] as $tableName => $sections) {
			foreach ($sections as $sectionKey => $sectionData) {
				if ($sectionKey == 'ctrl' && isset($sectionData['external'])) {
					foreach ($sectionData['external'] as $index => $externalConfig) {
						if (!empty($externalConfig['connector'])) {
							if (isset($externalConfig['priority'])) {
								$priority = $externalConfig['priority'];
							}
							else {
								$priority = 1000; // If priority is not defined, set to very low
							}
							if (!isset($externalTables[$priority])) $externalTables[$priority] = array();
							$externalTables[$priority][] = array('table' => $tableName, 'index' => $index);
						}
					}
				}
			}
		}

// Sort tables by priority (lower number is highest priority)

		ksort($externalTables);
		if ($this->extConf['debug'] || TYPO3_DLOG) t3lib_div::devLog($GLOBALS['LANG']->getLL('sync_all'), $this->extKey, 0, $externalTables);

// Synchronise all tables

		foreach ($externalTables as $priority => $tables) {
			foreach ($tables as $tableData) {
				$this->messages = array('error' => array(), 'warning' => array(), 'success' => array()); // Reset error messages array
				$this->synchronizeData($tableData['table'], $tableData['index']);
			}
		}
	}

	/**
	 * This method stores information about the synchronised table into member variables
	 *
	 * @param	string		$table: name of the table to synchronise
	 * @param	integer		$index: index of the synchronisation configuration to use
	 * @return	void
	 */
	protected function initTCAData($table, $index) {
		$this->table = $table;
		$this->index = $index;
		t3lib_div::loadTCA($this->table);
		$this->tableTCA = $GLOBALS['TCA'][$this->table];
		$this->externalConfig = $GLOBALS['TCA'][$this->table]['ctrl']['external'][$index];
			// Set the pid where the recors will be stored
			// This is either specific for the given table or generic from the extension configuration
		if (isset($this->externalConfig['pid'])) {
			$this->pid = $this->externalConfig['pid'];
		}
		else {
			$this->pid = $this->extConf['storagePID'];
		}
			// Set this storage page as the related page for the devLog entries
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['debugData']['pid'] = $this->pid;

			// Get the list of additional fields
			// Additional fields are fields that must be taken from the imported data,
			// but that will not be saved into the database
		if (!empty($this->externalConfig['additional_fields'])) {
			$this->additionalFields = t3lib_div::trimExplode(',', $this->externalConfig['additional_fields'], 1);
			$this->numAdditionalFields = count($this->additionalFields);
		}
	}

	/**
	 * This method calls on the distant data source and synchronises the data in the TYPO3 database
	 * It returns information about the results of the operation
	 *
	 * @param	string		$table: name of the table to synchronise
	 * @param	integer		$index: index of the synchronisation configuration to use
	 * @return	array		List of error or success messages
	 */
	public function synchronizeData($table, $index) {
		$this->initTCAData($table, $index);

// Instantiate specific connector service

		if (empty($this->externalConfig['connector'])) {
			$this->messages['error'][] = $GLOBALS['LANG']->getLL('no_connector');
		}
		else {
			$services = t3lib_extMgm::findService('connector', $this->externalConfig['connector']);

// The service is not available

			if ($services === false) {
				$this->messages['error'][] = $GLOBALS['LANG']->getLL('no_service');
			}
			else {
				$connector = t3lib_div::makeInstanceService('connector', $this->externalConfig['connector']);

// The service was instatiated, but an error occurred while initiating the connection

				if (is_array($connector)) { // If the returned value is an array, an error has occurred
					$this->messages['error'][] = $GLOBALS['LANG']->getLL('data_not_fetched');
				}
				else {

// The connection is established, get the data

					switch ($this->externalConfig['data']) {
						case 'xml':
							$data = $connector->fetchXML($this->externalConfig['parameters']);
							break;
						case 'array':
							$data = $connector->fetchArray($this->externalConfig['parameters']);
							break;
						default:
							$data = $connector->fetchRaw($this->externalConfig['parameters']);
							break;
					}
					if ($this->extConf['debug'] || TYPO3_DLOG) {
						t3lib_div::devLog('Data received (example)', $this->extKey, -1, $data);
					}
					$this->handleData($data);
				}
			}
		}

			// Log results to devlog
		if ($this->extConf['debug'] || TYPO3_DLOG) {
			$this->logMessages();
		}
			// Flush all existing output (PHP errors, debug output, etc.)
			// so that it doesn't corrupt module response (in particular when called via AJAX)
		if ($this->extConf['flushOutput']) {
			ob_end_clean();
		}
		return $this->messages;
	}

	/**
	 * This method receives raw data from some external source, transforms it and stores it into the local database
	 * It returns information about the results of the operation
	 *
	 * @param	string		$table: name of the table to import into
	 * @param	integer		$index: index of the synchronisation configuration to use
	 * @param	mixed		$rawData: data in the format provided by the external source (XML string, PHP array, etc.)
	 * @return	array		List of error or success messages
	 */
	public function importData($table, $index, $rawData) {
		$this->initTCAData($table, $index);
		$this->handleData($rawData);

// Log results to devlog

		if ($this->extConf['debug'] || TYPO3_DLOG) {
			$this->logMessages();
		}
		return $this->messages;
	}

	/**
	 * This method receives raw data from some external source, transforms it and stores it into the local database
	 * It returns information about the results of the operation
	 *
	 * @param	mixed		$rawData: data in the format provided by the external source (XML string, PHP array, etc.)
	 * @return	void
	 */
	protected function handleData($rawData) {

// Prepare the data, depending on result type

		switch ($this->externalConfig['data']) {
			case 'xml':
				$records = $this->handleXML($rawData);
				break;
			case 'array':
				$records = $this->handleArray($rawData);
				break;
			default:
				$records = $rawData;
				break;
		}

// Apply any existing preprocessing hook to the raw data

		$records = $this->preprocessRawData($records);

// Transform data

		$records = $this->transformData($records);

// Apply any existing preprocessing hook to the transformed data

		$records = $this->preprocessData($records);

// Store data

		$this->storeData($records);

// Apply postprocessing

//		$this->postProcessing($records);
	}

	/**
	 * This method takes the data returned by the distant source as array and prepares it
	 * for update/insertion/deletion in the database
	 *
	 * @param	array		$rawData: response array
	 * @return	array		response stored as an indexed array of records (associative array of fields)
	 */
	protected function handleArray($rawData) {
		$data = array();

// Loop on all entries

		if (is_array($rawData) && count($rawData) > 0) {
			foreach ($rawData as $theRecord) {
				$theData = array();

// Loop on the database columns and get the corresponding value from the import data

				foreach ($this->tableTCA['columns'] as $columnName => $columnData) {
					if (isset($columnData['external'][$this->index]['field'])) {
						if (isset($theRecord[$columnData['external'][$this->index]['field']])) {
							$theData[$columnName] = $theRecord[$columnData['external'][$this->index]['field']];
						}
					}
				}

// Get additional fields data, if any

				if ($this->numAdditionalFields > 0) {
					foreach ($this->additionalFields as $fieldName) {
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

	/**
	 * This method takes the data returned by the distant source as XML and prepares it
	 * for update/insertion/deletion in the database
	 *
	 * @param	string		$rawData: response XML as a string
	 * @return	array		response stored as an indexed array of records (associative array of fields)
	 */
	protected function handleXML($rawData) {
		$data = array();

// Load the XML into a DOM object

		$dom = new DOMDocument();
		$dom->loadXML($rawData);

// Get the nodes that represent the root of each data record

		$records = $dom->getElementsByTagName($this->externalConfig['nodetype']);
		for ($i = 0; $i < $records->length; $i++) {
			$theRecord = $records->item($i);
			$theData = array();

// Loop on the database columns and get the corresponding value from the import data

			foreach ($this->tableTCA['columns'] as $columnName => $columnData) {
				if (isset($columnData['external'][$this->index]['field'])) {
					$node = $theRecord->getElementsByTagName($columnData['external'][$this->index]['field']);
					if ($node->length > 0) {
						$theData[$columnName] = $node->item(0)->nodeValue;
					}
				}
			}

// Get additional fields data, if any

			if ($this->numAdditionalFields > 0) {
				foreach ($this->additionalFields as $fieldName) {
					$node = $theRecord->getElementsByTagName($fieldName);
					if ($node->length > 0) {
						$theData[$fieldName] = $node->item(0)->nodeValue;
					}
				}
			}

			$data[] = $theData;
		}
		return $data;
	}

	/**
	 * This method applies any transformation necessary on the data
	 * Transformations are defined by mappings applied to one or more columns
	 *
	 * @param	array		$records: records containing the data
	 * @return	array		the transformed records
	 */
	protected function transformData($records) {
		$numRecords = count($records);

// Loop on all tables to find any defined transformations. This might be mappings and/or user functions

		foreach ($this->tableTCA['columns'] as $columnName => $columnData) {

				// Get existing mappings and apply them to records
			if (isset($columnData['external'][$this->index]['mapping'])) {
				$mappings = $this->getMapping($columnData['external'][$this->index]['mapping']);
				for ($i = 0; $i < $numRecords; $i++) {
					$externalValue = $records[$i][$columnName];
					if (isset($mappings[$externalValue])) {
						$records[$i][$columnName] = $mappings[$externalValue];
					}
					else {
						unset($records[$i][$columnName]);
					}
				}
			}
				// Otherwise apply constant value, if defined
			elseif (isset($columnData['external'][$this->index]['value'])) {
				for ($i = 0; $i < $numRecords; $i++) {
					$records[$i][$columnName] = $columnData['external'][$this->index]['value'];
				}
			}

// Apply defined user function

			if (isset($columnData['external'][$this->index]['userFunc'])) {
				// Try to get the referenced class
				$userObject = t3lib_div::getUserObj($columnData['external'][$this->index]['userFunc']['class']);
				// Could not instantiate the class, log error and do nothing
				if ($userObject === false) {
					if ($this->extConf['debug'] || TYPO3_DLOG) {
						t3lib_div::devLog(sprintf($GLOBALS['LANG']->getLL('invalid_userfunc'), $columnData['external'][$this->index]['userFunc']['class']), $this->extKey, 2, $columnData['external'][$this->index]['userFunc']);
					}
				}
				// Otherwise call referenced class on all records
				else {
					$methodName = $columnData['external'][$this->index]['userFunc']['method'];
					$parameters = isset($columnData['external'][$this->index]['userFunc']['params']) ? $columnData['external'][$this->index]['userFunc']['params'] : array();
					for ($i = 0; $i < $numRecords; $i++) {
						$records[$i][$columnName] = $userObject->$methodName($records[$i], $columnName, $parameters);
					}
				}
			}
		}
		return $records;
	}

	/**
	 * This method applies any existing pre-processing to the data just as it was fetched, before any transformation
	 * Note that this method does not do anything by itself. It just calls on a pre-processing hook
	 *
	 * @param	array		$records: records containing the data
	 * @return	array		the pre-processed records
	 */
	protected function preprocessRawData($records) {
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['preprocessRawRecordset'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['preprocessRawRecordset'] as $className) {
				$preProcessor = &t3lib_div::getUserObj($className);
				$records = $preProcessor->preprocessRawRecordset($records, $this);
			}
		}
		return $records;
	}

	/**
	 * This method applies any existing pre-processing to the data before it is stored (but after is has been transformed)
	 * Note that this method does not do anything by itself. It just calls on a pre-processing hook
	 *
	 * @param	array		$records: records containing the data
	 * @return	array		the pre-processed records
	 */
	protected function preprocessData($records) {
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['preprocessRecordset'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['preprocessRecordset'] as $className) {
				$preProcessor = &t3lib_div::getUserObj($className);
				$records = $preProcessor->preprocessRecordset($records, $this);
			}
		}
		return $records;
	}

	/**
	 * This method stores the imported data in the database
	 * New data is inserted, existing data is updated and absent data is deleted
	 *
	 * @param	array		$records: records containing the data
	 * @return	void
	 */
	protected function storeData($records) {
//		if ($this->extConf['debug'] || TYPO3_DLOG) t3lib_div::devLog('Data received for storage', $this->extKey, 0, $records);
		$errors = 0;

// Get the list of existing uids for the table

		$existingUids = $this->getExistingUids();

// Check which columns are MM-relations and get mappings to foreign tables for each
// NOTE: as it is now, it is assumed that the imported data is denormalised

// There's more to do than that:
//
// 1.	a sorting field may have been defined, but TCEmain assumes the MM-relations are in the right order
//		and inserts its own number for the table's sorting field. So MM-relations must be sorted before executing TCEmain.
// 2.a	it is possible to store additional fields in the MM-relations. This is not TYPO3-standard, so TCEmain will
//		not be able to handle it. We thus need to store all that data now and rework the MM-relations when TCEmain is done.
// 2.b	if a pair of records is related to each other several times (because the additional fields vary), this will be filtered out
//		by TCEmain. So we must preserve also these additional relations.

		$mappings = array();
		$fullMappings = array();
		foreach ($this->tableTCA['columns'] as $columnName => $columnData) {
			if (isset($columnData['external'][$this->index]['MM'])) {
				$mmData = $columnData['external'][$this->index]['MM'];
				$sortingField = (isset($mmData['sorting'])) ? $mmData['sorting'] : false;
				$additionalFields = (isset($mmData['additional_fields'])) ? $mmData['additional_fields'] : false;

				$mappings[$columnName] = array();
				if ($additionalFields || $mmData['multiple']) {
					$fullMappings[$columnName] = array();
				}

// Get foreign mapping for column

				if (!isset($mmData['mapping']['value'])) {
					$mappingInformation = isset($mmData['mappings']['uid_foreign']) ? $mmData['mappings']['uid_foreign'] : $mmData['mapping'];
					$foreignMappings = $this->getMapping($mappingInformation);
				}

// Go through each record and assemble pairs of primary and foreign keys

				foreach ($records as $theRecord) {
					$externalUid = $theRecord[$this->externalConfig['reference_uid']];
					if (isset($mmData['mapping']['value'])) {
						$foreignValue = $mmData['mapping']['value'];
					}
					elseif (isset($foreignMappings[$theRecord[$columnName]])) {
						$foreignValue = $foreignMappings[$theRecord[$columnName]];
					}
					if (isset($foreignValue)) {
						if (!isset($mappings[$columnName][$externalUid])) {
							$mappings[$columnName][$externalUid] = array();
							// Initialise only if necessary
							if ($additionalFields || $mmData['multiple']) {
								$fullMappings[$columnName][$externalUid] = array();
							}
						}

// If additional fields are defined, store those values in an intermediate array

						if ($additionalFields) {
							$fields = array();
							foreach ($mmData['additional_fields'] as $localFieldName => $externalFieldName) {
								$fields[$localFieldName] = $theRecord[$externalFieldName];
							}
						}

// If a sorting field is defined, use that value for indexing, otherwise just add the element at the end of the array

						if ($sortingField) {
							$sortingValue = $theRecord[$sortingField];
							$mappings[$columnName][$externalUid][$sortingValue] =  $foreignValue;
							if ($additionalFields || $mmData['multiple']) {
								$fullMappings[$columnName][$externalUid][$sortingValue] = array(
									'value' => $foreignValue,
									'additional_fields' => $fields
								);
							}
						}
						else {
							$mappings[$columnName][$externalUid][] =  $foreignValue;
							if ($additionalFields || $mmData['multiple']) {
								$fullMappings[$columnName][$externalUid][] = array(
									'value' => $foreignValue,
									'additional_fields' => $fields
								);
							}
						}
					}
				}

// If there was some special sorting to do, do it now

				if ($sortingField) {
					foreach ($mappings as $columnName => $columnMappings) {
						foreach ($columnMappings as $uid => $values) {
							ksort($values);
							$mappings[$columnName][$uid] = $values;

// Do the same for extended MM-relations, if necessary

							if ($additionalFields || $mmData['multiple']) {
								$fullValues = $fullMappings[$columnName][$uid];
								ksort($fullValues);
								$fullMappings[$columnName][$uid] = $fullValues;
							}
						}
					}
				}
			}
		}
		$hasMMRelations = count($mappings);

// Insert or update records depending on existing uids

		$updates = 0;
		$inserts = 0;
		$deletes = 0;
		$updatedUids = array();
		$handledUids = array();
		$tceData = array($this->table => array());
		$savedAdditionalFields = array();
		foreach ($records as $theRecord) {
			$localAdditionalFields = array();
			$externalUid = $theRecord[$this->externalConfig['reference_uid']];
			if (in_array($externalUid, $handledUids)) continue; // Skip handling of already handled records (this can happen with denormalised structures)
			$handledUids[] = $externalUid;

				// Prepare MM-fields, if any
			if ($hasMMRelations) {
				foreach ($mappings as $columnName => $columnMappings) {
					if (isset($columnMappings[$externalUid])) {
						$theRecord[$columnName] = implode(',', $columnMappings[$externalUid]);
					}
				}
			}

				// Remove additional fields data, if any. They must not be saved to database
				// They are saved locally however, for later use
			if ($this->numAdditionalFields > 0) {
				foreach ($this->additionalFields as $fieldName) {
					$localAdditionalFields[$fieldName] = $theRecord[$fieldName];
					unset($theRecord[$fieldName]);
				}
			}

			$theID = '';
				// Reference uid is found, perform an update (if not disabled)
			if (isset($existingUids[$externalUid]) && !t3lib_div::inList($this->externalConfig['disabledOperations'], 'update')) {
					// First call a preprocessing hook
				if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['updatePreProcess'])) {
					foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['updatePreProcess'] as $className) {
						$preProcessor = &t3lib_div::getUserObj($className);
						$theRecord = $preProcessor->processBeforeUpdate($theRecord, $this);
					}
				}
				$theID = $existingUids[$externalUid];

				$tceData[$this->table][$theID] = $theRecord;
				$updatedUids[] = $theID;
				$updates++;
			}

				// Reference uid not found, perform an insert (if not disabled)
			elseif (!t3lib_div::inList($this->externalConfig['disabledOperations'], 'insert')) {
					// First call a preprocessing hook
				if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['insertPreProcess'])) {
					foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['insertPreProcess'] as $className) {
						$preProcessor = &t3lib_div::getUserObj($className);
						$theRecord = $preProcessor->processBeforeInsert($theRecord, $this);
					}
				}
				$theRecord['pid'] = $this->pid;
				$inserts++;
				$theID = 'NEW_' . $inserts;
				$tceData[$this->table][$theID] = $theRecord;
			}
				// Store local additional fields into general additional fields array
				// keyed to proper id's (if the record was processed)
			if (!empty($theID)) {
				$savedAdditionalFields[$theID] = $localAdditionalFields;
			}
		}
		if ($this->extConf['debug'] || TYPO3_DLOG) t3lib_div::devLog('TCEmain data', $this->extKey, 0, $tceData);
		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$tce->stripslashes_values = 0;
		$tce->start($tceData, array());
		$tce->process_datamap();
		if ($this->extConf['debug'] || TYPO3_DLOG) t3lib_div::devLog('New IDs', 'external_import', 0, $tce->substNEWwithIDs);
			// Store the number of new IDs created. This is used in error reporting later
		$numberOfNewIDs = count($tce->substNEWwithIDs);

			// Post-processing hook after data was saved
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['datamapPostProcess'])) {
			$savedData = array();
			foreach ($tceData as $table => $tableRecords) {
				foreach ($tableRecords as $id => $record) {
						// Added status to record
						// If operation was insert, match placeholder to actual id
					if (isset($tce->substNEWwithIDs[$id])) {
						$uid = $tce->substNEWwithIDs[$id];
						$record['tx_externalimport:status'] = 'insert';
					} else {
						$uid = $id;
						$record['tx_externalimport:status'] = 'update';
					}
						// Restore additional fields, if any
					if ($this->numAdditionalFields > 0) {
						foreach ($savedAdditionalFields[$id] as $fieldName => $fieldValue) {
							$record[$fieldName] = $fieldValue;
						}
					}
					$savedData[$uid] = $record;
				}
			}
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['datamapPostProcess'] as $className) {
				$postProcessor = &t3lib_div::getUserObj($className);
				$postProcessor->datamapPostProcess($this->table, $savedData, $this);
			}
		}
			// Clean up
		unset($tceData);
		unset($savedData);

			// Mark as deleted records with existing uids that were not in the import data anymore (if automatic delete is activated)
		if (t3lib_div::inList($this->externalConfig['disabledOperations'], 'delete') || (isset($this->externalConfig['deleteNonSynchedRecords']) && $this->externalConfig['deleteNonSynchedRecords'] === false)) {
			$deletes = 0;
		}
		else {
			$absentUids = array_diff($existingUids, $updatedUids);
				// Call a preprocessing hook
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['deletePreProcess'])) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['deletePreProcess'] as $className) {
					$preProcessor = &t3lib_div::getUserObj($className);
					$absentUids = $preProcessor->processBeforeDelete($this->table, $absentUids, $this);
				}
			}
			$deletes = count($absentUids);
			if ($deletes > 0) {
				$tceCommands = array($this->table => array());
				foreach ($absentUids as $id) {
					$tceCommands[$this->table][$id] = array('delete' => 1);
				}
				if ($this->extConf['debug'] || TYPO3_DLOG) t3lib_div::devLog('TCEmain commands', $this->extKey, 0, $tceCommands);
				$tce->start(array(), $tceCommands);
				$tce->process_cmdmap();
					// Call a postprocessing hook
				if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['cmdmapPostProcess'])) {
					foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['cmdmapPostProcess'] as $className) {
						$postProcessor = &t3lib_div::getUserObj($className);
						$absentUids = $postProcessor->cmdmapPostProcess($this->table, $absentUids, $this);
					}
				}
			}
		}

// Perform post-processing of MM-relations if necessary

		if (count($fullMappings) > 0) {
			if ($this->extConf['debug'] || TYPO3_DLOG) t3lib_div::devLog('Handling full mappings', $this->extKey, 0, $fullMappings);

// Refresh list of existing primary keys now that new records have been inserted

			$existingUids = $this->getExistingUids();

// Loop on all columns that require a remapping

			foreach ($fullMappings as $columnName => $mappingData) {
				$mmTable = $this->tableTCA['columns'][$columnName]['config']['MM'];
				foreach ($mappingData as $externalUid => $sortedData) {
					$uid = $existingUids[$externalUid];

// Delete existing MM-relations for current uid

					$GLOBALS['TYPO3_DB']->exec_DELETEquery($mmTable, "uid_local = '$uid'");

// Recreate all MM-relations with additional fields, if any

					$counter = 0;
					foreach ($sortedData as $mmData) {
						$counter++;
						$fields = $mmData['additional_fields'];
						$fields['uid_local'] = $uid;
						$fields['uid_foreign'] = $mmData['value'];
						$fields['sorting'] = $counter;
						$result = $GLOBALS['TYPO3_DB']->exec_INSERTquery($mmTable, $fields);
					}
				}
			}
		}

			// Check if there were any errors reported by TCEmain
		if (count($tce->errorLog) > 0) {
				// If yes, get these messages from the sys_log table
			$where = "tablename = '" . $this->table . "' AND error > '0'";
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'sys_log', $where, '', 'tstamp DESC', count($tce->errorLog));
			if ($res) {
				while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
						// Check if there's a label for the message
					$labelCode = 'msg_'. $row['type'] . '_' . $row['action'] . '_' . $row['details_nr'];
					$label = $GLOBALS['LANG']->sL('LLL:EXT:belog/mod/locallang.xml:' . $labelCode);
						// If not, use details field
					if (empty($label)) {
						$label = $row['details'];
					}
						// Substitute the first 5 items of extra data into the error message
					if (!empty($row['log_data'])) {
						$data = unserialize($row['log_data']);
						$message = sprintf($label, htmlspecialchars($data[0]), htmlspecialchars($data[1]), htmlspecialchars($data[2]), htmlspecialchars($data[3]), htmlspecialchars($data[4]));
					}
					else {
						$message = $label;
					}
					$this->messages['error'][] = $message;
					if ($this->extConf['debug'] || TYPO3_DLOG) t3lib_div::devLog($message, $this->extKey, 3);
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($res);
			}
				// Substract the number of new IDs from the number of inserts,
				// to get a realistic number of new records
			$inserts = $inserts + ($numberOfNewIDs - $inserts);
				// Add a warning that numbers reported (below) may not be accurate
			$this->messages['warning'][] = $GLOBALS['LANG']->getLL('things_happened');
		}

			// Set informational messages
		$this->messages['success'][] = sprintf($GLOBALS['LANG']->getLL('records_inserted'), $inserts);
		$this->messages['success'][] = sprintf($GLOBALS['LANG']->getLL('records_updated'), $updates);
		$this->messages['success'][] = sprintf($GLOBALS['LANG']->getLL('records_deleted'), $deletes);
	}

	/**
	 * Utility method to get a list of all existing primary keys in the table being synchronised
	 *
	 * @return	array		Hash table of all external primary keys matched to internal primary keys
	 */
	protected function getExistingUids() {
		$existingUids = array();
		if ($this->externalConfig['enforcePid']) {
			$where = "pid = '" . $this->pid . "'";
		}
		else {
			$where = '1 = 1';
		}
		$where .= t3lib_BEfunc::deleteClause($this->table);
		$db = $GLOBALS['TYPO3_DB']->exec_SELECTquery($this->externalConfig['reference_uid'] . ',uid', $this->table, $where);
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($db)) {
			$existingUids[$row[$this->externalConfig['reference_uid']]] = $row['uid'];
		}
		return $existingUids;
	}

	/**
	 * Utility method used to retrieve a single mapping
	 *
	 * @param	array		$mappingData: data for assemble a mapping of fields
	 * @return	array		hash table for mapping
	 */
	protected function getMapping($mappingData) {
		$localMapping = array();

			// Check if there's a fixed value map
		if (isset($mappingData['valueMap'])) {
				// Use value map directly
			$localMapping = $mappingData['valueMap'];
		}
		else {
				// Assemble query and get data
			if (isset($mappingData['value_field'])) {
				$valueField = $mappingData['value_field'];
			}
			else {
				$valueField = 'uid';
			}
			$fields = $mappingData['reference_field'] . ', ' . $valueField;
			$db = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields, $mappingData['table'], '1 = 1' . t3lib_BEfunc::deleteClause($mappingData['table']));

				// Fill hash table
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($db)) {
				$localMapping[$row[$mappingData['reference_field']]] = $row[$valueField];
			}
		}
		return $localMapping;
	}

	/**
	 * This method stores the error or success messages into the devLog
	 *
	 * @return	void
	 */
	protected function logMessages() {

// Define severity based on types of messages

		if (count($this->messages['error']) > 0) {
			$severity = 3;
		}
		elseif (count($this->messages['warning']) > 0) {
			$severity = 2;
		}
		else {
			$severity = -1;
		}
		if ($this->extConf['debug'] || TYPO3_DLOG) t3lib_div::devLog(sprintf($GLOBALS['LANG']->getLL('sync_table'), $this->table), $this->extKey, $severity, $this->messages);
	}

// Getters and setters

	/**
	 * This method returns the name of the table being synchronised
	 *
	 * @return	string		Name of the table
	 */
	public function getTableName() {
		return $this->table;
	}

	/**
	 * This method returns the index of the configuration used in the current synchronisation
	 *
	 * @return	integer		The index
	 */
	public function getIndex() {
		return $this->index;
	}

	/**
	 * This method returns the external configuration found in the ctrl section of the TCA
	 * of the table being synchronised
	 *
	 * @return	array		External configuration from the TCA ctrl section
	 */
	public function getExternalConfig() {
		return $this->externalConfig;
	}

	/**
	 * This method is used to add a message to the message queue that will be returned
	 * when the synchronisation is complete
	 *
	 * @param	string		$text: the message itself
	 * @param	string		$status: status of the message. Expected is "success", "warning" or "error"
	 * @return	void
	 */
	public function addMessage($text, $status = 'error') {
		if (!empty($text)) {
			$this->messages[$status][] = $text;
		}
	}
}
?>