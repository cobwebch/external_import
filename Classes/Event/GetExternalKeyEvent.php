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

use Cobweb\ExternalImport\Domain\Model\Configuration;

final class GetExternalKeyEvent
{
    /**
     * @var array<string, mixed>
     */
    private array $data;
    private Configuration $configuration;
    /**
     * @var mixed
     */
    private $externalKey;

    /**
     * @param array<string, mixed> $data
     * @param mixed $externalKey
     */
    public function __construct(array $data, Configuration $configuration, $externalKey = null)
    {
        $this->data = $data;
        $this->configuration = $configuration;
        $this->externalKey = $externalKey;
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    public function getConfiguration(): Configuration
    {
        return $this->configuration;
    }

    /**
     * @return mixed
     */
    public function getExternalKey()
    {
        return $this->externalKey;
    }

    /**
     * @param mixed $externalKey
     */
    public function setExternalKey($externalKey): void
    {
        $this->externalKey = $externalKey;
    }
}
