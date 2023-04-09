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

namespace Cobweb\ExternalImport\Utility;

use Cobweb\Svconnector\Exception\ConnectorRuntimeException;
use Cobweb\Svconnector\Exception\UnknownServiceException;
use Cobweb\Svconnector\Registry\ConnectorRegistry;
use Cobweb\Svconnector\Service\ConnectorBase;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Small class for encapsulating methods related to backwards-compatibility.
 *
 * @package Cobweb\ExternalImport\Utility
 */
class CompatibilityUtility
{
    /**
     * Checks whether we are running TYPO3 v11 or not.
     *
     * @return bool
     */
    public static function isV11(): bool
    {
        $typo3Version = GeneralUtility::makeInstance(Typo3Version::class);
        return $typo3Version->getMajorVersion() === 11;
    }

    /**
     * Checks whether we are running TYPO3 v12 or not.
     *
     * @return bool
     */
    public static function isV12(): bool
    {
        $typo3Version = GeneralUtility::makeInstance(Typo3Version::class);
        return $typo3Version->getMajorVersion() === 12;
    }

    /**
     * Returns the requested connector service, depending on the available architecture
     * (old TYPO3 services or new connector registry).
     *
     * @param string $key
     * @return ConnectorBase
     * @throws UnknownServiceException|ConnectorRuntimeException
     */
    public static function getConnectorService(string $key): ConnectorBase
    {
        if (class_exists(ConnectorRegistry::class)) {
            $registry = GeneralUtility::makeInstance(ConnectorRegistry::class);
            $service = $registry->getServiceForType($key);
            if (!$service->isAvailable()) {
                throw new ConnectorRuntimeException(
                    sprintf(
                        'The requested connector service (%s) is not available.',
                        $key
                    ),
                    1673026928
                );
            }
        } else {
            $service = GeneralUtility::makeInstanceService(
                'connector',
                $key
            );
            if (!$service) {
                throw new UnknownServiceException(
                    sprintf(
                        'No connector service found for type %s',
                        $key
                    ),
                    1673026336
                );
            }

            if (is_array($service)) {
                throw new ConnectorRuntimeException(
                    sprintf(
                        'The connector service could not be initialized. Reason %s (code: %s).',
                        $service['msg'],
                        $service['nr']
                    ),
                    1673026771
                );
            }
        }
        return $service;
    }
}