<?php

namespace Cobweb\ExternalImport\Controller;

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

use Cobweb\ExternalImport\Domain\Repository\LogRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Controller for the AJAX calls of the "Log" module
 *
 * @author Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package TYPO3
 * @subpackage tx_externalimport
 */
class LogAjaxController
{

    /**
     * Returns the list of all log entries, in JSON format.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function getAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        // Process query parameters
        $queryParameters = GeneralUtility::makeInstance(
                \Cobweb\ExternalImport\Domain\Model\Dto\QueryParameters::class,
                $request->getQueryParams()
        );
        // Get an instance of the log repository
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $logRepository = $objectManager->get(LogRepository::class);

        // Get the count of all the log entries
        $totalEntries = $logRepository->findAll()->count();
        // Get the filtered entries and their count
        $logs = [];
        $error = '';
        try {
            // Search for all matching entries
            $logEntries = $logRepository->findBySearch($queryParameters);
            // The log count encompasses all matching entries, ignoring the limit
            $logCount = $logRepository->countBySearch($queryParameters);
            /** @var \Cobweb\ExternalImport\Domain\Model\Log $logEntry */
            foreach ($logEntries as $logEntry) {
                $logs[] = [
                        'status' => $logEntry->getStatus(),
                        'crdate' => $logEntry->getCrdate()->format('U'),
                        'username' => $logEntry->getCruserId()->getUserName(),
                        'configuration' => $logEntry->getConfiguration(),
                        'context' => $logEntry->getContext(),
                        'message' => $logEntry->getMessage()
                ];
            }
        } catch (\Exception $e) {
            $error = sprintf(
                    'An error happened retrieving the data (Exception: %s [%d]).',
                    $e->getMessage(),
                    $e->getCode()
            );
        }
        // Send the response
        $fullResponse = [
                'draw' => $queryParameters->getDraw(),
                'data' => $logs,
                'recordsTotal' => $totalEntries,
                'recordsFiltered' => $logCount,
                'error' => $error
        ];
        $response->getBody()->write(json_encode($fullResponse));
        return $response;
    }
}