<?php

declare(strict_types=1);

namespace Cobweb\ExternalImport\Tests\Functional\Validator;

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
use Cobweb\ExternalImport\Testing\FunctionalTestCaseWithDatabaseTools;
use Cobweb\ExternalImport\Validator\ColumnConfigurationValidator;
use Cobweb\ExternalImport\Validator\ValidationResult;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ColumnConfigurationValidatorTest extends FunctionalTestCaseWithDatabaseTools
{
    protected array $coreExtensionsToLoad = [
        'scheduler',
    ];

    protected array $testExtensionsToLoad = [
        'cobweb/svconnector',
        'cobweb/external_import',
    ];

    protected ColumnConfigurationValidator $subject;

    public function setUp(): void
    {
        parent::setUp();
        $this->initializeBackendUser();
        Bootstrap::initializeLanguageObject();

        $this->subject = new ColumnConfigurationValidator(new ValidationResult());
    }

    public static function validConfigurationProvider(): array
    {
        return [
            'Data type "array": using property "field" (string)' => [
                'generalConfiguration' => [
                    'data' => 'array',
                ],
                'columnConfiguration' => [
                    'col' => [
                        'field' => 'foo',
                    ],
                ],
            ],
            'Data type "array": using property "field" (positive integer)' => [
                'generalConfiguration' => [
                    'data' => 'array',
                ],
                'columnConfiguration' => [
                    'col' => [
                        'field' => 42,
                    ],
                ],
            ],
            'Data type "array": using property "field" (zero)' => [
                'generalConfiguration' => [
                    'data' => 'array',
                ],
                'columnConfiguration' => [
                    'col' => [
                        'field' => 0,
                    ],
                ],
            ],
            'Data type "array": using column property "value" (number)' => [
                'generalConfiguration' => [
                    'data' => 'array',
                ],
                'columnConfiguration' => [
                    'col' => [
                        'value' => 17,
                    ],
                ],
            ],
            'Data type "array": using transformations property "value" (number)' => [
                'generalConfiguration' => [
                    'data' => 'array',
                ],
                'columnConfiguration' => [
                    'col' => [
                        'transformations' => [
                            10 => [
                                'value' => 17,
                            ],
                        ],
                    ],
                ],
            ],
            'Data type "array": using column property "value" (string)' => [
                'generalConfiguration' => [
                    'data' => 'array',
                ],
                'columnConfiguration' => [
                    'col' => [
                        'value' => 'bar',
                    ],
                ],
            ],
            'Data type "array": using transformations property "value" (string)' => [
                'generalConfiguration' => [
                    'data' => 'array',
                ],
                'columnConfiguration' => [
                    'col' => [
                        'transformations' => [
                            10 => [
                                'value' => 'bar',
                            ],
                        ],
                    ],
                ],
            ],
            'Data type "array": using property "arrayPath"' => [
                'generalConfiguration' => [
                    'data' => 'array',
                ],
                'columnConfiguration' => [
                    'col' => [
                        'arrayPath' => 'foo/bar',
                    ],
                ],
            ],
            'Data type "xml": using property "field" (string)' => [
                'generalConfiguration' => [
                    'data' => 'xml',
                ],
                'columnConfiguration' => [
                    'col' => [
                        'field' => 'foo',
                    ],
                ],
            ],
            'Data type "xml": using column property "value" (number)' => [
                'generalConfiguration' => [
                    'data' => 'xml',
                ],
                'columnConfiguration' => [
                    'col' => [
                        'value' => 17,
                    ],
                ],
            ],
            'Data type "xml": using transformations property "value" (number)' => [
                'generalConfiguration' => [
                    'data' => 'xml',
                ],
                'columnConfiguration' => [
                    'col' => [
                        'transformations' => [
                            10 => [
                                'value' => 17,
                            ],
                        ],
                    ],
                ],
            ],
            'Data type "xml": using column property "value" (string)' => [
                'generalConfiguration' => [
                    'data' => 'xml',
                ],
                'columnConfiguration' => [
                    'col' => [
                        'value' => 'bar',
                    ],
                ],
            ],
            'Data type "xml": using transformations property "value" (string)' => [
                'generalConfiguration' => [
                    'data' => 'xml',
                ],
                'columnConfiguration' => [
                    'col' => [
                        'transformations' => [
                            10 => [
                                'value' => 'bar',
                            ],
                        ],
                    ],
                ],
            ],
            'Data type "xml": using property "attribute" (string)' => [
                'generalConfiguration' => [
                    'data' => 'xml',
                ],
                'columnConfiguration' => [
                    'col' => [
                        'field' => 'baz',
                    ],
                ],
            ],
            'Data type "xml": using property "xpath" (string)' => [
                'generalConfiguration' => [
                    'data' => 'xml',
                ],
                'columnConfiguration' => [
                    'col' => [
                        'field' => 'hello',
                    ],
                ],
            ],
            'Children definition' => [
                'generalConfiguration' => [
                    'data' => 'array',
                ],
                'columnConfiguration' => [
                    'col' => [
                        'field' => 'foo',
                        'children' => [
                            'table' => 'foo',
                            'columns' => [
                                'column1' => [
                                    'value' => 'bar',
                                ],
                                'column2' => [
                                    'field' => 'baz',
                                ],
                            ],
                            'controlColumnsForUpdate' => 'column1',
                            'controlColumnsForDelete' => 'column2',
                        ],
                    ],
                ],
            ],
            'Substructure fields: valid structure and properties for "array" data type' => [
                'generalConfiguration' => [
                    'data' => 'array',
                ],
                'columnConfiguration' => [
                    'col' => [
                        'field' => 'foo',
                        'substructureFields' => [
                            'foo' => [
                                'arrayPath' => 'bar',
                            ],
                        ],
                    ],
                ],
            ],
            'Substructure fields: valid structure and properties for "xml" data type' => [
                'generalConfiguration' => [
                    'data' => 'xml',
                ],
                'columnConfiguration' => [
                    'col' => [
                        'substructureFields' => [
                            'foo' => [
                                'xpath' => 'bar',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    #[Test] #[DataProvider('validConfigurationProvider')]
    public function isValidReturnsTrueForValidConfiguration(
        array $generalConfiguration,
        array $columnConfiguration
    ): void {
        $configuration = GeneralUtility::makeInstance(Configuration::class);
        $configuration->setGeneralConfiguration($generalConfiguration);
        $configuration->setColumnConfiguration($columnConfiguration);
        self::assertTrue(
            $this->subject->isValid(
                $configuration,
                'col'
            ),
            serialize($this->subject->getResults()->getAll())
        );
    }

    public static function invalidConfigurationProvider(): array
    {
        return [
            'Data type "array": missing data-setting properties' => [
                'generalConfiguration' => [
                    'data' => 'array',
                ],
                'columnConfiguration' => [],
                'severity' => ContextualFeedbackSeverity::ERROR,
            ],
            'Data type "xml": missing data-setting properties' => [
                'generalConfiguration' => [
                    'data' => 'xml',
                ],
                'columnConfiguration' => [],
                'severity' => ContextualFeedbackSeverity::NOTICE,
            ],
            'Data type "array": conflicting data-setting properties' => [
                'generalConfiguration' => [
                    'data' => 'array',
                ],
                'columnConfiguration' => [
                    'col' => [
                        'field' => 'foo',
                        'transformations' => [
                            10 => [
                                'value' => 42,
                            ],
                        ],
                    ],
                ],
                'severity' => ContextualFeedbackSeverity::NOTICE,
            ],
            'Data type "xml": conflicting data-setting properties' => [
                'generalConfiguration' => [
                    'data' => 'xml',
                ],
                'columnConfiguration' => [
                    'col' => [
                        'xpath' => 'item',
                        'transformations' => [
                            10 => [
                                'value' => 42,
                            ],
                        ],
                    ],
                ],
                'severity' => ContextualFeedbackSeverity::NOTICE,
            ],
            'Children definition: no "table" property' => [
                'generalConfiguration' => [
                    'data' => 'array',
                ],
                'columnConfiguration' => [
                    'col' => [
                        'children' => [
                            'columns' => [
                                'column1' => [
                                    'value' => 'bar',
                                ],
                                'column2' => [
                                    'field' => 'baz',
                                ],
                            ],
                            'controlColumnsForUpdate' => 'column1',
                            'controlColumnsForDelete' => 'column1',
                        ],
                    ],
                ],
                'severity' => ContextualFeedbackSeverity::ERROR,
            ],
            'Children definition: no "columns" property' => [
                'generalConfiguration' => [
                    'data' => 'array',
                ],
                'columnConfiguration' => [
                    'col' => [
                        'children' => [
                            'table' => 'foo',
                        ],
                    ],
                ],
                'severity' => ContextualFeedbackSeverity::ERROR,
            ],
            'Children definition: "columns" sub-property not an array' => [
                'generalConfiguration' => [
                    'data' => 'array',
                ],
                'columnConfiguration' => [
                    'col' => [
                        'children' => [
                            'table' => 'foo',
                            'columns' => [
                                'column1' => 'bar',
                            ],
                            'controlColumnsForUpdate' => 'column1',
                            'controlColumnsForDelete' => 'column1',
                        ],
                    ],
                ],
                'severity' => ContextualFeedbackSeverity::ERROR,
            ],
            'Children definition: wrong "columns" sub-property' => [
                'generalConfiguration' => [
                    'data' => 'array',
                ],
                'columnConfiguration' => [
                    'col' => [
                        'children' => [
                            'table' => 'foo',
                            'columns' => [
                                'column1' => [
                                    'bar' => 'baz',
                                ],
                            ],
                            'controlColumnsForUpdate' => 'column1',
                            'controlColumnsForDelete' => 'column1',
                        ],
                    ],
                ],
                'severity' => ContextualFeedbackSeverity::ERROR,
            ],
            'Children definition: wrong "controlColumnsForUpdate" sub-property' => [
                'generalConfiguration' => [
                    'data' => 'array',
                ],
                'columnConfiguration' => [
                    'col' => [
                        'children' => [
                            'table' => 'foo',
                            'columns' => [
                                'column1' => [
                                    'bar' => 'baz',
                                ],
                            ],
                            'controlColumnsForUpdate' => 'columnX',
                        ],
                    ],
                ],
                'severity' => ContextualFeedbackSeverity::ERROR,
            ],
            'Children definition: wrong "controlColumnsForDelete" sub-property' => [
                'generalConfiguration' => [
                    'data' => 'array',
                ],
                'columnConfiguration' => [
                    'col' => [
                        'children' => [
                            'table' => 'foo',
                            'columns' => [
                                'column1' => [
                                    'bar' => 'baz',
                                ],
                            ],
                            'controlColumnsForDelete' => 'columnX',
                        ],
                    ],
                ],
                'severity' => ContextualFeedbackSeverity::ERROR,
            ],
            'Substructure fields: wrong structure' => [
                'generalConfiguration' => [
                    'data' => 'array',
                ],
                'columnConfiguration' => [
                    'col' => [
                        'field' => 'foo',
                        'substructureFields' => [
                            'field' => 'foo',
                        ],
                    ],
                ],
                'severity' => ContextualFeedbackSeverity::ERROR,
            ],
            'Substructure fields: empty configuration for "array" data type' => [
                'generalConfiguration' => [
                    'data' => 'array',
                ],
                'columnConfiguration' => [
                    'col' => [
                        'field' => 'foo',
                        'substructureFields' => [
                            'foo' => [],
                        ],
                    ],
                ],
                'severity' => ContextualFeedbackSeverity::ERROR,
            ],
            'Substructure fields: invalid properties for "array" data type' => [
                'generalConfiguration' => [
                    'data' => 'array',
                ],
                'columnConfiguration' => [
                    'col' => [
                        'field' => 'foo',
                        'substructureFields' => [
                            'foo' => [
                                'xpath' => 'bar',
                            ],
                        ],
                    ],
                ],
                'severity' => ContextualFeedbackSeverity::ERROR,
            ],
            'Substructure fields: invalid properties for "xml" data type' => [
                'generalConfiguration' => [
                    'data' => 'xml',
                ],
                'columnConfiguration' => [
                    'col' => [
                        'field' => 'foo',
                        'substructureFields' => [
                            'foo' => [
                                'arrayPath' => 'bar',
                            ],
                        ],
                    ],
                ],
                'severity' => ContextualFeedbackSeverity::ERROR,
            ],
        ];
    }

    #[Test] #[DataProvider('invalidConfigurationProvider')]
    public function isValidRaisesMessageForInvalidConfiguration(
        array $generalConfiguration,
        array $columnConfiguration,
        ContextualFeedbackSeverity $severity
    ): void {
        $configuration = GeneralUtility::makeInstance(Configuration::class);
        $configuration->setGeneralConfiguration($generalConfiguration);
        $configuration->setColumnConfiguration($columnConfiguration);
        $this->subject->isValid(
            $configuration,
            'col'
        );
        $results = $this->subject->getResults()->getForPropertyAndSeverity('field', $severity);
        self::assertGreaterThan(
            0,
            count($results),
            serialize($this->subject->getResults()->getAll())
        );
    }
}
