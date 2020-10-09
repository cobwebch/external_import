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

use Cobweb\ExternalImport\Exception\CriticalFailureException;
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
    public function run(): void
    {
        $generalConfiguration = $this->importer->getExternalConfiguration()->getGeneralConfiguration();
        // Check if there are any services of the given type
        $services = ExtensionManagementUtility::findService(
                'connector',
                $generalConfiguration['connector']
        );

        // The service is not available
        if ($services === false) {
            $this->setAbortFlag(true);
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
                    $generalConfiguration['connector']
            );

            // The service was instantiated, but an error occurred while initiating the connection
            // The returned value is not a Connector service
            if (!($connector instanceof ConnectorBase)) {
                $this->setAbortFlag(true);
                // If the returned value is an array, we have proper error reporting.
                if (is_array($connector)) {
                    $this->importer->addMessage(
                            LocalizationUtility::translate(
                                    'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:data_not_fetched_with_error',
                                    'external_import',
                                    [
                                            $connector['msg'],
                                            $connector['nr']
                                    ]
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
                $this->importer->getExternalConfiguration()->setConnector($connector);
                $data = [];

                // Pre-process connector parameters
                try {
                    $parameters = $this->processParameters($generalConfiguration['parameters']);
                } catch (CriticalFailureException $e) {
                    // If a critical failure occurred during hook execution, set the abort flag and return to controller
                    $this->setAbortFlag(true);
                    return;
                }

                // A problem may happen while fetching the data
                // If so, the import process has to be aborted
                switch ($generalConfiguration['data']) {
                    case 'xml':
                        try {
                            $data = $connector->fetchXML($parameters);
                        } catch (\Exception $e) {
                            $this->abortFlag = true;
                            $this->importer->addMessage(
                                    LocalizationUtility::translate(
                                            'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:data_not_fetched_connector_error',
                                            'external_import',
                                            [
                                                    $e->getMessage()
                                            ]
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
                                            [
                                                    $e->getMessage()
                                            ]
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

                // Set the data as raw data (and also as preview, if activated)
                $this->getData()->setRawData($data);
                $this->setPreviewData($data);
            }
        }
    }

    /**
     * Pre-processes the configured connector parameters.
     *
     * @param array $parameters List of parameters to process
     * @return array The processed parameters
     * @throws CriticalFailureException
     */
    protected function processParameters($parameters): array
    {
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['external_import']['processParameters'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['external_import']['processParameters'] as $className) {
                try {
                    $preProcessor = GeneralUtility::makeInstance($className);
                    $parameters = $preProcessor->processParameters($parameters, $this->importer->getExternalConfiguration());
                } catch (CriticalFailureException $e) {
                    // This exception must not be caught here, but thrown further up
                    throw $e;
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
}