<?php

declare(strict_types=1);

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

use Cobweb\ExternalImport\Domain\Model\ConfigurationKey;
use Cobweb\ExternalImport\Event\ProcessConnectorParametersEvent;
use Cobweb\ExternalImport\Exception\CriticalFailureException;
use Cobweb\Svconnector\Registry\ConnectorRegistry;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This step reads the data from the external source.
 */
class ReadDataStep extends AbstractStep
{
    public function __construct(protected ConnectorRegistry $connectorRegistry, protected EventDispatcherInterface $eventDispatcher) {}

    /**
     * Reads the data from the external source.
     */
    public function run(): void
    {
        $generalConfiguration = $this->importer->getExternalConfiguration()->getGeneralConfiguration();

        // Pre-process connector parameters
        try {
            $parameters = $this->processParameters($generalConfiguration['parameters'] ?? []);
        } catch (CriticalFailureException $e) {
            // If a critical failure occurred during hook execution, set the abort flag and return to controller
            $this->setAbortFlag(true);
            return;
        }

        // Check if there are any services of the given type
        try {
            $connector = $this->connectorRegistry->getServiceForType(
                $generalConfiguration['connector'],
                $parameters
            );
            if (!$connector->isAvailable()) {
                $this->setAbortFlag(true);
                $this->importer->addMessage(
                    sprintf(
                        $this->importer->getLanguageService()->sL('LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:service_not_available'),
                        $generalConfiguration['connector'],
                    )
                );
                return;
            }
        } catch (\Exception $e) {
            $this->setAbortFlag(true);
            $this->importer->addMessage(
                sprintf(
                    $this->importer->getLanguageService()->sL('LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:service_error'),
                    $generalConfiguration['connector'],
                    $e->getMessage(),
                    $e->getCode(),
                )
            );
            return;
        }
        // Set the call context for the connector
        $configurationKey = GeneralUtility::makeInstance(ConfigurationKey::class);
        $configurationKey->setTableAndIndex(
            $this->importer->getExternalConfiguration()->getTable(),
            (string)$this->importer->getExternalConfiguration()->getIndex()
        );
        $connector->getCallContext()->add(
            'external_import',
            $configurationKey->getConfigurationKey(),
        );

        // Store a reference to the connector object for the callback step
        $this->importer->getExternalConfiguration()->setConnector($connector);
        $data = [];

        // A problem may happen while fetching the data
        // If so, the import process has to be aborted
        switch ($generalConfiguration['data']) {
            case 'xml':
                try {
                    $data = $connector->fetchXML();
                } catch (\Exception $e) {
                    $this->abortFlag = true;
                    $this->importer->addMessage(
                        sprintf(
                            $this->importer->getLanguageService()->sL('LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:data_not_fetched_connector_error'),
                            $e->getMessage()
                        )
                    );
                }
                break;

            case 'array':
                try {
                    $data = $connector->fetchArray();
                } catch (\Exception $e) {
                    $this->abortFlag = true;
                    $this->importer->addMessage(
                        sprintf(
                            $this->importer->getLanguageService()->sL('LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:data_not_fetched_connector_error'),
                            $e->getMessage()
                        )
                    );
                }
                break;

                // If the data type is not defined, issue error and abort process
            default:
                $this->abortFlag = true;
                $this->importer->addMessage(
                    $this->importer->getLanguageService()->sL('LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:data_type_not_defined')
                );
                break;
        }

        // Set the data as raw data (and also as preview, if activated)
        $this->getData()->setRawData($data);
        $this->setPreviewData($data);
    }

    /**
     * Pre-processes the configured connector parameters.
     *
     * @param array $parameters List of parameters to process
     * @return array The processed parameters
     * @throws CriticalFailureException
     */
    protected function processParameters(array $parameters): array
    {
        /** @var ProcessConnectorParametersEvent $event */
        $event = $this->eventDispatcher->dispatch(
            new ProcessConnectorParametersEvent(
                $parameters,
                $this->importer->getExternalConfiguration()
            )
        );
        return $event->getParameters();
    }
}
