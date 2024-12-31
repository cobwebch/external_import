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

use Cobweb\ExternalImport\Exception\InvalidPayloadException;
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
            if (count($messages[ContextualFeedbackSeverity::ERROR->value]) > 0) {
                // If errors occurred, report only about errors
                return $this->jsonResponse(
                    [
                        'success' => false,
                        'errors' => $messages[ContextualFeedbackSeverity::ERROR->value],
                    ],
                    400
                );
            }
            // If import completed successfully, report about success and possible warnings
            $responseBody = [
                'success' => true,
                'messages' => $messages[ContextualFeedbackSeverity::OK->value],
            ];
            if (count($messages[ContextualFeedbackSeverity::WARNING->value]) > 0) {
                $responseBody['warnings'] = $messages[ContextualFeedbackSeverity::WARNING->value];
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
}
