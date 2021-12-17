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

namespace Cobweb\ExternalImport\Compatibility\Dbal;

/**
 * Wrapper for handling doctrine/dbal compatibility across TYPO3 versions
 * for the replacement of fetch() by fetchAssociative().
 */
abstract class AbstractResultIterator
{
    abstract public function next($result);
}