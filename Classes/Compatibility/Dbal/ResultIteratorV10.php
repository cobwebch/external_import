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
 *
 * In TYPO3 v10, it is not safe to use fetchAssociative(), because non-composer based
 * installations are shipped with an older version of doctrine/dbal.
 */
class ResultIteratorV10 extends AbstractResultIterator
{
    public function next($result) {
        return $result->fetch(\PDO::FETCH_ASSOC);
    }
}