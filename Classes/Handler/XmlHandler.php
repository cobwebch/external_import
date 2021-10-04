<?php

declare(strict_types=1);

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
use TYPO3\CMS\Core\Messaging\AbstractMessage;

/**
 * Remaps data from a XML structure to an array mapped to TCA columns.
 *
 * @package Cobweb\ExternalImport\Handler
 */
class XmlHandler implements DataHandlerInterface
{
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
        $generalConfiguration = $configuration->getGeneralConfiguration();
        $columnConfiguration = $configuration->getColumnConfiguration();

        // Load the XML into a DOM object
        $dom = new \DOMDocument();
        $dom->loadXML($rawData, LIBXML_PARSEHUGE);
        // Instantiate a XPath object and load with any defined namespaces
        $xPathObject = new \DOMXPath($dom);
        if (isset($generalConfiguration['namespaces']) && is_array($generalConfiguration['namespaces'])) {
            foreach ($generalConfiguration['namespaces'] as $prefix => $uri) {
                $xPathObject->registerNamespace($prefix, $uri);
            }
        }

        // Get the nodes that represent the root of each data record, using XPath if defined
        $records = [];
        if (array_key_exists('nodepath', $generalConfiguration)) {
            try {
                $records = $this->selectNodeWithXpath(
                    $xPathObject,
                    $generalConfiguration['nodepath']
                );
            } catch (\Exception $e) {
                $importer->addMessage(
                    $e->getMessage(),
                    AbstractMessage::WARNING
                );
            }
        } else {
            $records = $dom->getElementsByTagName($generalConfiguration['nodetype']);
        }
        for ($i = 0; $i < $records->length; $i++) {
            $referenceCounter = $counter;
            $data[$referenceCounter] = [];
            $rows = [];
            /** @var \DOMElement $theRecord */
            $theRecord = $records->item($i);

            // Loop on the database columns and get the corresponding value from the import data
            foreach ($columnConfiguration as $columnName => $columnData) {
                try {
                    if (isset($columnData['substructureFields'])) {
                        $nodeList = $this->getNodeList(
                            $theRecord,
                            $columnData,
                            $xPathObject
                        );
                        $rows[$columnName] = $this->getSubstructureValues(
                            $nodeList,
                            $columnData['substructureFields'],
                            $xPathObject
                        );
                    } else {
                        $data[$referenceCounter][$columnName] = $this->getValue(
                            $theRecord,
                            $columnData,
                            $xPathObject
                        );
                    }
                } catch (\Exception $e) {
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
                // Add as many records to the import data as the highest count, while filling in with the values found in each substructure
                // NOTE: this is not equivalent to a full denormalization, but is enough for the needs of External Import
                for ($j = 0; $j < $maxItems; $j++) {
                    // Base data is the first entry of the $theData array
                    // NOTE: the first pass is a neutral operation
                    $data[$counter] = $data[$referenceCounter];
                    // Add a value from each structure field to each row, if it exists
                    foreach ($rows as $column => $items) {
                        if (isset($items[$j])) {
                            foreach ($items[$j] as $key => $item) {
                                $data[$counter][$key] = $item;
                            }
                        }
                    }
                    $counter++;
                }
            } else {
                $counter++;
            }
        }
        // Filter out empty entries (may happen if no value could be found)
        foreach ($data as $index => $item) {
            if (count($item) === 0) {
                unset($data[$index]);
            }
        }
        // Compact array
        return array_values($data);
    }

    /**
     * Searches for a value inside the record using the given configuration.
     *
     * @param \DOMNode $record Data record
     * @param array $columnConfiguration External Import configuration for a single column
     * @param \DOMXPath $xPathObject
     * @return mixed
     * @throws \Exception
     */
    public function getValue(\DOMNode $record, array $columnConfiguration, \DOMXPath $xPathObject)
    {
        // If a "field" is defined, refine the selection to get the correct node
        if (isset($columnConfiguration['field'])) {
            // Use namespace or not
            if (empty($columnConfiguration['fieldNS'])) {
                $nodeList = $record->getElementsByTagName($columnConfiguration['field']);
            } else {
                $nodeList = $record->getElementsByTagNameNS(
                    $columnConfiguration['fieldNS'],
                    $columnConfiguration['field']
                );
            }

            if ($nodeList->length > 0) {
                /** @var $selectedNode \DOMNode */
                $selectedNode = $nodeList->item(0);
                // If an XPath expression is defined, apply it (relative to currently selected node)
                if (!empty($columnConfiguration['xpath'])) {
                    $nodes = $this->selectNodeWithXpath(
                        $xPathObject,
                        $columnConfiguration['xpath'],
                        $selectedNode
                    );
                    $selectedNode = $nodes->item(0);
                }
                $value = $this->extractValueFromNode(
                    $selectedNode,
                    $columnConfiguration
                );
            } else {
                throw new \InvalidArgumentException(
                    'No value found',
                    1534166267
                );
            }
            // Without "field" property, use the current node itself
        } else {
            // If an XPath expression is defined, apply it (relative to current node)
            if (!empty($columnConfiguration['xpath'])) {
                $nodes = $this->selectNodeWithXpath(
                    $xPathObject,
                    $columnConfiguration['xpath'],
                    $record
                );
                $selectedNode = $nodes->item(0);
                $value = $this->extractValueFromNode(
                    $selectedNode,
                    $columnConfiguration
                );
            } else {
                $value = $this->extractValueFromNode(
                    $record,
                    $columnConfiguration
                );
            }
        }
        return $value;
    }

    /**
     * Searches for a value inside the record using the given configuration.
     *
     * @param \DOMNode $record Data record
     * @param array $columnConfiguration External Import configuration for a single column
     * @param \DOMXPath $xPathObject
     * @return \DOMNodeList
     * @throws \Exception
     */
    public function getNodeList(\DOMNode $record, array $columnConfiguration, \DOMXPath $xPathObject): \DOMNodeList
    {
        // If a "field" is defined, refine the selection to get the correct node
        if (isset($columnConfiguration['field'])) {
            // Use namespace or not
            if (empty($columnConfiguration['fieldNS'])) {
                $nodeList = $record->getElementsByTagName($columnConfiguration['field']);
            } else {
                $nodeList = $record->getElementsByTagNameNS(
                    $columnConfiguration['fieldNS'],
                    $columnConfiguration['field']
                );
            }

            if ($nodeList->length > 0 && !empty($columnConfiguration['xpath'])) {
                $selectedNode = $nodeList->item(0);
                $nodeList = $this->selectNodeWithXpath(
                    $xPathObject,
                    $columnConfiguration['xpath'],
                    $selectedNode
                );
            } else {
                throw new \InvalidArgumentException(
                    'No value found',
                    1534166267
                );
            }
            // Without "field" property, use the current node itself
        } else {
            // If an XPath expression is defined, apply it (relative to current node)
            if (!empty($columnConfiguration['xpath'])) {
                $nodeList = $this->selectNodeWithXpath(
                    $xPathObject,
                    $columnConfiguration['xpath'],
                    $record
                );
            } else {
                $nodeList = $record;
            }
        }
        return $nodeList;
    }

    /**
     * Extracts either an attribute from a XML node or the value of the node itself,
     * based on the configuration received.
     *
     * @param \DOMNode $node Currently handled XML node
     * @param array $columnConfiguration Handling information for the XML node
     * @return mixed The extracted value
     */
    public function extractValueFromNode(\DOMNode $node, array $columnConfiguration)
    {
        // Get the named attribute, if defined
        if (!empty($columnConfiguration['attribute'])) {
            // Use namespace or not
            if (empty($columnConfiguration['attributeNS'])) {
                $value = $node->attributes->getNamedItem($columnConfiguration['attribute'])->nodeValue;
            } else {
                $value = $node->attributes->getNamedItemNS(
                    $columnConfiguration['attributeNS'],
                    $columnConfiguration['attribute']
                )->nodeValue;
            }
            // Otherwise directly take the node's value
        } else {
            // If "xmlValue" is set, we want the node's inner XML structure as is.
            // Otherwise, we take the straight node value, which is similar but with tags stripped.
            if (empty($columnConfiguration['xmlValue'])) {
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
     * @param \DOMNode $node Currently handled XML node
     * @return string Code inside the node
     */
    public function getXmlValue(\DOMNode $node): string
    {
        $innerHTML = '';
        $children = $node->childNodes;
        foreach ($children as $child) {
            try {
                $innerHTML .= $child->ownerDocument->saveXML($child);
            } catch (\Exception $e) {
                // Nothing to do
            }
        }
        return $innerHTML;
    }

    /**
     * Queries the current structure with an XPath query.
     *
     * @param \DOMXPath $xPathObject Instantiated DOMXPath object
     * @param string $xPath XPath query to evaluate
     * @param \DOMNode $context Node giving the context of the XPath query (null for root node)
     * @return \DOMNodeList List of found nodes
     * @throws \Exception
     */
    public function selectNodeWithXpath(\DOMXPath $xPathObject, string $xPath, $context = null): \DOMNodeList
    {
        $resultNodes = $xPathObject->evaluate($xPath, $context);
        if ($resultNodes->length > 0) {
            return $resultNodes;
        }
        throw new \Exception(
            'No node found with xPath: ' . $xPath,
            1399497464
        );
    }

    /**
     * Extracts data from a substructure, i.e. when a value is not just a simple type but contains
     * related data.
     *
     * @param \DOMNodeList $structure Data structure
     * @param array $columnConfiguration External Import configuration for a single column
     * @param \DOMXPath $xPathObject
     * @return mixed
     */
    public function getSubstructureValues(\DOMNodeList $structure, array $columnConfiguration, \DOMXPath $xPathObject)
    {
        $rows = [];
        for ($i = 0; $i < $structure->length; $i++) {
            $item = $structure->item($i);
            $row = [];
            foreach ($columnConfiguration as $key => $configuration) {
                try {
                    $value = $this->getValue($item, $configuration, $xPathObject);
                    $row[$key] = $value;
                } catch (\Exception $e) {
                    // Nothing to do, we ignore values that were not found
                }
            }
            $rows[] = $row;
        }
        return $rows;
    }
}