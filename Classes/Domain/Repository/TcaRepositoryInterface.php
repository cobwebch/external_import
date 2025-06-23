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

/**
 * This is not a true repository in the Extbase sense of the term.
 * It returns the TCA and not a database.
 * It also does not provide any persistence.
 *
 * The extension makes heavy use of TCA as configuration.
 * This interface is used to fetch the actual TCA, instead of using direct access.
 *
 * That way there is a single place. This one can be replaced via Dependency Injection. E.g. to alter the TCA to use.
 */
interface TcaRepositoryInterface
{
    /**
     * Fetch the actual TCA configuration to use.
     */
    public function getTca(): array;
}
