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
use Cobweb\ExternalImport\Event\ProcessConnectorParametersEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Processes the connector parameters of an external import configuration
 * and makes them available variable.
 */
class ProcessedParametersViewHelper extends AbstractViewHelper
{
    /**
     * Do not escape output of child nodes.
     *
     * @var bool
     */
    protected $escapeOutput = false;

    public function __construct(
        protected EventDispatcherInterface $eventDispatcher,
    ) {}

    /**
     * Initializes the arguments of the ViewHelper.
     */
    public function initializeArguments(): void
    {
        $this->registerArgument('configuration', Configuration::class, 'The configuration object to handle', true);
    }

    /**
     * Process parameters and set them as variable.
     */
    public function render(): string
    {
        /** @var Configuration $configuration */
        $configuration = $this->arguments['configuration'];

        $event = $this->eventDispatcher->dispatch(
            new ProcessConnectorParametersEvent(
                $configuration->getGeneralConfigurationProperty('parameters'),
                $configuration
            )
        );
        $processedParameters = $event->getParameters();

        $templateVariableContainer = $this->renderingContext->getVariableProvider();
        $templateVariableContainer->add('processedParameters', $processedParameters);

        $output = $this->renderChildren();

        $templateVariableContainer->remove('processedParameters');

        return $output;
    }
}
