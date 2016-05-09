<?php
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

use Cobweb\ExternalImport\Importer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface;

/**
 * Processes the connector parameters of an external import configuration
 * and makes them available variable.
 *
 * @package Cobweb\ExternalImport\ViewHelpers
 */
class ProcessedParametersViewHelper extends AbstractViewHelper implements CompilableInterface
{
    /**
     * Process parameters and set them as variable.
     *
     * @param string $table Name of the table being displayed
     * @param string $index Index of the configuration being displayed
     * @param array $parameters Connector parameters
     * @return string Rendered string
     */
    public function render($table, $index, array $parameters)
    {
        return static::renderStatic(
            array(
                    'table' => $table,
                    'index' => $index,
                    'parameters' => $parameters
            ),
            $this->buildRenderChildrenClosure(),
            $this->renderingContext
        );
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
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        // Call any hook that may be declared to process parameters
        $processedParameters = array();
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['external_import']['processParameters'])) {
            // The hook needs an instance of the importer class
            /** @var Importer $importer */
            $importer = GeneralUtility::makeInstance(Importer::class);
            $importer->setTableName($arguments['table']);
            $importer->setIndex($arguments['index']);
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['external_import']['processParameters'] as $className) {
                $preProcessor = GeneralUtility::getUserObj($className);
                $processedParameters = $preProcessor->processParameters(
                        $arguments['parameters'],
                        $importer
                );
            }
        }

        $templateVariableContainer = $renderingContext->getTemplateVariableContainer();
        $templateVariableContainer->add('processedParameters', $processedParameters);

        $output = $renderChildrenClosure();

        $templateVariableContainer->remove('processedParameters');

        return $output;
    }

}
