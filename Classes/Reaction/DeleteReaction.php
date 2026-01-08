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

use Cobweb\ExternalImport\Domain\Model\Configuration;
use Cobweb\ExternalImport\Domain\Model\ConfigurationKey;
use Cobweb\ExternalImport\Domain\Repository\ConfigurationRepository;
use Cobweb\ExternalImport\Domain\Repository\ItemRepository;
use Cobweb\ExternalImport\Event\GetExternalKeyEvent;
use Cobweb\ExternalImport\Event\ModifyReactionResponseEvent;
use Cobweb\ExternalImport\Exception\DeletedRecordException;
use Cobweb\ExternalImport\Exception\InvalidConfigurationException;
use Cobweb\ExternalImport\Exception\InvalidPayloadException;
use Cobweb\ExternalImport\Exception\NoConfigurationException;
use Cobweb\ExternalImport\Exception\ReactionFailedException;
use Cobweb\ExternalImport\Validator\GeneralConfigurationValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Reactions\Model\ReactionInstruction;
use TYPO3\CMS\Reactions\Reaction\ReactionInterface;

class DeleteReaction extends AbstractReaction implements ReactionInterface
{
    public static function getType(): string
    {
        return 'delete-external-data';
    }

    public static function getDescription(): string
    {
        return 'LLL:EXT:external_import/Resources/Private/Language/locallang_db.xlf:sys_reaction.reaction_type.delete_external_data';
    }

    public static function getIconIdentifier(): string
    {
        return 'tx_external_import-reaction-import';
    }

    public function react(ServerRequestInterface $request, array $payload, ReactionInstruction $reaction): ResponseInterface
    {
        $responseCode = 200;
        $configurationKey = (string)($reaction->toArray()['external_import_configuration'] ?? '');
        try {
            $configurationKeyObject = $this->validatePayloadAndConfigurationKey($payload, $configurationKey);
            $deletedItems = $this->deleteItems($configurationKeyObject, $payload);
            $responseBody = [
                'success' => true,
                'message' => sprintf(
                    '%d item(s) successfully deleted',
                    $deletedItems
                ),
            ];
        } catch (\Throwable $e) {
            $responseBody = [
                'success' => false,
                'error' => sprintf(
                    '%s [%d]',
                    $e->getMessage(),
                    $e->getCode()
                ),
            ];
            $responseCode = 400;
        }

        // Fire event to modify response body and/or code
        $event = $this->eventDispatcher->dispatch(
            new ModifyReactionResponseEvent(
                $this,
                $responseBody,
                $responseCode,
                isset($configurationKeyObject) ? [$configurationKeyObject] : []
            )
        );
        $responseBody = $event->getResponseBody();
        $responseCode = $event->getResponseCode();

        return $this->jsonResponse(
            $responseBody,
            $responseCode
        );
    }

    /**
     * Validates that the payload contains the proper structure for the External Import reaction
     * and that the configuration exists.
     */
    protected function validatePayloadAndConfigurationKey(array $payload, string $configurationKey): ConfigurationKey
    {
        if (!isset($payload['data'])) {
            throw new InvalidPayloadException(
                'The payload does not contain any data to be deleted',
                1740406649
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
     * Delete the items designated in the payload
     *
     * @throws \Cobweb\ExternalImport\Exception\NoConfigurationException
     * @throws InvalidConfigurationException
     * @throws ReactionFailedException
     */
    protected function deleteItems(ConfigurationKey $configurationKey, array $payload): int
    {
        $deletedItems = 0;
        // Get the corresponding configuration and validate it
        $configurationRepository = GeneralUtility::makeInstance(ConfigurationRepository::class);
        $configuration = $configurationRepository->findConfigurationObject(
            $configurationKey->getTable(),
            $configurationKey->getIndex()
        );
        $validator = GeneralUtility::makeInstance(GeneralConfigurationValidator::class);
        if ($validator->isValid($configuration)) {
            $generalConfiguration = $configuration->getGeneralConfiguration();
            $externalKeyField = $generalConfiguration['referenceUid'];

            // Loop on the items, skip those with no external key
            foreach ($payload['data'] as $item) {
                $externalKey = $this->getExternalKey($configuration, $item);
                if ($externalKey !== null) {
                    // Fetch the corresponding record from the database, applying relevant constraints from External Import
                    if (array_key_exists(
                        'whereClause',
                        $generalConfiguration
                    ) && !empty($generalConfiguration['whereClause'])) {
                        $additionalConstraint = $generalConfiguration['whereClause'];
                    } else {
                        $additionalConstraint = '';
                    }
                    $constraints = [
                        $externalKeyField => $externalKey,
                    ];
                    if ((bool)($generalConfiguration['enforcePid'] ?? false)) {
                        $constraints['pid'] = (int)$configuration->getStoragePid();
                    }
                    $itemRepository = GeneralUtility::makeInstance(ItemRepository::class);
                    try {
                        $itemId = $itemRepository->find(
                            $configurationKey->getTable(),
                            $constraints,
                            $additionalConstraint
                        );
                        // Delete the selected record
                        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
                        $dataHandler->start(
                            [],
                            [
                                $configurationKey->getTable() => [
                                    $itemId => [
                                        'delete' => 1,
                                    ],
                                ],
                            ],
                        );
                        $dataHandler->process_cmdmap();
                        // Check for errors
                        if (count($dataHandler->errorLog) > 0) {
                            throw new ReactionFailedException(
                                'One or more errors occurred while trying to delete the item(s). Please refer to the TYPO3 log',
                                1735291140
                            );
                        }
                        $deletedItems++;
                    } catch (DeletedRecordException $e) {
                        // If the record was found, but is already deleted, simply count it as deleted
                        $deletedItems++;
                    }
                }
            }
        } else {
            throw new InvalidConfigurationException(
                sprintf(
                    'Invalid configuration for table %s, index %s. Please use the backend module to check it.',
                    $configurationKey->getTable(),
                    $configurationKey->getIndex()
                ),
                1735286147
            );
        }
        return $deletedItems;
    }

    /**
     * Extract the external key from the data, firing an event for further manipulation
     */
    protected function getExternalKey(Configuration $configuration, array $data)
    {
        /** @var GetExternalKeyEvent $event */
        $event = $this->eventDispatcher->dispatch(
            new GetExternalKeyEvent(
                $data,
                $configuration,
                $data['external_id'] ?? null
            )
        );
        return $event->getExternalKey();
    }
}
