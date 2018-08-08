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
    public function handleData($rawData, Importer $importer)
    {
        $data = array();
        $configuration = $importer->getExternalConfiguration();
        $ctrlConfiguration = $configuration->getCtrlConfiguration();
        $columnConfiguration = $configuration->getColumnConfiguration();

        // Load the XML into a DOM object
        $dom = new \DOMDocument();
        $dom->loadXML($rawData, LIBXML_PARSEHUGE);
        // Instantiate a XPath object and load with any defined namespaces
        $xPathObject = new \DOMXPath($dom);
        if (isset($ctrlConfiguration['namespaces']) && is_array($ctrlConfiguration['namespaces'])) {
            foreach ($ctrlConfiguration['namespaces'] as $prefix => $uri) {
                $xPathObject->registerNamespace($prefix, $uri);
            }
        }

        // Get the nodes that represent the root of each data record
        $records = $dom->getElementsByTagName($ctrlConfiguration['nodetype']);
        for ($i = 0; $i < $records->length; $i++) {
            /** @var \DOMElement $theRecord */
            $theRecord = $records->item($i);
            $theData = array();

            // Loop on the database columns and get the corresponding value from the import data
            foreach ($columnConfiguration as $columnName => $columnData) {
                    // If a "field" is defined, refine the selection to get the correct node
                    if (isset($columnData['field'])) {
                        // Use namespace or not
                        if (empty($columnData['fieldNS'])) {
                            $nodeList = $theRecord->getElementsByTagName($columnData['field']);
                        } else {
                            $nodeList = $theRecord->getElementsByTagNameNS(
                                    $columnData['fieldNS'],
                                    $columnData['field']
                            );
                        }

                        if ($nodeList->length > 0) {
                            /** @var $selectedNode \DOMNode */
                            $selectedNode = $nodeList->item(0);
                            // If an XPath expression is defined, apply it (relative to currently selected node)
                            if (!empty($columnData['xpath'])) {
                                try {
                                    $selectedNode = $this->selectNodeWithXpath(
                                            $xPathObject,
                                            $columnData['xpath'],
                                            $selectedNode
                                    );
                                } catch (\Exception $e) {
                                    // Nothing to do, data is ignored
                                }
                            }
                            $theData[$columnName] = $this->extractValueFromNode(
                                    $selectedNode,
                                    $columnData
                            );
                        }

                    // Without "field" property, use the current node itself
                    } else {
                        // If an XPath expression is defined, apply it (relative to current node)
                        if (!empty($columnData['xpath'])) {
                            try {
                                $selectedNode = $this->selectNodeWithXpath(
                                        $xPathObject,
                                        $columnData['xpath'],
                                        $theRecord
                                );
                                $theData[$columnName] = $this->extractValueFromNode(
                                        $selectedNode,
                                        $columnData
                                );
                            } catch (\Exception $e) {
                                // Nothing to do, data is ignored
                            }
                        } else {
                            $theData[$columnName] = $this->extractValueFromNode(
                                    $theRecord,
                                    $columnData
                            );
                        }
                    }
            }

            // Get additional fields data, if any
            if ($configuration->getCountAdditionalFields() > 0) {
                foreach ($configuration->getAdditionalFields() as $fieldName) {
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
     * @param \DOMNode $node Currently handled XML node
     * @param array $columnData Handling information for the XML node
     * @return mixed The extracted value
     */
    protected function extractValueFromNode($node, $columnData)
    {
        // Get the named attribute, if defined
        if (!empty($columnData['attribute'])) {
            // Use namespace or not
            if (empty($columnData['attributeNS'])) {
                $value = $node->attributes->getNamedItem($columnData['attribute'])->nodeValue;
            } else {
                $value = $node->attributes->getNamedItemNS($columnData['attributeNS'],
                        $columnData['attribute'])->nodeValue;
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
     * @param \DOMNode $node Currently handled XML node
     * @return string Code inside the node
     */
    protected function getXmlValue($node)
    {
        $innerHTML = '';
        $children = $node->childNodes;
        foreach ($children as $child) {
            try {
                $innerHTML .= $child->ownerDocument->saveXML($child);
            }
            catch (\Exception $e) {
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
     * @param \DOMNode $context Node giving the context of the XPath query
     * @return \DOMElement First node found
     * @throws \Exception
     */
    protected function selectNodeWithXpath($xPathObject, $xPath, $context)
    {
        $resultNodes = $xPathObject->evaluate($xPath, $context);
        if ($resultNodes->length > 0) {
            return $resultNodes->item(0);
        }
        throw new \Exception(
                'No node found with xPath: ' . $xPath,
                1399497464
        );
    }
}