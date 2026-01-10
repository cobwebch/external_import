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

namespace Cobweb\ExternalImport\Domain\Repository;

/*
 * Default implementation of the interface.
 * This is backwards compatible and directly accesses the globals TCA.
 */
class TcaDirectAccessRepository extends AbstractTcaRepository
{
    public function getTca(): array
    {
        return $GLOBALS['TCA'];
    }
}
