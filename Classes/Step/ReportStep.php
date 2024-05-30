<?php

declare(strict_types=1);

namespace Cobweb\ExternalImport\Step;

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
use Cobweb\ExternalImport\Utility\CompatibilityUtility;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Step dedicated to reporting about the import run
 */
class ReportStep extends AbstractStep
{
    protected EventDispatcherInterface $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->setExecuteDespiteAbort(true);
    }

    public function run(): void
    {
        // Log results
        $this->importer->getReportingUtility()->writeToLog();

        // Fire event for custom reporting
        // NOTE: it also triggers the reporting webhook and hence is fired only with TYPO3 12
        if (CompatibilityUtility::isV12()) {
            /** @var ReportEvent $event */
            $event = $this->eventDispatcher->dispatch(
                new ReportEvent(
                    $this->importer
                )
            );
        }
    }
}
