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
    protected array $data;
    protected Configuration $configuration;
    protected $externalKey;

    public function __construct(array $data, Configuration $configuration, $externalKey = null)
    {
        $this->data = $data;
        $this->configuration = $configuration;
        $this->externalKey = $externalKey;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getConfiguration(): Configuration
    {
        return $this->configuration;
    }

    public function getExternalKey()
    {
        return $this->externalKey;
    }

    public function setExternalKey($externalKey): void
    {
        $this->externalKey = $externalKey;
    }
}
