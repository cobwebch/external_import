<?php

declare(strict_types=1);

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

namespace Cobweb\ExternalImport\Reaction;

use Cobweb\ExternalImport\Domain\Model\ConfigurationKey;
use Cobweb\ExternalImport\Domain\Repository\ConfigurationRepository;
use Cobweb\ExternalImport\Exception\InvalidPayloadException;
use Cobweb\ExternalImport\Exception\NoConfigurationException;
use Cobweb\ExternalImport\Importer;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Reactions\Model\ReactionInstruction;
use TYPO3\CMS\Reactions\Reaction\ReactionInterface;

class ImportReaction implements ReactionInterface
{
    protected ResponseFactoryInterface $responseFactory;
    protected StreamFactoryInterface $streamFactory;

    public function __construct(ResponseFactoryInterface $responseFactory, StreamFactoryInterface $streamFactory)
    {
        $this->responseFactory = $responseFactory;
        $this->streamFactory = $streamFactory;
    }

    public static function getType(): string
    {
        return 'import-external-data';
    }

    public static function getDescription(): string
    {
        return 'LLL:EXT:external_import/Resources/Private/Language/locallang_db.xlf:sys_reaction.reaction_type.import_external_data';
    }

    public static function getIconIdentifier(): string
    {
        return 'tx_external_import-reaction-import';
    }

    /**
     * Reacts to the call for importing data
     *
     * @param ServerRequestInterface $request
     * @param array $payload
     * @param ReactionInstruction $reaction
     * @return ResponseInterface
     */
    public function react(ServerRequestInterface $request, array $payload, ReactionInstruction $reaction): ResponseInterface
    {
        $configurationKey = (string)($reaction->toArray()['external_import_configuration'] ?? '');
        try {
            $configurationKeyObject = $this->validatePayloadAndConfigurationKey($payload, $configurationKey);
            // Import
            $importer = GeneralUtility::makeInstance(Importer::class);
            // Check if a storage pid was given
            if (MathUtility::canBeInterpretedAsInteger($payload['pid'] ?? null)) {
                $importer->setForcedStoragePid((int)$payload['pid']);
            }
            $messages = $importer->import(
                $configurationKeyObject->getTable(),
                $configurationKeyObject->getIndex(),
                $payload['data']
            );
            // Report on import outcome
            if (count($messages[AbstractMessage::ERROR]) > 0) {
                // If errors occurred, report only about errors
                return $this->jsonResponse(
                    [
                        'success' => false,
                        'errors' => $messages[AbstractMessage::ERROR],
                    ],
                    400
                );
            }
            // If import completed successfully, report about success and possible warnings
            $responseBody = [
                'success' => true,
                'messages' => $messages[AbstractMessage::OK],
            ];
            if (count($messages[AbstractMessage::WARNING]) > 0) {
                $responseBody['warnings'] .= $messages[AbstractMessage::WARNING];
            }
            return $this->jsonResponse($responseBody);
        } catch (InvalidPayloadException $e) {
            return $this->jsonResponse(
                [
                    'success' => false,
                    'error' => sprintf(
                        '%s [%d]',
                        $e->getMessage(),
                        $e->getCode()
                    ),
                ],
                400
            );
        }
    }

    /**
     * Validates that the payloads contains the proper structure for the External Import reaction
     * and that the configuration exists.
     */
    protected function validatePayloadAndConfigurationKey(array $payload, string $configurationKey): ConfigurationKey
    {
        if (!isset($payload['data'])) {
            throw new InvalidPayloadException(
                'The payload does not contain any data to import',
                1681482804
            );
        }

        if ($configurationKey === '') {
            if (!isset($payload['table'], $payload['index'])) {
                throw new InvalidPayloadException(
                    'The payload must contain both a "table" and an "index" information',
                    1681482506
                );
            }

            $configurationRepository = GeneralUtility::makeInstance(ConfigurationRepository::class);

            try {
                $configurationRepository->findByTableAndIndex($payload['table'], $payload['index']);
            } catch (NoConfigurationException $e) {
                throw new InvalidPayloadException(
                    'The "table" and "index" information given in the payload does not match an existing configuration',
                    1681482838,
                    $e
                );
            }

            $configurationKeyObject = GeneralUtility::makeInstance(ConfigurationKey::class);
            $configurationKeyObject->setTableAndIndex($payload['table'], (string)$payload['index']);
        } else {
            if (isset($payload['table'], $payload['index'])) {
                throw new InvalidPayloadException(
                    'The payload must not contain a "table" and an "index" information',
                    1726559649
                );
            }

            $configurationKeyObject = GeneralUtility::makeInstance(ConfigurationKey::class);
            $configurationKeyObject->setConfigurationKey($configurationKey);
        }

        return $configurationKeyObject;
    }

    /**
     * Prepares and returns the JSON response
     *
     * @param array $data
     * @param int $statusCode
     * @return ResponseInterface
     */
    protected function jsonResponse(array $data, int $statusCode = 200): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse($statusCode)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->streamFactory->createStream((string)json_encode($data)));
    }
}
