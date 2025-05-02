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
use Cobweb\ExternalImport\Enum\CallType;
use Cobweb\ExternalImport\Exception\InvalidPayloadException;
use Cobweb\ExternalImport\Exception\NoConfigurationException;
use Cobweb\ExternalImport\Importer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Reactions\Model\ReactionInstruction;
use TYPO3\CMS\Reactions\Reaction\ReactionInterface;

class ImportReaction extends AbstractReaction implements ReactionInterface
{
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
            $configurations = $this->validatePayloadAndConfigurationKey($payload, $configurationKey);
            $importer = GeneralUtility::makeInstance(Importer::class);
            $importer->setCallType(CallType::Reaction);
            // Check if a storage pid was given
            if (MathUtility::canBeInterpretedAsInteger($payload['pid'] ?? null)) {
                $importer->setForcedStoragePid((int)$payload['pid']);
            }
            /** @var ConfigurationKey $configuration */
            $successes = [];
            $errors = [];
            $warnings = [];
            foreach ($configurations as $configuration) {
                $messages = $importer->import(
                    $configuration->getTable(),
                    $configuration->getIndex(),
                    $payload['data']
                );
                // Report on import outcome
                if (count($messages[ContextualFeedbackSeverity::ERROR->value]) > 0) {
                    foreach ($messages[ContextualFeedbackSeverity::ERROR->value] as $message) {
                        $errors[] = sprintf(
                            '%s - %s: %s',
                            $configuration->getTable(),
                            $configuration->getIndex(),
                            $message
                        );
                    }
                }
                foreach ($messages[ContextualFeedbackSeverity::OK->value] as $message) {
                    $successes[] = sprintf(
                        '%s - %s: %s',
                        $configuration->getTable(),
                        $configuration->getIndex(),
                        $message
                    );
                }
                if (count($messages[ContextualFeedbackSeverity::WARNING->value]) > 0) {
                    foreach ($messages[ContextualFeedbackSeverity::WARNING->value] as $message) {
                        $warnings[] = sprintf(
                            '%s - %s: %s',
                            $configuration->getTable(),
                            $configuration->getIndex(),
                            $message
                        );
                    }
                }
            }

            // Return response
            if (count($errors) > 0) {
                // If errors occurred, report only about errors
                return $this->jsonResponse(
                    [
                        'success' => false,
                        'errors' => $errors,
                    ],
                    400
                );
            }
            // If import completed successfully, report about success and possible warnings
            $responseBody = [
                'success' => true,
                'messages' => $successes,
            ];
            if (count($warnings) > 0) {
                $responseBody['warnings'] = $warnings;
            }
            return $this->jsonResponse($responseBody);

        } catch (\Throwable $e) {
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
     * Validates that the payload contains the proper structure for the External Import reaction
     * and that the configuration exists. Returns the list of configurations (single configuration
     * or grouped configurations).
     */
    protected function validatePayloadAndConfigurationKey(array $payload, string $configurationKey): array
    {
        if (!isset($payload['data'])) {
            throw new InvalidPayloadException(
                'The payload does not contain any data to import',
                1681482804
            );
        }
        $configurations = [];
        $configurationRepository = GeneralUtility::makeInstance(ConfigurationRepository::class);

        if ($configurationKey === '') {
            if (!isset($payload['table'], $payload['index']) && !isset($payload['group'])) {
                throw new InvalidPayloadException(
                    'The payload must contain a "table" and an "index" information or a "group" information',
                    1740406882
                );
            }
            if (isset($payload['table'], $payload['index'], $payload['group'])) {
                throw new InvalidPayloadException(
                    'The payload must contain either a "table" and an "index" information or a "group" information, but not both',
                    1740406964
                );
            }

            // Handle single configuration designated by table and index
            if (isset($payload['table'], $payload['index'])) {
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
                $configurations[] = $configurationKeyObject;

                // Handle configuration group
            } else {
                $configurations = $this->retrieveGroupedConfigurations(
                    $payload['group'],
                    $configurationRepository
                );
            }

        } else {
            if (isset($payload['table'], $payload['index']) || isset($payload['group'])) {
                throw new InvalidPayloadException(
                    'The payload must not contain a "table" and an "index" information and neither a "group" information',
                    1740407045
                );
            }

            // Handle configuration group
            if (str_starts_with($configurationKey, 'group:')) {
                $configurations = $this->retrieveGroupedConfigurations(
                    substr($configurationKey, strlen('group:')),
                    $configurationRepository
                );

                // Handle single configuration designated by table and index
            } else {
                $configurationKeyObject = GeneralUtility::makeInstance(ConfigurationKey::class);
                $configurationKeyObject->setConfigurationKey($configurationKey);
                $configurations[] = $configurationKeyObject;
            }
        }

        return $configurations;
    }

    /**
     * Returns list of External Import configurations for the given group key.
     */
    protected function retrieveGroupedConfigurations(string $group, ConfigurationRepository $configurationRepository): array
    {
        $configurations = [];
        $groupedConfigurations = $configurationRepository->findByGroup($group);
        if (count($groupedConfigurations) === 0) {
            throw new InvalidPayloadException(
                sprintf(
                    'The given "group" configuration (%s) does not match any External Import configuration',
                    $group
                ),
                1740407689
            );
        }

        foreach ($groupedConfigurations as $configurationList) {
            foreach ($configurationList as $configuration) {
                $configurationKeyObject = GeneralUtility::makeInstance(ConfigurationKey::class);
                $configurationKeyObject->setTableAndIndex($configuration['table'], (string)$configuration['index']);
                $configurations[] = $configurationKeyObject;
            }
        }
        return $configurations;
    }
}
