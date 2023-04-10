<?php

declare(strict_types=1);

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

use Cobweb\ExternalImport\Domain\Model\Dto\QueryParameters;
use Cobweb\ExternalImport\Domain\Repository\LogRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Controller for the AJAX calls of the "Log" module
 *
 * @package Cobweb\ExternalImport\Controller
 */
class LogAjaxController
{

    /**
     * Returns the list of all log entries, in JSON format.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface|null $response
     * @return ResponseInterface
     */
    public function getAction(ServerRequestInterface $request, ResponseInterface $response = null): ResponseInterface
    {
        // Process query parameters
        $queryParameters = GeneralUtility::makeInstance(
            QueryParameters::class,
            $request->getQueryParams()
        );
        // Get an instance of the log repository
        $logRepository = GeneralUtility::makeInstance(LogRepository::class);

        // Get the count of all the log entries
        $totalEntries = $logRepository->findAll()->count();
        // Get the filtered entries and their count
        $logs = [];
        $logCount = 0;
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
                    'username' => $logEntry->getCruserId() ? $logEntry->getCruserId()->getUserName() : '-',
                    'configuration' => $logEntry->getConfiguration(),
                    'context' => $logEntry->getContext(),
                    'message' => $logEntry->getMessage(),
                    'duration' => $logEntry->getDuration()
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
        if ($response === null) {
            $response = GeneralUtility::makeInstance(JsonResponse::class);
        }
        $response->getBody()->write(json_encode($fullResponse, JSON_THROW_ON_ERROR));
        return $response;
    }
}