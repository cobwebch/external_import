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
        return array(
                'Data type "array": using property "field" (string)' => array(
                        array(
                                'data' => 'array',
                        ),
                        array(
                                'col' => array(
                                        'field' => 'foo'
                                )
                        )
                ),
                'Data type "array": using property "field" (positive integer)' => array(
                        array(
                                'data' => 'array',
                        ),
                        array(
                                'col' => array(
                                        'field' => 42
                                )
                        )
                ),
                'Data type "array": using property "field" (zero)' => array(
                        array(
                                'data' => 'array',
                        ),
                        array(
                                'col' => array(
                                        'field' => 0
                                )
                        )
                ),
                'Data type "array": using property "value" (number)' => array(
                        array(
                                'data' => 'array',
                        ),
                        array(
                                'col' => array(
                                        'value' => 17
                                )
                        )
                ),
                'Data type "array": using property "value" (string)' => array(
                        array(
                                'data' => 'array',
                        ),
                        array(
                                'col' => array(
                                        'value' => 'bar'
                                )
                        )
                ),
                'Data type "xml": using property "field" (string)' => array(
                        array(
                                'data' => 'xml',
                        ),
                        array(
                                'col' => array(
                                        'field' => 'foo'
                                )
                        )
                ),
                'Data type "xml": using property "value" (number)' => array(
                        array(
                                'data' => 'xml',
                        ),
                        array(
                                'col' => array(
                                        'value' => 17
                                )
                        )
                ),
                'Data type "xml": using property "value" (string)' => array(
                        array(
                                'data' => 'xml',
                        ),
                        array(
                                'col' => array(
                                        'value' => 'bar'
                                )
                        )
                ),
                'Data type "xml": using property "attribute" (string)' => array(
                        array(
                                'data' => 'xml',
                        ),
                        array(
                                'col' => array(
                                        'field' => 'baz'
                                )
                        )
                ),
                'Data type "xml": using property "xpath" (string)' => array(
                        array(
                                'data' => 'xml',
                        ),
                        array(
                                'col' => array(
                                        'field' => 'hello'
                                )
                        )
                ),
        );
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
        return array(
                'Data type "array": missing data-setting properties' => array(
                        array(
                                'data' => 'array'
                        ),
                        array(),
                        FlashMessage::ERROR
                ),
                'Data type "xml": missing data-setting properties' => array(
                        array(
                                'data' => 'xml'
                        ),
                        array(),
                        FlashMessage::NOTICE
                ),
                'Data type "array": conflicting data-setting properties' => array(
                        array(
                                'data' => 'array'
                        ),
                        array(
                                'col' => array(
                                        'value' => 42,
                                        'field' => 'foo'
                                )
                        ),
                        FlashMessage::NOTICE
                ),
                'Data type "xml": conflicting data-setting properties' => array(
                        array(
                                'data' => 'xml'
                        ),
                        array(
                                'col' => array(
                                        'value' => 42,
                                        'xpath' => 'item'
                                )
                        ),
                        FlashMessage::NOTICE
                ),
        );
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