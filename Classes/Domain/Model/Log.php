<?php

declare(strict_types=1);

namespace Cobweb\ExternalImport\Domain\Model;

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

use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Model for the log table.
 */
class Log extends AbstractEntity
{
    /**
     * @var int Run status (based on FlashMessage codes)
     */
// TODO: switch when support for PHP 8.1 is dropped (or get rid of that class entirely)
//    protected int $status = ContextualFeedbackSeverity::NOTICE->value;
    protected int $status = -2;

    /**
     * @var \DateTime|null Run date and time
     */
    protected ?\DateTime $crdate = null;

    /**
     * @var BackendUser|null User who executed the run
     */
    protected ?BackendUser $cruserId = null;

    /**
     * @var string Name of the corresponding external import configuration (table and index)
     */
    protected string $configuration = '';

    /**
     * @var string Execution context
     */
    protected string $context = '';

    /**
     * @var string The log message
     */
    protected string $message = '';

    /**
     * @var int Run duration (in seconds)
     */
    protected int $duration = 0;

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @param int $status
     */
    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    /**
     * @return \DateTime|null
     */
    public function getCrdate(): ?\DateTime
    {
        return $this->crdate;
    }

    /**
     * @param \DateTime $crdate
     */
    public function setCrdate(\DateTime $crdate): void
    {
        $this->crdate = $crdate;
    }

    /**
     * @return BackendUser|null
     */
    public function getCruserId(): ?BackendUser
    {
        return $this->cruserId;
    }

    /**
     * @param int|BackendUser $cruserId
     */
    public function setCruserId($cruserId): void
    {
        $this->cruserId = $cruserId;
    }

    /**
     * @return string
     */
    public function getConfiguration(): string
    {
        return $this->configuration;
    }

    /**
     * @param string $configuration
     */
    public function setConfiguration(string $configuration): void
    {
        $this->configuration = $configuration;
    }

    /**
     * @return string
     */
    public function getContext(): string
    {
        return $this->context;
    }

    /**
     * @param string $context
     */
    public function setContext(string $context): void
    {
        $this->context = $context;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    /**
     * @return int
     */
    public function getDuration(): int
    {
        return $this->duration;
    }

    /**
     * @param int $duration
     */
    public function setDuration(int $duration): void
    {
        $this->duration = $duration;
    }
}
