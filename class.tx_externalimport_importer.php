<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2014 Francois Suter (Cobweb) <typo3@cobweb.ch>
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
***************************************************************/

/**
 * This class performs the data update from the external sources
 *
 * @author		Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package		TYPO3
 * @subpackage	tx_externalimport
 */
class tx_externalimport_importer {
	public    $extKey = 'external_import';
	protected $vars = array(); // Variables from the query string (TODO: remove, unused)
	protected $extConf = array(); // Extension configuration
	protected $messages = array(); // List of result messages
	protected $table; // Name of the table being synchronised
	/**
	 * @var mixed Index of the synchronisation configuration in use
	 */
	protected $index;
	/**
	 * @var mixed Index for the columns, may be different from $this->index
	 */
	protected $columnIndex;
	protected $tableTCA; // TCA of the table being synchronised
	protected $externalConfig; // Ctrl-section external config being used for synchronisation
	protected $pid = 0; // uid of the page where the records will be stored
	protected $additionalFields = array(); // List of fields to import, but not to save
	protected $numAdditionalFields = 0; // Number of such fields
	/**
	 * @var array $temporaryKeys list of temporary keys created on the fly for new records. Used in TCEmain data map.
	 */
	protected $temporaryKeys = array();
	/**
	 * @var int $newKeysCounter simple counter for generating the temporary keys
	 */
	protected $newKeysCounter = 0;

	/**
	 * This is the constructor
	 * It initialises some properties and makes sure that a lang object is available
	 *
	 * @return tx_externalimport_importer object
	 */
	public function __construct() {
		$this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extKey]);
		$this->messages = array(t3lib_FlashMessage::ERROR => array(), t3lib_FlashMessage::WARNING => array(), t3lib_FlashMessage::OK => array());

			// Make sure we have a language object
			// If initialised, use existing, if not, initialise it
		if (!isset($GLOBALS['LANG'])) {
			require_once(PATH_typo3 . 'sysext/lang/lang.php');
			$GLOBALS['LANG'] = t3lib_div::makeInstance('language');
			$GLOBALS['LANG']->init($GLOBALS['BE_USER']->uc['lang']);
		}
		$GLOBALS['LANG']->includeLLFile('EXT:' . $this->extKey . '/locallang.xml');

			// Force PHP limit execution time if set
		if (isset($this->extConf['timelimit']) && ($this->extConf['timelimit'] > -1)) {
			set_time_limit($this->extConf['timelimit']);
			if ($this->extConf['debug'] || TYPO3_DLOG) {
				t3lib_div::devLog($GLOBALS['LANG']->getLL('timelimit'), $this->extKey, 0, $this->extConf['timelimit']);
			}
		}
	}

	/**
	 * This method synchronises all the external tables, respecting the order of priority
	 *
	 * @return array List of all messages
	 */
	public function synchronizeAllTables() {

			// Look in the TCA for tables with an "external" control section and a "connector"
			// Tables without connectors cannot be synchronised
		$externalTables = array();
		foreach ($GLOBALS['TCA'] as $tableName => $sections) {
			if (isset($sections['ctrl']['external'])) {
				foreach ($sections['ctrl']['external'] as $index => $externalConfig) {
					if (!empty($externalConfig['connector'])) {
							// Default priority if not defined, set to very low
						$priority = 1000;
						if (isset($externalConfig['priority'])) {
							$priority = $externalConfig['priority'];
						}
						if (!isset($externalTables[$priority])) $externalTables[$priority] = array();
						$externalTables[$priority][] = array('table' => $tableName, 'index' => $index);
					}
				}
			}
		}

			// Sort tables by priority (lower number is highest priority)
		ksort($externalTables);
		if ($this->extConf['debug'] || TYPO3_DLOG) {
			t3lib_div::devLog($GLOBALS['LANG']->getLL('sync_all'), $this->extKey, 0, $externalTables);
		}

			// Synchronise all tables
		$allMessages = array();
		foreach ($externalTables as $tables) {
			foreach ($tables as $tableData) {
				$this->messages = array(t3lib_FlashMessage::ERROR => array(), t3lib_FlashMessage::WARNING => array(), t3lib_FlashMessage::OK => array()); // Reset error messages array
				$messages = $this->synchronizeData($tableData['table'], $tableData['index']);
				$key = $tableData['table'] . '/' .$tableData['index'];
				$allMessages[$key] = $messages;
			}
		}

			// Return compiled array of messages for all imports
		return $allMessages;
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
		// Set the pid where the records will be stored
		// This is either specific for the given table or generic from the extension configuration
		if (isset($this->externalConfig['pid'])) {
			$this->pid = $this->externalConfig['pid'];
		} else {
			$this->pid = $this->extConf['storagePID'];
		}
		// Sets the column configuration index (may differ from main one)
		if (isset($this->externalConfig['useColumnIndex'])) {
			$this->columnIndex = $this->externalConfig['useColumnIndex'];
		} else {
			$this->columnIndex = $index;
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
			// If the user has enough rights on the table, proceed with synchronization
		if ($GLOBALS['BE_USER']->check('tables_modify', $table)) {
			$this->initTCAData($table, $index);

				// Instantiate specific connector service
			if (empty($this->externalConfig['connector'])) {
				$this->addMessage(
					$GLOBALS['LANG']->getLL('no_connector')
				);
			} else {
				$services = t3lib_extMgm::findService('connector', $this->externalConfig['connector']);

					// The service is not available
				if ($services === FALSE) {
					$this->addMessage(
						$GLOBALS['LANG']->getLL('no_service')
					);
				} else {
						/** @var $connector tx_svconnector_base */
					$connector = t3lib_div::makeInstanceService('connector', $this->externalConfig['connector']);

						// The service was instantiated, but an error occurred while initiating the connection
						// If the returned value is an array, an error has occurred
					if (is_array($connector)) {
						$this->addMessage(
							$GLOBALS['LANG']->getLL('data_not_fetched')
						);

					// The connection is established, get the data
					} else {
						$data = array();

						// Pre-process connector parameters
						$this->externalConfig['parameters'] = $this->processParameters($this->externalConfig['parameters']);

						// A problem may happen while fetching the data
						// If so, the import process has to be aborted
						$abortImportProcess = FALSE;
						switch ($this->externalConfig['data']) {
							case 'xml':
								try {
									$data = $connector->fetchXML($this->externalConfig['parameters']);
								}
								catch (Exception $e) {
									$abortImportProcess = TRUE;
									$this->addMessage(
										sprintf($GLOBALS['LANG']->getLL('data_not_fetched_connector_error'), $e->getMessage())
									);
								}
								break;

							case 'array':
								try {
									$data = $connector->fetchArray($this->externalConfig['parameters']);
								}
								catch (Exception $e) {
									$abortImportProcess = TRUE;
									$this->addMessage(
										sprintf($GLOBALS['LANG']->getLL('data_not_fetched_connector_error'), $e->getMessage())
									);
								}
								break;

								// If the data type is not defined, issue error and abort process
							default:
								$abortImportProcess = TRUE;
								$this->addMessage(
									$GLOBALS['LANG']->getLL('data_type_not_defined')
								);
								break;
						}
							// Continue, if the process was not marked as aborted
						if (!$abortImportProcess) {
							if ($this->extConf['debug'] || TYPO3_DLOG) {
								$debugData = $this->prepareDataSample($data);
								t3lib_div::devLog('Data received (sample)', $this->extKey, -1, $debugData);
							}
							$this->handleData($data);
						}
							// Call connector's post-processing with a rough error status
						$errorStatus = FALSE;
						if (count($this->messages[t3lib_FlashMessage::ERROR]) > 0) {
							$errorStatus = TRUE;
						}
						$connector->postProcessOperations($this->externalConfig['parameters'], $errorStatus);
					}
				}
			}

			// The user doesn't have enough rights on the table
			// Log error
		} else {
			$userName = $GLOBALS['BE_USER']->user['username'];
			$this->addMessage(
				sprintf($GLOBALS['LANG']->getLL('no_rights_for_sync'), $userName, $table)
			);
		}

			// Log results to devlog
		if ($this->extConf['debug'] || TYPO3_DLOG) {
			$this->logMessages();
		}

		return $this->messages;
	}

	/**
	 * Pre-processes the configured connector parameters.
	 *
	 * @param array $parameters List of parameters to process
	 * @return array The processed parameters
	 */
	protected function processParameters($parameters) {
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['processParameters'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['processParameters'] as $className) {
				$preProcessor = t3lib_div::getUserObj($className);
				$parameters = $preProcessor->processParameters($parameters, $this);
			}
		}
		return $parameters;
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
	 * This method prepares a sample from the data to import, based on the preview limit
	 * The process applied for this depends on the data type (array or XML)
	 *
	 * @param	mixed	$data: the input data as a XML string or a PHP array
	 * @return	array	The data sample, in same format as input (but written inside an array in case of XML data)
	 */
	protected function prepareDataSample($data) {
		$dataSample	= $data;
		if (!empty($this->extConf['previewLimit'])) {
			switch ($this->externalConfig['data']) {
				case 'xml':

					// Load the XML into a DOM object
					$dom = new DOMDocument();
					$dom->loadXML($data, LIBXML_PARSEHUGE);
					// Prepare an empty DOM object for the sample data
					$domSample = new DOMDocument();
					// Define a root node
					$element = $domSample->createElement('sample');
					$domSample->appendChild($element);
					// Get the desired nodes
					$selectedNodes = $dom->getElementsByTagName($this->externalConfig['nodetype']);
					// Loop until the preview limit and import selected nodes into the sample XML object
					$loopLimit = min($selectedNodes->length, $this->extConf['previewLimit']);
					for ($i = 0; $i < $loopLimit; $i++) {
						$newNode = $domSample->importNode($selectedNodes->item($i), TRUE);
						$domSample->documentElement->appendChild($newNode);
					}
					// Store the XML sample in an array, to have a common return format
					$dataSample = array();
					$dataSample[] = $domSample->saveXML();
					break;
				case 'array':
					$dataSample = array_slice($data, 0, $this->extConf['previewLimit']);
					break;
			}
		}
		return $dataSample;
	}

	/**
	 * Receives raw data from some external source, transforms it and stores it into the local database
	 *
	 * Returns information about the results of the operation
	 *
	 * @param mixed $rawData Data in the format provided by the external source (XML string, PHP array, etc.)
	 * @return void
	 */
	protected function handleData($rawData) {
			// Check for custom data handlers
		if (!empty($this->externalConfig['dataHandler'])) {
				/** @var $dataHandler tx_externalimport_dataHandler */
			$dataHandler = t3lib_div::makeInstance($this->externalConfig['dataHandler']);
			if ($dataHandler instanceof tx_externalimport_dataHandler) {
				$records = $dataHandler->handleData($rawData, $this);

				// If the data handler is not appropriate, keep the raw data
			} else {
				$records = $rawData;
			}

			// Use default handlers
		} else {

				// Prepare the data, depending on result type
			switch ($this->externalConfig['data']) {
				case 'xml':
					$records = $this->handleXML($rawData);
					break;
				case 'array':
					$records = $this->handleArray($rawData);
					break;

					// This should really not happen
				default:
					$records = $rawData;
					break;
			}
		}

			// Apply any existing pre-processing hook to the raw data
		$records = $this->preprocessRawData($records);

			// Check the raw data to see if import process should continue
		$continueImport = $this->validateRawData($records);

			// If raw data was judged valid, continue with import
		if ($continueImport) {
				// Transform data
			$records = $this->transformData($records);

				// Apply any existing pre-processing hook to the transformed data
			$records = $this->preprocessData($records);

				// Store data
			$this->storeData($records);

				// Clear cache
			$this->clearCache();

			// Import was aborted, issue warning message
		} else {
			$this->addMessage($GLOBALS['LANG']->getLL('importAborted'), t3lib_FlashMessage::WARNING);
		}
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
					if (isset($columnData['external'][$this->columnIndex]['field'])) {
						if (isset($theRecord[$columnData['external'][$this->columnIndex]['field']])) {
							$theData[$columnName] = $theRecord[$columnData['external'][$this->columnIndex]['field']];
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
		$dom->loadXML($rawData, LIBXML_PARSEHUGE);
		// Instantiate a XPath object and load with any defined namespaces
		$xPathObject = new DOMXPath($dom);
		if (isset($this->externalConfig['namespaces']) && is_array($this->externalConfig['namespaces'])) {
			foreach ($this->externalConfig['namespaces'] as $prefix => $uri) {
				$xPathObject->registerNamespace($prefix, $uri);
			}
		}

			// Get the nodes that represent the root of each data record
		$records = $dom->getElementsByTagName($this->externalConfig['nodetype']);
		for ($i = 0; $i < $records->length; $i++) {
			/** @var DOMElement $theRecord */
			$theRecord = $records->item($i);
			$theData = array();

				// Loop on the database columns and get the corresponding value from the import data
			foreach ($this->tableTCA['columns'] as $columnName => $columnData) {
					// Act only if there's an external import definition
				if (isset($columnData['external'][$this->columnIndex])) {
						// If a "field" is defined, refine the selection to get the correct node
					if (isset($columnData['external'][$this->columnIndex]['field'])) {
							// Use namespace or not
						if (empty($columnData['external'][$this->columnIndex]['fieldNS'])) {
							$nodeList = $theRecord->getElementsByTagName($columnData['external'][$this->columnIndex]['field']);
						} else {
							$nodeList = $theRecord->getElementsByTagNameNS($columnData['external'][$this->columnIndex]['fieldNS'], $columnData['external'][$this->columnIndex]['field']);
						}

						if ($nodeList->length > 0) {
							/** @var $selectedNode DOMNode */
							$selectedNode = $nodeList->item(0);
							// If an XPath expression is defined, apply it (relative to currently selected node)
							if (!empty($columnData['external'][$this->columnIndex]['xpath'])) {
								try {
									$selectedNode = $this->selectNodeWithXpath(
										$xPathObject,
										$columnData['external'][$this->columnIndex]['xpath'],
										$selectedNode
									);
								}
								catch (Exception $e) {
									// Nothing to do, data is ignored
								}
							}
							$theData[$columnName] = $this->extractValueFromNode(
								$selectedNode,
								$columnData['external'][$this->columnIndex]
							);
						}

					// Without "field" property, use the current node itself
					} else {
						// If an XPath expression is defined, apply it (relative to current node)
						if (!empty($columnData['external'][$this->columnIndex]['xpath'])) {
							try {
								$selectedNode = $this->selectNodeWithXpath(
									$xPathObject,
									$columnData['external'][$this->columnIndex]['xpath'],
									$theRecord
								);
								$theData[$columnName] = $this->extractValueFromNode(
									$selectedNode,
									$columnData['external'][$this->columnIndex]
								);
							}
							catch (Exception $e) {
								// Nothing to do, data is ignored
							}
						} else {
							$theData[$columnName] = $this->extractValueFromNode(
								$theRecord,
								$columnData['external'][$this->columnIndex]
							);
						}
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

			if (count($theData) > 0) {
				$data[] = $theData;
			}
		}
		return $data;
	}

	/**
	 * Extracts either an attribute from a XML node or the value of the node itself,
	 * based on the configuration received.
	 *
	 * @param DOMNode $node Currently handled XML node
	 * @param array $columnData Handling information for the XML node
	 * @return mixed The extracted value
	 */
	protected function extractValueFromNode($node, $columnData) {
			// Get the named attribute, if defined
		if (!empty($columnData['attribute'])) {
				// Use namespace or not
			if (empty($columnData['attributeNS'])) {
				$value = $node->attributes->getNamedItem($columnData['attribute'])->nodeValue;
			} else {
				$value = $node->attributes->getNamedItemNS($columnData['attributeNS'], $columnData['attribute'])->nodeValue;
			}

		// Otherwise directly take the node's value
		} else {
			// If "xmlValue" is set, we want the node's inner XML structure as is.
			// Otherwise, we take the straight node value, which is similar but with tags stripped.
			if (empty($columnData['xmlValue'])) {
				$value = $node->nodeValue;
			} else {
				$value = $this->getXmlValue($node);
			}
		}
		return $value;
	}

	/**
	 * Extracts the value of the node as structured XML.
	 *
	 * @param DOMNode $node Currently handled XML node
	 * @throws Exception
	 * @return string Code inside the node
	 */

	protected function getXmlValue($node) {
		$innerHTML = '';
		$children = $node->childNodes;
		foreach ($children as $child) {
			$innerHTML .= $child->ownerDocument->saveXML($child);
		}
		return $innerHTML;
	}

	/**
	 * Queries the current structure with an XPath query.
	 *
	 * @param DOMXPath $xPathObject Instantiated DOMXPath object
	 * @param string $xPath XPath query to evaluate
	 * @param DOMNode $context Node giving the context of the XPath query
	 * @return DOMElement First node found
	 * @throws Exception
	 */
	protected function selectNodeWithXpath($xPathObject, $xPath, $context) {
		$resultNodes = $xPathObject->evaluate($xPath, $context);
		if ($resultNodes->length > 0) {
			return $resultNodes->item(0);
		} else {
			throw new Exception('No node found with xPath: ' . $xPath, 1399497464);
		}
	}

	/**
	 * This method applies any transformation necessary on the data
	 * Transformations are defined by mappings or custom functions
	 * applied to one or more columns
	 *
	 * @param	array		$records: records containing the data
	 * @return	array		the transformed records
	 */
	protected function transformData($records) {
		$numRecords = count($records);

			// Loop on all tables to find any defined transformations. This might be mappings and/or user functions
		foreach ($this->tableTCA['columns'] as $columnName => $columnData) {
				// If the column's content must be trimmed, apply trim to all records
			if (!empty($columnData['external'][$this->columnIndex]['trim'])) {
				for ($i = 0; $i < $numRecords; $i++) {
					$records[$i][$columnName] = trim($records[$i][$columnName]);
				}
			}

				// Get existing mappings and apply them to records
			if (isset($columnData['external'][$this->columnIndex]['mapping'])) {
				$records = $this->mapData($records, $columnName, $columnData['external'][$this->columnIndex]['mapping']);

				// Otherwise apply constant value, if defined
			} elseif (isset($columnData['external'][$this->columnIndex]['value'])) {
				for ($i = 0; $i < $numRecords; $i++) {
					$records[$i][$columnName] = $columnData['external'][$this->columnIndex]['value'];
				}
			}

				// Add field for RTE transformation to each record, if column has RTE enabled
			if (!empty($columnData['external'][$this->columnIndex]['rteEnabled'])) {
				for ($i = 0; $i < $numRecords; $i++) {
					$records[$i]['_TRANSFORM_' . $columnName] = 'RTE';
				}
			}

				// Apply defined user function
			if (isset($columnData['external'][$this->columnIndex]['userFunc'])) {
					// Try to get the referenced class
				$userObject = t3lib_div::getUserObj($columnData['external'][$this->columnIndex]['userFunc']['class']);
					// Could not instantiate the class, log error and do nothing
				if ($userObject === FALSE) {
					if ($this->extConf['debug'] || TYPO3_DLOG) {
						t3lib_div::devLog(
							sprintf(
								$GLOBALS['LANG']->getLL('invalid_userfunc'),
								$columnData['external'][$this->columnIndex]['userFunc']['class']
							),
							$this->extKey,
							2,
							$columnData['external'][$this->columnIndex]['userFunc']
						);
					}

					// Otherwise call referenced class on all records
				} else {
					$methodName = $columnData['external'][$this->columnIndex]['userFunc']['method'];
					$parameters = isset($columnData['external'][$this->columnIndex]['userFunc']['params']) ? $columnData['external'][$this->columnIndex]['userFunc']['params'] : array();
					for ($i = 0; $i < $numRecords; $i++) {
						$records[$i][$columnName] = $userObject->$methodName($records[$i], $columnName, $parameters);
					}
				}
			}
		}
		return $records;
	}

	/**
	 * This method takes the records and applies a mapping to a selected column
	 *
	 * @param	array	$records: original records to handle
	 * @param	string	$columnName: name of the column whose values must be mapped
	 * @param	array	$mappingInformation: mapping configuration
	 * @return	array	The records with the mapped values
	 */
	protected function mapData($records, $columnName, $mappingInformation) {
		$mappings = $this->getMapping($mappingInformation);
		$numRecords = count($records);
		// If no particular matching method is defined, match exactly on the keys of the mapping table
		if (empty($mappingInformation['match_method'])) {
			// Determine if mapping is self-referential
			// Self-referential mappings cause a problem, because they may refer to a record that is not yet
			// in the database, but is part of the import. In this case we need to create a temporary ID for that
			// record and store it in order to reuse it when assembling the TCEmain data map (in storeData()).
			$isSelfReferencing = FALSE;
			if ($mappingInformation['table'] == $this->table) {
				$isSelfReferencing = TRUE;
			}

			for ($i = 0; $i < $numRecords; $i++) {
				$externalValue = $records[$i][$columnName];
				// If the external value is empty, don't even try to map it. Otherwise, proceed.
				if (empty($externalValue)) {
					unset($records[$i][$columnName]);
				} else {
					// The external field may contain multiple values
					if (!empty($mappingInformation['multipleValuesSeparator'])) {
						$singleExternalValues = t3lib_div::trimExplode(
							$mappingInformation['multipleValuesSeparator'],
							$externalValue,
							TRUE
						);

					// The external field is expected to contain a single value
					} else {
						$singleExternalValues = array($externalValue);
					}
					// Loop on all values and try to map them
					$mappedExternalValues = array();
					foreach ($singleExternalValues as $singleValue) {

						// Value is matched in the available mapping
						if (isset($mappings[$singleValue])) {
							$mappedExternalValues[] = $mappings[$singleValue];

						// Value is not matched, maybe it matches a temporary key, if self-referential
						} else {
							// If the relation is self-referential, use a temporary key
							if ($isSelfReferencing) {
								// Check if a temporary key was already created for that external key
								if (isset($this->temporaryKeys[$singleValue])) {
									$temporaryKey = $this->temporaryKeys[$singleValue];

								// If not, create a new temporary key
								} else {
									$this->newKeysCounter++;
									$temporaryKey = 'NEW_' . $this->newKeysCounter;
									$this->temporaryKeys[$singleValue] = $temporaryKey;
								}
								// Use temporary key
								$mappedExternalValues[] = $temporaryKey;
							}
						}
					}
					if (count($mappedExternalValues) > 0) {
						$records[$i][$columnName] = implode(',', $mappedExternalValues);
					} else {
						unset($records[$i][$columnName]);
					}
				}
			}

			// If a particular mapping method is defined, use it on the keys of the mapping table
			// NOTE: self-referential relations are not checked in this case, as it does not seem to make sense
			// to have weak-matching external keys
		} else {
			if ($mappingInformation['match_method'] == 'strpos' || $mappingInformation['match_method'] == 'stripos') {
				for ($i = 0; $i < $numRecords; $i++) {
					$externalValue = $records[$i][$columnName];
					// The external field may contain multiple values
					if (!empty($mappingInformation['multipleValuesSeparator'])) {
						$singleExternalValues = t3lib_div::trimExplode(
							$mappingInformation['multipleValuesSeparator'],
							$externalValue,
							TRUE
						);

					// The external field is expected to contain a single value
					} else {
						$singleExternalValues = array($externalValue);
					}
					// Loop on all values and try to map them
					$mappedExternalValues = array();
					foreach ($singleExternalValues as $singleValue) {
						// Try matching the value. If matching fails, unset it.
						try {
							$mappedExternalValues[] = $this->matchSingleField($singleValue, $mappingInformation, $mappings);
						}
						catch (Exception $e) {
							// Ignore unmapped values
						}
					}
					if (count($mappedExternalValues) > 0) {
						$records[$i][$columnName] = implode(',', $mappedExternalValues);
					} else {
						unset($records[$i][$columnName]);
					}
				}
			}
		}
		return $records;
	}

	/**
	 * This method tries to match a single value to a table of mappings
	 *
	 * @param mixed $externalValue The value to match
	 * @param array $mappingInformation Mapping configuration
	 * @param array $mappingTable Value map
	 * @throws UnexpectedValueException
	 * @return mixed The matched value
	 */
	public function matchSingleField($externalValue, $mappingInformation, $mappingTable) {
		$returnValue = '';
		$function = $mappingInformation['match_method'];
		if (!empty($externalValue)) {
			$hasMatch = FALSE;
			foreach ($mappingTable as $key => $value) {
				$hasMatch = (call_user_func($function, $key, $externalValue) !== FALSE);
				if (!empty($mappingInformation['match_symmetric'])) {
					$hasMatch |= (call_user_func($function, $externalValue, $key) !== FALSE);
				}
				if ($hasMatch) {
					$returnValue = $value;
					break;
				}
			}
				// If unmatched, throw exception
			if (!$hasMatch) {
				throw new UnexpectedValueException('Unmatched value ' . $externalValue, 1294739120);
			}
		}
		return $returnValue;
	}

	/**
	 * This method applies any existing pre-processing to the data just as it was fetched,
	 * before any transformation.
	 *
	 * Note that this method does not do anything by itself. It just calls on a pre-processing hook.
	 *
	 * @param array $records Records containing the raw data
	 * @return array The processed records
	 */
	protected function preprocessRawData($records) {
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['preprocessRawRecordset'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['preprocessRawRecordset'] as $className) {
				$preProcessor = &t3lib_div::getUserObj($className);
				$records = $preProcessor->preprocessRawRecordset($records, $this);
				// Compact the array again, in case some values were unset in the pre-processor
				$records = array_values($records);
			}
		}
		return $records;
	}

	/**
	 * This method is used to check whether the data is ok and import should continue
	 * It performs a basic check on the minimum number of records expected (if defined),
	 * but provides a hook for more refined tests.
	 *
	 * @param	array		$records: records containing the raw data (after preprocessRawData())
	 * @return	boolean		True if data is valid and import should continue, false otherwise
	 */
	protected function validateRawData($records) {
		$continueImport = TRUE;

			// Check if number of records is larger than or equal to the minimum required number of records
			// Note that if the minimum is not defined, this test is skipped
		if (!empty($this->externalConfig['minimumRecords'])) {
			$numRecords = count($records);
			$continueImport = $numRecords >= $this->externalConfig['minimumRecords'];
			if (!$continueImport) {
				$this->addMessage(sprintf($GLOBALS['LANG']->getLL('notEnoughRecords'), $numRecords, $this->externalConfig['minimumRecords']));
			}
		}

			// Call hooks to perform additional checks,
			// but only if previous check was passed
		if ($continueImport) {
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['validateRawRecordset'])) {
				foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['validateRawRecordset'] as $className) {
					$validator = &t3lib_div::getUserObj($className);
					$continueImport = $validator->validateRawRecordset($records, $this);
						// If a single check fails, don't call further hooks
					if (!$continueImport) {
						break;
					}
				}
			}
		}
		return $continueImport;
	}

	/**
	 * This method applies any existing pre-processing to the data before it is stored
	 * (but after is has been transformed).
	 *
	 * Note that this method does not do anything by itself. It just calls on a pre-processing hook.
	 *
	 * @param array $records Records containing the data
	 * @return array The processed records
	 */
	protected function preprocessData($records) {
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['preprocessRecordset'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['preprocessRecordset'] as $className) {
				$preProcessor = &t3lib_div::getUserObj($className);
				$records = $preProcessor->preprocessRecordset($records, $this);
				// Compact the array again, in case some values were unset in the pre-processor
				$records = array_values($records);
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
		if ($this->extConf['debug'] || TYPO3_DLOG) {
			t3lib_div::devLog('Data received for storage', $this->extKey, 0, $records);
		}

			// Initialize some variables
		$fieldsExcludedFromInserts = array();
		$fieldsExcludedFromUpdates = array();

			// Get the list of existing uids for the table
		$existingUids = $this->getExistingUids();

			// Check which columns are MM-relations and get mappings to foreign tables for each
			// NOTE: as it is now, it is assumed that the imported data is denormalised
			//
			// NOTE2:	as long as we're looping on all columns, we assemble the list
			//			of fields that are excluded from insert or update operations
			//
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
				// Check if some fields are excluded from some operations
				// and add them to the relevant list
			if (isset($columnData['external'][$this->columnIndex]['disabledOperations'])) {
				if (t3lib_div::inList($columnData['external'][$this->columnIndex]['disabledOperations'], 'insert')) {
					$fieldsExcludedFromInserts[] = $columnName;
				}
				if (t3lib_div::inList($columnData['external'][$this->columnIndex]['disabledOperations'], 'update')) {
					$fieldsExcludedFromUpdates[] = $columnName;
				}
			}
			// The "excludedOperations" property is deprecated and replaced by "disabledOperations"
			// It is currently kept for backwards-compatibility reasons
			// TODO: remove in next major version
			if (isset($columnData['external'][$this->columnIndex]['excludedOperations'])) {
				$deprecationMessage = 'Property "excludedOperations" has been deprecated. Please use "disabledOperations" instead.';
				$deprecationMessage .= LF . 'Support for "excludedOperations" will be removed in external_import version 3.0.';
				t3lib_div::deprecationLog($deprecationMessage);
				if (t3lib_div::inList($columnData['external'][$this->columnIndex]['excludedOperations'], 'insert')) {
					$fieldsExcludedFromInserts[] = $columnName;
				}
				if (t3lib_div::inList($columnData['external'][$this->columnIndex]['excludedOperations'], 'update')) {
					$fieldsExcludedFromUpdates[] = $columnName;
				}
			}
			// Process MM-relations, if any
			if (isset($columnData['external'][$this->columnIndex]['MM'])) {
				$mmData = $columnData['external'][$this->columnIndex]['MM'];
				$sortingField = (isset($mmData['sorting'])) ? $mmData['sorting'] : FALSE;
				$additionalFields = (isset($mmData['additional_fields'])) ? $mmData['additional_fields'] : FALSE;

				$mappings[$columnName] = array();
				if ($additionalFields || $mmData['multiple']) {
					$fullMappings[$columnName] = array();
				}

				// Get foreign mapping for column
				// TODO: remove in next major version
				if (isset($mmData['mappings']['uid_foreign'])) {
					$deprecationMessage = 'Property "mappings.uid_foreign" has been deprecated. Please use "mapping" instead.';
					$deprecationMessage .= LF . 'Support for "mappings.uid_foreign" will be removed in external_import version 3.0.';
					t3lib_div::deprecationLog($deprecationMessage);
					$mappingInformation = $mmData['mappings']['uid_foreign'];
				} else {
					$mappingInformation = $mmData['mapping'];
				}
				$foreignMappings = $this->getMapping($mappingInformation);

				// Go through each record and assemble pairs of primary and foreign keys
				foreach ($records as $theRecord) {
					$externalUid = $theRecord[$this->externalConfig['reference_uid']];
						// Make sure not to keep the value from the previous iteration
					unset($foreignValue);

					// Get foreign value
					// First try the "soft" matching method to mapping table
					if (!empty($mmData['mapping']['match_method'])) {
						if ($mmData['mapping']['match_method'] == 'strpos' || $mmData['mapping']['match_method'] == 'stripos') {
							// Try matching the value. If matching fails, unset it.
							try {
						$foreignValue = $this->matchSingleField($theRecord[$columnName], $mmData['mapping'], $foreignMappings);
							}
							catch (Exception $e) {
						// Nothing to do, foreign value must stay "unset"
						}
					}

					// Then the "strict" matching method to mapping table
					} elseif (isset($foreignMappings[$theRecord[$columnName]])) {
						$foreignValue = $foreignMappings[$theRecord[$columnName]];
					}

					// If a value was found, use it
					if (isset($foreignValue)) {
						if (!isset($mappings[$columnName][$externalUid])) {
							$mappings[$columnName][$externalUid] = array();
								// Initialise only if necessary
							if ($additionalFields || $mmData['multiple']) {
								$fullMappings[$columnName][$externalUid] = array();
							}
						}

						// If additional fields are defined, store those values in an intermediate array
						$fields = array();
						if ($additionalFields) {
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
						} else {
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
					foreach ($mappings as $innerColumnName => $columnMappings) {
						foreach ($columnMappings as $uid => $values) {
							ksort($values);
							$mappings[$innerColumnName][$uid] = $values;

								// Do the same for extended MM-relations, if necessary
							if ($additionalFields || $mmData['multiple']) {
								$fullValues = $fullMappings[$innerColumnName][$uid];
								ksort($fullValues);
								$fullMappings[$innerColumnName][$uid] = $fullValues;
							}
						}
					}
				}
			}
		}
		$hasMMRelations = count($mappings);

		// Insert or update records depending on existing uids
		$updates = 0;
		$updatedUids = array();
		$handledUids = array();
		$tceData = array($this->table => array());
		$savedAdditionalFields = array();
		foreach ($records as $theRecord) {
			$localAdditionalFields = array();
			$externalUid = $theRecord[$this->externalConfig['reference_uid']];
			// Skip handling of already handled records (this can happen with denormalized structures)
			// NOTE: using isset() on index instead of in_array() offers far better performance
			if (isset($handledUids[$externalUid])) {
				continue;
			}
			$handledUids[$externalUid] = $externalUid;

			// Prepare MM-fields, if any
			if ($hasMMRelations) {
				foreach ($mappings as $columnName => $columnMappings) {
					if (isset($columnMappings[$externalUid])) {
						$theRecord[$columnName] = implode(',', $columnMappings[$externalUid]);

					// Make sure not to keep the original value if no mapping was found
					} else {
						unset($theRecord[$columnName]);
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
			if (isset($existingUids[$externalUid])) {
				if (!t3lib_div::inList($this->externalConfig['disabledOperations'], 'update')) {
					// First call a pre-processing hook
					if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['updatePreProcess'])) {
						foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['updatePreProcess'] as $className) {
							$preProcessor = &t3lib_div::getUserObj($className);
							$theRecord = $preProcessor->processBeforeUpdate($theRecord, $this);
						}
					}

					// Remove the fields which must be excluded from updates
					if (count($fieldsExcludedFromUpdates) > 0) {
						foreach ($fieldsExcludedFromUpdates as $excludedField) {
							unset($theRecord[$excludedField]);
						}
					}

					$theID = $existingUids[$externalUid];
					$tceData[$this->table][$theID] = $theRecord;
					$updatedUids[] = $theID;
					$updates++;
				}

				// Reference uid not found, perform an insert (if not disabled)
			} elseif (!t3lib_div::inList($this->externalConfig['disabledOperations'], 'insert')) {

					// First call a pre-processing hook
				if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['insertPreProcess'])) {
					foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['insertPreProcess'] as $className) {
						$preProcessor = &t3lib_div::getUserObj($className);
						$theRecord = $preProcessor->processBeforeInsert($theRecord, $this);
					}
				}

					// Remove the fields which must be excluded from inserts
				if (count($fieldsExcludedFromInserts) > 0) {
					foreach ($fieldsExcludedFromInserts as $excludedField) {
						unset($theRecord[$excludedField]);
					}
				}

					// Make sure the record has a pid, but avoid overwriting an existing one
					// (for example, when external data is imported into the pages table)
				if (!isset($theRecord['pid'])) {
					$theRecord['pid'] = $this->pid;
				}
					// If a temporary key was already defined, use it, otherwise create a new one.
					// Temporary keys may exist if self-referential mapping was handled beforehand (see mapData())
				if (isset($this->temporaryKeys[$externalUid])) {
					$theID = $this->temporaryKeys[$externalUid];
				} else {
					$this->newKeysCounter++;
					$theID = 'NEW_' . $this->newKeysCounter;
				}
				$tceData[$this->table][$theID] = $theRecord;
			}
				// Store local additional fields into general additional fields array
				// keyed to proper id's (if the record was processed)
			if (!empty($theID)) {
				$savedAdditionalFields[$theID] = $localAdditionalFields;
			}
		}
			// If the table has a sorting field, reverse the data array,
			// otherwise the first record will come last (because TCEmain
			// itself inverts the incoming order)
		if (!empty($this->tableTCA['ctrl']['sortby'])) {
			$tceData[$this->table] = array_reverse($tceData[$this->table], TRUE);
		}
		if ($this->extConf['debug'] || TYPO3_DLOG) {
			t3lib_div::devLog('TCEmain data', $this->extKey, 0, $tceData);
		}
			// Create an instance of TCEmain and process the data
			/** @var $tce t3lib_TCEmain */
		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$tce->stripslashes_values = 0;
			// Check if TCEmain logging should be turned on or off
		$disableLogging = (empty($this->extConf['disableLog'])) ? FALSE : TRUE;
		if (isset($this->externalConfig['disableLog'])) {
			$disableLogging = (empty($this->externalConfig['disableLog'])) ? FALSE : TRUE;
		}
		$tce->enableLogging = !$disableLogging;
			// Load the data and process it
		$tce->start($tceData, array());
		$tce->process_datamap();
		if ($this->extConf['debug'] || TYPO3_DLOG) {
			t3lib_div::devLog('New IDs', 'external_import', 0, $tce->substNEWwithIDs);
		}
			// Store the number of new IDs created. This is used in error reporting later
		$numberOfNewIDs = count($tce->substNEWwithIDs);

			// Post-processing hook after data was saved
		$savedData = array();
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['datamapPostProcess'])) {
			foreach ($tceData as $tableRecords) {
				foreach ($tableRecords as $id => $record) {
						// Added status to record
						// If operation was insert, match placeholder to actual id
					$uid = $id;
					if (isset($tce->substNEWwithIDs[$id])) {
						$uid = $tce->substNEWwithIDs[$id];
						$record['tx_externalimport:status'] = 'insert';
					} else {
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

		// Mark as deleted records with existing uids that were not in the import data anymore
		// (if automatic delete is activated)
		if (
			t3lib_div::inList($this->externalConfig['disabledOperations'], 'delete')
			|| (
				isset($this->externalConfig['deleteNonSynchedRecords'])
				&& $this->externalConfig['deleteNonSynchedRecords'] === FALSE
				)
			) {
			$deletes = 0;
		} else {
			$absentUids = array_diff($existingUids, $updatedUids);
				// Call a pre-processing hook
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
					// Call a post-processing hook
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
			if ($this->extConf['debug'] || TYPO3_DLOG) {
				t3lib_div::devLog('Handling full mappings', $this->extKey, 0, $fullMappings);
			}

				// Refresh list of existing primary keys now that new records have been inserted
			$existingUids = $this->getExistingUids();

				// Loop on all columns that require a remapping
			foreach ($fullMappings as $columnName => $mappingData) {
				$mmTable = $this->tableTCA['columns'][$columnName]['config']['MM'];
				foreach ($mappingData as $externalUid => $sortedData) {
					$uid = $existingUids[$externalUid];

						// Delete existing MM-relations for current uid
					$GLOBALS['TYPO3_DB']->exec_DELETEquery($mmTable, 'uid_local = ' . intval($uid));

						// Recreate all MM-relations with additional fields, if any
					$counter = 0;
					foreach ($sortedData as $mmData) {
						$counter++;
						$fields = $mmData['additional_fields'];
						$fields['uid_local'] = $uid;
						$fields['uid_foreign'] = $mmData['value'];
						$fields['sorting'] = $counter;
						$GLOBALS['TYPO3_DB']->exec_INSERTquery($mmTable, $fields);
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
					$message = $label;
					if (!empty($row['log_data'])) {
						$data = unserialize($row['log_data']);
						$message = sprintf($label, htmlspecialchars($data[0]), htmlspecialchars($data[1]), htmlspecialchars($data[2]), htmlspecialchars($data[3]), htmlspecialchars($data[4]));
					}
					$this->messages[t3lib_FlashMessage::ERROR][] = $message;
					if ($this->extConf['debug'] || TYPO3_DLOG) {
						t3lib_div::devLog($message, $this->extKey, 3);
					}
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($res);
			}
				// Substract the number of new IDs from the number of inserts,
				// to get a realistic number of new records
			$inserts = $this->newKeysCounter + ($numberOfNewIDs - $this->newKeysCounter);
				// Add a warning that numbers reported (below) may not be accurate
			$this->messages[t3lib_FlashMessage::WARNING][] = $GLOBALS['LANG']->getLL('things_happened');
		} else {
			$inserts = $numberOfNewIDs;
		}
		unset($tce);

			// Set informational messages
		$this->messages[t3lib_FlashMessage::OK][] = sprintf($GLOBALS['LANG']->getLL('records_inserted'), $inserts);
		$this->messages[t3lib_FlashMessage::OK][] = sprintf($GLOBALS['LANG']->getLL('records_updated'), $updates);
		$this->messages[t3lib_FlashMessage::OK][] = sprintf($GLOBALS['LANG']->getLL('records_deleted'), $deletes);
	}

	/**
	 * Clears the cache of some pages, if such a list was defined
	 *
	 * @return void
	 */
	protected function clearCache() {
		if (!empty($this->externalConfig['clearCache'])) {
				// Extract the list of pages for which to clear the cache
			$pages = t3lib_div::trimExplode(',', $this->externalConfig['clearCache'], TRUE);
				// Use TCEmain to clear the cache of individual pages
			if (count($pages) > 0) {
					/** @var $tce t3lib_TCEmain */
				$tce = t3lib_div::makeInstance('t3lib_TCEmain');
				$tce->start(array(), array());
				foreach ($pages as $pageId) {
					$tce->clear_cacheCmd(intval($pageId));
				}
			}
			unset($tce);
		}
	}

	/**
	 * Gets a list of all existing primary keys in the table being synchronized
	 *
	 * @return array Hash table of all external primary keys matched to internal primary keys
	 */
	protected function getExistingUids() {
		$existingUids = array();
		$where = '1 = 1';
		if ($this->externalConfig['enforcePid']) {
			$where = "pid = '" . $this->pid . "'";
		}
		if (!empty($this->externalConfig['where_clause'])) {
			$where .= ' AND ' . $this->externalConfig['where_clause'];
		}
		$where .= t3lib_BEfunc::deleteClause($this->table);
		$referenceUidField = $this->externalConfig['reference_uid'];
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($referenceUidField . ',uid', $this->table, $where);
		if ($res) {
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					// Don't consider records with empty references, as they can't be matched
					// to external data anyway (but a real zero is acceptable)
				if (!empty($row[$referenceUidField]) || $row[$referenceUidField] === '0' || $row[$referenceUidField] === 0) {
					$existingUids[$row[$referenceUidField]] = $row['uid'];
				}
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
		}
		return $existingUids;
	}

	/**
	 * Retrieves a single mapping
	 *
	 * @param array $mappingData Data defining the mapping of fields
	 * @return array Hash table for mapping
	 */
	protected function getMapping($mappingData) {
		$localMapping = array();

			// Check if there's a fixed value map
		if (isset($mappingData['valueMap'])) {
				// Use value map directly
			$localMapping = $mappingData['valueMap'];

			// No value map, get values from the database
		} else {
				// Assemble query and get data
			$valueField = 'uid';
			if (isset($mappingData['value_field'])) {
				$valueField = $mappingData['value_field'];
			}
			$referenceField = $mappingData['reference_field'];
			$fields = $referenceField . ', ' . $valueField;
			// Define where clause
			$whereClause = '1 = 1';
			if (!empty($mappingData['where_clause'])) {
				// If the where clause contains the ###PID_IN_USE### marker, replace it with current storage pid
				if (strpos($mappingData['where_clause'], '###PID_IN_USE###') !== FALSE) {
					$whereClause = str_replace('###PID_IN_USE###', $this->pid, $mappingData['where_clause']);
				} else {
					$whereClause = $mappingData['where_clause'];
				}
			}
			$whereClause .= t3lib_BEfunc::deleteClause($mappingData['table']);
				// Query the table
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields, $mappingData['table'], $whereClause);

				// Fill hash table
			if ($res) {
				while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					// Don't consider records with empty references, as they can't be matched
					// to external data anyway (but a real zero is acceptable)
					if (!empty($row[$referenceField]) || $mappingData[$referenceField] === '0' || $mappingData[$referenceField] === 0) {
						$localMapping[$row[$referenceField]] = $row[$valueField];
					}
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($res);
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
		$severity = -1;

			// Define severity based on types of messages
		if (count($this->messages[t3lib_FlashMessage::ERROR]) > 0) {
			$severity = 3;
		} elseif (count($this->messages[t3lib_FlashMessage::WARNING]) > 0) {
			$severity = 2;
		}
		if ($this->extConf['debug'] || TYPO3_DLOG) {
			t3lib_div::devLog(sprintf($GLOBALS['LANG']->getLL('sync_table'), $this->table), $this->extKey, $severity, $this->messages);
		}
	}


// Getters and setters


	/**
	 * Returns the name of the table being synchronised
	 *
	 * @return string Name of the table
	 */
	public function getTableName() {
		return $this->table;
	}

	/**
	 * Sets the name of the table to be synchronized.
	 *
	 * This is used only in special cases, you should never need to call this.
	 *
	 * @param string $table Name of the table
	 */
	public function setTableName($table) {
		$this->table = $table;
	}

	/**
	 * Returns the index of the configuration used in the current synchronisation.
	 *
	 * @return mixed
	 */
	public function getIndex() {
		return $this->index;
	}

	/**
	 * Sets the index of the configuration to used for synchronization.
	 *
	 * This is used only in special cases, you should never need to call this.
	 *
	 * @param mixed $index Index to use
	 */
	public function setIndex($index) {
		$this->index = $index;
	}

	/**
	 * Returns the index of the configuration used for the columns.
	 *
	 * @return mixed
	 */
	public function getColumnIndex() {
		return $this->columnIndex;
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
	 * This method returns the extension's configuration
	 * It is used to avoid reading it multiple times from the various components of this extension
	 *
	 * @return	array	The unserialized extension's configuration
	 */
	public function getExtensionConfiguration() {
		return $this->extConf;
	}

	/**
	 * This method is used to add a message to the message queue that will be returned
	 * when the synchronisation is complete
	 *
	 * @param string $text The message itself
	 * @param integer $status Status of the message. Expected is "success", "warning" or "error"
	 * @return void
	 */
	public function addMessage($text, $status = t3lib_FlashMessage::ERROR) {
		if (!empty($text)) {
			$this->messages[$status][] = $text;
		}
	}


// Reporting utilities


	/**
	 * This method assembles a report for a given table/index
	 *
	 * @param string $table Name of the table
	 * @param integer $index Number of the synchronisation configuration
	 * @param array $messages List of messages for the given table
	 * @return string Formatted text of the report
	 */
	public function reportForTable($table, $index, $messages) {
		$report = sprintf($GLOBALS['LANG']->getLL('synchronizeTableX'), $table, $index) . "\n\n";
		foreach ($messages as $type => $messageList) {
			$report .= $GLOBALS['LANG']->getLL('label.' . $type) . "\n";
			if (count($messageList) == 0) {
				$report .= "\t" . $GLOBALS['LANG']->getLL('no.' . $type) . "\n";
			} else {
				foreach ($messageList as $aMessage) {
					$report .= "\t- " . $aMessage . "\n";
				}
			}
		}
		$report .= "\n";
		return $report;
	}

	/**
	 * Sends a reporting mail to the configured e-mail address
	 *
	 * @param string $subject Subject of the mail
	 * @param string $body Text body of the mail
	 * @return void
	 */
	public function sendMail($subject, $body) {
		$result = 0;
			// Define sender mail and name
		$senderMail = '';
		$senderName = '';
		if (!empty($GLOBALS['BE_USER']->user['email'])) {
			$senderMail = $GLOBALS['BE_USER']->user['email'];
			if (empty($GLOBALS['BE_USER']->user['realName'])) {
				$senderName = $GLOBALS['BE_USER']->user['username'];
			} else {
				$senderName = $GLOBALS['BE_USER']->user['realName'];
			}
		} elseif (!empty($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'])) {
			$senderMail = $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'];
			if (empty($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'])) {
				$senderName =  $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'];
			} else {
				$senderName =  $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'];
			}
		}
			// If no mail could be found, avoid sending the mail
			// The message will be logged as an error
		if (empty($senderMail)) {
			$message = 'No sender mail defined. Please check the manual.';

			// Proceed with sending the mail
		} else {
				// Instantiate and initialize the mail object
				/** @var $mailObject t3lib_mail_Message */
			$mailObject = t3lib_div::makeInstance('t3lib_mail_Message');
			try {
				$sender = array(
					$senderMail => $senderName
				);
				$mailObject->setFrom($sender);
				$mailObject->setReplyTo($sender);
				$mailObject->setTo(array($this->extConf['reportEmail']));
				$mailObject->setSubject($subject);
				$mailObject->setBody($body);
					// Send mail
				$result = $mailObject->send();
				$message = '';
			}
			catch (Exception $e) {
				$message = $e->getMessage() . '[' . $e->getCode() . ']';
			}
		}

			// Report error in log, if any
		if ($result == 0) {
			$comment = 'Reporting mail could not be sent to ' . $this->extConf['reportEmail'];
			if (!empty($message)) {
				$comment .= ' (' . $message . ')';
			}
			$GLOBALS['BE_USER']->writelog(
				4,
				0,
				1,
				$this->extKey,
				$comment,
				array()
			);
		}
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/external_import/class.tx_externalimport_importer.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/external_import/class.tx_externalimport_importer.php']);
}
?>
