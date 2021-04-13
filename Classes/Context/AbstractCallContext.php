<?php

declare(strict_types=1);

namespace Cobweb\ExternalImport\Context;

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

use Cobweb\ExternalImport\Importer;

/**
 * This abstract class defines what methods a call context class should implement.
 *
 * Call contexts are used to react to some output from the Importer class which needs some special handling
 * depending on the context in which the call happens (command-line, scheduler, etc.). This offloads the
 * responsibility of all these contexts from the Importer class itself.
 *
 * @package Cobweb\ExternalImport\Step
 */
abstract class AbstractCallContext
{
    /**
     * @var Importer
     */
    protected $importer;

    public function __construct(Importer $importer)
    {
        $this->importer = $importer;
    }

    /**
     * Outputs the debug data in accordance with the current call context.
     *
     * @param string $message Message to display
     * @param int $severity Degree of severity
     * @param mixed $data Additional data to display
     * @return void
     */
    abstract public function outputDebug(string $message, int $severity, $data): void;
}