<?php

declare(strict_types=1);

namespace Cobweb\ExternalImport\Message;

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

use Cobweb\ExternalImport\Event\ReportEvent;
use Cobweb\ExternalImport\Importer;
use TYPO3\CMS\Core\Attribute\WebhookMessage;
use TYPO3\CMS\Core\Messaging\WebhookMessageInterface;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;

#[WebhookMessage(identifier: 'externalimport/after-import', description: 'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:webhook.after_import')]
class AfterImportMessage implements WebhookMessageInterface
{
    protected Importer $importer;

    public function __construct(Importer $importer)
    {
        $this->importer = $importer;
    }

    /**
     * Create a message from the reporting event
     *
     * @param ReportEvent $event
     * @return self
     */
    public static function createFromEvent(ReportEvent $event): self
    {
        return new self(
            $event->getImporter()
        );
    }

    /**
     * Prepare and return the message body
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        $externalConfiguration = $this->importer->getExternalConfiguration();
        $response = [
            'table' => $externalConfiguration->getTable(),
            'index' => $externalConfiguration->getIndex(),
            'description' => $externalConfiguration->getGeneralConfigurationProperty('description'),
        ];
        $messages = $this->importer->getMessages();
        $response['result'] = [
            'success' => $messages[ContextualFeedbackSeverity::OK->value],
            'warning' => $messages[ContextualFeedbackSeverity::WARNING->value],
            'error' => $messages[ContextualFeedbackSeverity::ERROR->value],
        ];
        return $response;
    }
}
