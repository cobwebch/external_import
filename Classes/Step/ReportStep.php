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
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Step dedicated to reporting about the import run
 */
class ReportStep extends AbstractStep
{
    public function __construct(protected EventDispatcherInterface $eventDispatcher)
    {
        $this->setExecuteDespiteAbort(true);
    }

    public function run(): void
    {
        // Log results
        $this->importer->getReportingUtility()->writeToLog();

        // Fire event for custom reporting
        $this->eventDispatcher->dispatch(
            new ReportEvent(
                $this->importer
            )
        );
    }
}
