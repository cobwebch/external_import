<?php
namespace Cobweb\ExternalImport\Tests\Unit\Validator;

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
use Cobweb\ExternalImport\Validator\ColumnConfigurationValidator;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class ColumnConfigurationValidatorTest extends FunctionalTestCase
{

    /**
     * @var ColumnConfigurationValidator
     */
    protected $subject;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    public function setUp()
    {
        parent::setUp();
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->subject = $this->objectManager->get(ColumnConfigurationValidator::class);
    }

    public function validConfigurationProvider()
    {
        return [
                'Data type "array": using property "field" (string)' => [
                        [
                                'data' => 'array',
                        ],
                        [
                                'col' => [
                                        'field' => 'foo'
                                ]
                        ]
                ],
                'Data type "array": using property "field" (positive integer)' => [
                        [
                                'data' => 'array',
                        ],
                        [
                                'col' => [
                                        'field' => 42
                                ]
                        ]
                ],
                'Data type "array": using property "field" (zero)' => [
                        [
                                'data' => 'array',
                        ],
                        [
                                'col' => [
                                        'field' => 0
                                ]
                        ]
                ],
                'Data type "array": using property "value" (number)' => [
                        [
                                'data' => 'array',
                        ],
                        [
                                'col' => [
                                        'value' => 17
                                ]
                        ]
                ],
                'Data type "array": using property "value" (string)' => [
                        [
                                'data' => 'array',
                        ],
                        [
                                'col' => [
                                        'value' => 'bar'
                                ]
                        ]
                ],
                'Data type "array": using property "arrayPath"' => [
                        [
                                'data' => 'array',
                        ],
                        [
                                'col' => [
                                        'arrayPath' => 'foo/bar'
                                ]
                        ]
                ],
                'Data type "xml": using property "field" (string)' => [
                        [
                                'data' => 'xml',
                        ],
                        [
                                'col' => [
                                        'field' => 'foo'
                                ]
                        ]
                ],
                'Data type "xml": using property "value" (number)' => [
                        [
                                'data' => 'xml',
                        ],
                        [
                                'col' => [
                                        'value' => 17
                                ]
                        ]
                ],
                'Data type "xml": using property "value" (string)' => [
                        [
                                'data' => 'xml',
                        ],
                        [
                                'col' => [
                                        'value' => 'bar'
                                ]
                        ]
                ],
                'Data type "xml": using property "attribute" (string)' => [
                        [
                                'data' => 'xml',
                        ],
                        [
                                'col' => [
                                        'field' => 'baz'
                                ]
                        ]
                ],
                'Data type "xml": using property "xpath" (string)' => [
                        [
                                'data' => 'xml',
                        ],
                        [
                                'col' => [
                                        'field' => 'hello'
                                ]
                        ]
                ]
        ];
    }

    /**
     * @param array $controlConfiguration
     * @param array $columnConfiguration
     * @test
     * @dataProvider validConfigurationProvider
     */
    public function isValidReturnsTrueForValidConfiguration($controlConfiguration, $columnConfiguration)
    {
        $configuration = $this->objectManager->get(Configuration::class);
        $configuration->setCtrlConfiguration($controlConfiguration);
        $configuration->setColumnConfiguration($columnConfiguration);
        self::assertTrue(
                $this->subject->isValid(
                        $configuration,
                        'col'
                )
        );
    }

    public function invalidConfigurationProvider()
    {
        return [
                'Data type "array": missing data-setting properties' => [
                        [
                                'data' => 'array'
                        ],
                        [],
                        FlashMessage::ERROR
                ],
                'Data type "xml": missing data-setting properties' => [
                        [
                                'data' => 'xml'
                        ],
                        [],
                        FlashMessage::NOTICE
                ],
                'Data type "array": conflicting data-setting properties' => [
                        [
                                'data' => 'array'
                        ],
                        [
                                'col' => [
                                        'value' => 42,
                                        'field' => 'foo'
                                ]
                        ],
                        FlashMessage::NOTICE
                ],
                'Data type "xml": conflicting data-setting properties' => [
                        [
                                'data' => 'xml'
                        ],
                        [
                                'col' => [
                                        'value' => 42,
                                        'xpath' => 'item'
                                ]
                        ],
                        FlashMessage::NOTICE
                ]
        ];
    }

    /**
     * @param array $controlConfiguration
     * @param array $columnConfiguration
     * @param int $severity
     * @test
     * @dataProvider invalidConfigurationProvider
     */
    public function isValidRaisesMessageForInvalidConfiguration($controlConfiguration, $columnConfiguration, $severity)
    {
        $configuration = $this->objectManager->get(Configuration::class);
        $configuration->setCtrlConfiguration($controlConfiguration);
        $configuration->setColumnConfiguration($columnConfiguration);
        $this->subject->isValid(
                $configuration,
                'col'
        );
        $results = $this->subject->getResults()->getAll();
        self::assertSame(
                $severity,
                $results['field']['severity']
        );
    }
}