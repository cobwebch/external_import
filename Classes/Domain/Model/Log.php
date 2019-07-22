<?php
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

use TYPO3\CMS\Extbase\Domain\Model\BackendUser;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Model for the log table.
 *
 * @author Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package TYPO3
 * @subpackage tx_externalimport
 */
class Log extends AbstractEntity
{
    /**
     * @var int Run status (based on FlashMessage codes)
     */
    protected $status;

    /**
     * @var \DateTime Run date and time
     */
    protected $crdate;

    /**
     * @var \TYPO3\CMS\Extbase\Domain\Model\BackendUser User who executed the run
     */
    protected $cruserId;

    /**
     * @var string Name of the corresponding external import configuration (table and index)
     */
    protected $configuration;

    /**
     * @var string Execution context
     */
    protected $context;

    /**
     * @var string The log message
     */
    protected $message;

    /**
     * @var int Run duration (in seconds)
     */
    protected $duration;

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
    public function setStatus($status): void
    {
        $this->status = $status;
    }

    /**
     * @return \DateTime
     */
    public function getCrdate(): \DateTime
    {
        return $this->crdate;
    }

    /**
     * @param \DateTime $crdate
     */
    public function setCrdate($crdate): void
    {
        $this->crdate = $crdate;
    }

    /**
     * @return BackendUser
     */
    public function getCruserId(): BackendUser
    {
        return $this->cruserId;
    }

    /**
     * @param BackendUser $cruserId
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
    public function setConfiguration($configuration): void
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
    public function setMessage($message): void
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
