<?php

namespace Cobweb\ExternalImport\Exception;

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

use TYPO3\CMS\Core\Exception;

/**
 * Exception that should be thrown whenever a failed operation should stop the whole import,
 * rather than just having a single record fail. See the documentation for more information.
 */
class CriticalFailureException extends Exception
{
}
