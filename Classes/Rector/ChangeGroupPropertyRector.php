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

namespace Cobweb\ExternalImport\Rector;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Scalar\String_;
use Ssch\TYPO3Rector\Rector\AbstractTcaRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Rector rule for changing the "group" (string) property to the "groups" (array) property
 * in the general configuration array of External Import
 */
class ChangeGroupPropertyRector extends AbstractTcaRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Replace "group" (string) property with "groups" (array) property in External Import configurations.',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
return [
    'external' => [
        'general' => [
            'group' => 'Foo',
        ],
    ],
];
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
return [
    'external' => [
        'general' => [
            'groups' => [
                'Foo'
            ],
        ],
    ],
];
CODE_SAMPLE
                ),
            ]
        );
    }

    /**
     * @param Array_ $node
     * @return Node|null
     */
    public function refactor(Node $node): ?Node
    {
        $this->resetInnerState();
        $this->hasAstBeenChanged = false;
        // Full TCA
        if ($this->isFullTcaDefinition($node)) {
            $externalConfigurationArray = $this->extractSubArrayByKey($node, 'external');
            $generalExternalConfigurationArray = $this->extractSubArrayByKey($externalConfigurationArray, 'general');
            if ($generalExternalConfigurationArray instanceof Array_) {
                $this->refactorGeneralConfiguration($generalExternalConfigurationArray);
            }
            // Only an external general configuration
        } elseif ($this->isExternalGeneralConfiguration($node)) {
            $this->refactorGeneralConfiguration($node);
        } else {
            // Try looping on the first level to check if it might be an array for external general configurations
            foreach ($node->items as $item) {
                if ($item instanceof Array_ && $this->isExternalGeneralConfiguration($item)) {
                    $this->refactorGeneralConfiguration($item);
                }
            }
        }
        return $this->hasAstBeenChanged ? $node : null;
    }

    /**
     * Try to guess if this is an External Import general configuration or not.
     * There's no real way of knowing except that the "data" property is mandatory, so we're basing ourselves
     * on finding that or not
     */
    protected function isExternalGeneralConfiguration(Array_ $generalConfigurationArray): bool
    {
        foreach ($generalConfigurationArray->items as $arrayItem) {
            if ($arrayItem->key instanceof String_ && $arrayItem->key->value === 'data') {
                return true;
            }
        }
        return false;
    }

    /**
     * Change the "group" string property to the "groups" array property
     */
    protected function refactorGeneralConfiguration(Array_ $generalExternalConfigurationArray): void
    {
        foreach ($generalExternalConfigurationArray->items as $item) {
            if ($item->key instanceof String_ && $item->key->value === 'group') {
                $newGroupConfiguration = new ArrayItem(
                    new Array_(
                        [
                            new ArrayItem(
                                $item->value,
                            ),
                        ],
                    ),
                    new String_('groups')
                );
                $this->removeArrayItemFromArrayByKey($generalExternalConfigurationArray, 'group');
                $generalExternalConfigurationArray->items[] = $newGroupConfiguration;
                $this->hasAstBeenChanged = true;
            }
        }
    }
}
