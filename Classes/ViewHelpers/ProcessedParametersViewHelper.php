<?php

declare(strict_types=1);

namespace Cobweb\ExternalImport\ViewHelpers;

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

use Cobweb\ExternalImport\Domain\Model\Configuration;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Processes the connector parameters of an external import configuration
 * and makes them available variable.
 *
 * @package Cobweb\ExternalImport\ViewHelpers
 */
class ProcessedParametersViewHelper extends AbstractViewHelper
{
    /**
     * Do not escape output of child nodes.
     *
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * Initializes the arguments of the ViewHelper.
     *
     * @return void
     */
    public function initializeArguments(): void
    {
        $this->registerArgument('configuration', Configuration::class, 'The configuration object to handle', true);
    }

    /**
     * Process parameters and set them as variable.
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return string
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ): string {
        /** @var Configuration $configuration */
        $configuration = $arguments['configuration'];

        $eventDispatcher = GeneralUtility::getContainer()->get(EventDispatcherInterface::class);
        $event = $eventDispatcher->dispatch(
            new \Cobweb\ExternalImport\Event\ProcessConnectorParametersEvent(
                $configuration->getGeneralConfigurationProperty('parameters'),
                $configuration
            )
        );
        $processedParameters = $event->getParameters();

        // Call any hook that may be declared to process parameters
        // Using a hook is deprecated
        // TODO: remove in the next major version
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['external_import']['processParameters'])) {
            trigger_error('Hook "processParameters" is deprecated. Use \Cobweb\ExternalImport\Event\ProcessConnectorParametersEvent instead.', E_USER_DEPRECATED);
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['external_import']['processParameters'] as $className) {
                $preProcessor = GeneralUtility::makeInstance($className);
                $processedParameters = $preProcessor->processParameters(
                    $configuration->getGeneralConfigurationProperty('parameters'),
                    $configuration
                );
            }
        }

        $templateVariableContainer = $renderingContext->getVariableProvider();
        $templateVariableContainer->add('processedParameters', $processedParameters);

        $output = $renderChildrenClosure();

        $templateVariableContainer->remove('processedParameters');

        return $output;
    }

}
