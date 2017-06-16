<?php
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

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Handles cache clearing operations.
 *
 * @package Cobweb\ExternalImport\Step
 */
class ClearCacheStep extends AbstractStep
{

    /**
     * Performs whatever cache clearing has been defined.
     *
     * @return void
     */
    public function run()
    {
        $configuration = $this->configuration->getCtrlConfiguration();
        if (!empty($configuration['clearCache'])) {
            // Extract the list of caches to clear
            $caches = GeneralUtility::trimExplode(
                    ',',
                    $configuration['clearCache'],
                    true
            );
            // Use DataHandler to clear the designated caches
            if (count($caches) > 0) {
                /** @var $tce DataHandler */
                $tce = GeneralUtility::makeInstance(DataHandler::class);
                $tce->start(array(), array());
                foreach ($caches as $cacheId) {
                    $tce->clear_cacheCmd($cacheId);
                }
            }
            unset($tce);
        }
    }
}