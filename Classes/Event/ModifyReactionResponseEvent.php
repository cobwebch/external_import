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

namespace Cobweb\ExternalImport\Event;

use Cobweb\ExternalImport\Domain\Model\ConfigurationKey;
use Cobweb\ExternalImport\Reaction\AbstractReaction;

/**
 * Event fired before a reaction returns its response
 */
class ModifyReactionResponseEvent
{
    public function __construct(
        protected AbstractReaction $reaction,
        protected array $responseBody,
        protected int $responseCode,
        /**
         * @var ConfigurationKey[]
         */
        protected array $configurations
    ) {}

    public function getReaction(): AbstractReaction
    {
        return $this->reaction;
    }

    public function getResponseBody(): array
    {
        return $this->responseBody;
    }

    public function setResponseBody(array $responseBody): void
    {
        $this->responseBody = $responseBody;
    }

    public function getResponseCode(): int
    {
        return $this->responseCode;
    }

    public function setResponseCode(int $responseCode): void
    {
        $this->responseCode = $responseCode;
    }

    /**
     * @return ConfigurationKey[]
     */
    public function getConfigurations(): array
    {
        return $this->configurations;
    }
}
