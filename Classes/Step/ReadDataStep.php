<?php
namespace Cobweb\ExternalImport\Step;

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

use Cobweb\Svconnector\Service\ConnectorBase;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * This step reads the data from the external source.
 *
 * @package Cobweb\ExternalImport\Step
 */
class ReadDataStep extends AbstractStep
{
    /**
     * Reads the data from the external source.
     *
     * @return void
     */
    public function run()
    {
        $ctrlConfiguration = $this->configuration->getCtrlConfiguration();
        // Check if there are any services of the given type
        $services = ExtensionManagementUtility::findService(
                'connector',
                $ctrlConfiguration['connector']
        );

        // The service is not available
        if ($services === false) {
            $this->importer->addMessage(
                    LocalizationUtility::translate(
                            'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:no_service',
                            'external_import'
                    )
            );
        } else {
            /** @var $connector ConnectorBase */
            $connector = GeneralUtility::makeInstanceService(
                    'connector',
                    $ctrlConfiguration['connector']
            );

            // The service was instantiated, but an error occurred while initiating the connection
            // The returned value is not a Connector service
            if (!($connector instanceof ConnectorBase)) {
                // If the returned value is an array, we have proper error reporting.
                if (is_array($connector)) {
                    $this->importer->addMessage(
                            LocalizationUtility::translate(
                                    'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:data_not_fetched_with_error',
                                    'external_import',
                                    array(
                                            $connector['msg'],
                                            $connector['nr']
                                    )
                            )
                    );

                // Otherwise display generic error message
                } else {
                    $this->importer->addMessage(
                            LocalizationUtility::translate(
                                    'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:data_not_fetched',
                                    'external_import'
                            )
                    );
                }

            // The connection is established, get the data
            } else {
                // Store a reference to the connector object for the callback step
                $this->configuration->setConnector($connector);
                $data = array();

                // Pre-process connector parameters
                $parameters = $this->processParameters($ctrlConfiguration['parameters']);

                // A problem may happen while fetching the data
                // If so, the import process has to be aborted
                switch ($ctrlConfiguration['data']) {
                    case 'xml':
                        try {
                            $data = $connector->fetchXML($parameters);
                        } catch (\Exception $e) {
                            $this->abortFlag = true;
                            $this->importer->addMessage(
                                    LocalizationUtility::translate(
                                            'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:data_not_fetched_connector_error',
                                            'external_import',
                                            array(
                                                    $e->getMessage()
                                            )
                                    )
                            );
                        }
                        break;

                    case 'array':
                        try {
                            $data = $connector->fetchArray($parameters);
                        } catch (\Exception $e) {
                            $this->abortFlag = true;
                            $this->importer->addMessage(
                                    LocalizationUtility::translate(
                                            'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:data_not_fetched_connector_error',
                                            'external_import',
                                            array(
                                                    $e->getMessage()
                                            )
                                    )
                            );
                        }
                        break;

                    // If the data type is not defined, issue error and abort process
                    default:
                        $this->abortFlag = true;
                        $this->importer->addMessage(
                                LocalizationUtility::translate(
                                        'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:data_type_not_defined',
                                        'external_import'
                                )
                        );
                        break;
                }

                // Debug a sample of the handled data
                // TODO: replace with preview feature
                $debugData = $this->prepareDataSample($data);
                $this->importer->debug(
                        'Data received (sample)',
                        -1,
                        $debugData
                );

                $this->getData()->setRawData($data);
            }
        }
    }

    /**
     * Pre-processes the configured connector parameters.
     *
     * @param array $parameters List of parameters to process
     * @return array The processed parameters
     */
    protected function processParameters($parameters)
    {
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['external_import']['processParameters'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['external_import']['processParameters'] as $className) {
                try {
                    $preProcessor = GeneralUtility::makeInstance($className);
                    $parameters = $preProcessor->processParameters($parameters, $this->importer);
                } catch (\Exception $e) {
                    $this->importer->debug(
                            sprintf(
                                    'Could not instantiate class %s for hook %s',
                                    $className,
                                    'processParameters'
                            ),
                            1
                    );
                }
            }
        }
        return $parameters;
    }

    /**
     * This method prepares a sample from the data to import, based on the preview limit
     * The process applied for this depends on the data type (array or XML)
     *
     * @param    mixed $data : the input data as a XML string or a PHP array
     * @return    array    The data sample, in same format as input (but written inside an array in case of XML data)
     */
    protected function prepareDataSample($data)
    {
        $extensionConfiguration = $this->importer->getExtensionConfiguration();
        $ctrlConfiguration = $this->configuration->getCtrlConfiguration();
        $dataSample = $data;
        if (!empty($extensionConfiguration['previewLimit'])) {
            switch ($ctrlConfiguration['data']) {
                case 'xml':

                    // Load the XML into a DOM object
                    $dom = new \DOMDocument();
                    $dom->loadXML($data, LIBXML_PARSEHUGE);
                    // Prepare an empty DOM object for the sample data
                    $domSample = new \DOMDocument();
                    // Define a root node
                    $element = $domSample->createElement('sample');
                    $domSample->appendChild($element);
                    // Get the desired nodes
                    $selectedNodes = $dom->getElementsByTagName($ctrlConfiguration['nodetype']);
                    // Loop until the preview limit and import selected nodes into the sample XML object
                    $loopLimit = min($selectedNodes->length, $extensionConfiguration['previewLimit']);
                    for ($i = 0; $i < $loopLimit; $i++) {
                        $newNode = $domSample->importNode($selectedNodes->item($i), true);
                        $domSample->documentElement->appendChild($newNode);
                    }
                    // Store the XML sample in an array, to have a common return format
                    $dataSample = array();
                    $dataSample[] = $domSample->saveXML();
                    break;
                case 'array':
                    $dataSample = array_slice($data, 0, $extensionConfiguration['previewLimit']);
                    break;
            }
        }
        return $dataSample;
    }
}