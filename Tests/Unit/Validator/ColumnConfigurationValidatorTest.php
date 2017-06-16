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

use Cobweb\ExternalImport\Validator\ColumnConfigurationValidator;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\BaseTestCase;

class ColumnConfigurationValidatorTest extends BaseTestCase
{
    /**
     * @var array List of globals to exclude (contain closures which cannot be serialized)
     */
    protected $backupGlobalsBlacklist = array('TYPO3_LOADED_EXT', 'TYPO3_CONF_VARS');

    /**
     * @var ColumnConfigurationValidator
     */
    protected $subject;

    public function setUp()
    {
        parent::setUp();
        $this->subject = GeneralUtility::makeInstance(ColumnConfigurationValidator::class);
    }

    public function validConfigurationProvider()
    {
        return array(
                'Data type "array": using property "field" (string)' => array(
                        array(
                                'data' => 'array',
                        ),
                        array(
                                'field' => 'foo'
                        )
                ),
                'Data type "array": using property "field" (positive integer)' => array(
                        array(
                                'data' => 'array',
                        ),
                        array(
                                'field' => 42
                        )
                ),
                'Data type "array": using property "field" (zero)' => array(
                        array(
                                'data' => 'array',
                        ),
                        array(
                                'field' => 0
                        )
                ),
                'Data type "array": using property "value" (number)' => array(
                        array(
                                'data' => 'array',
                        ),
                        array(
                                'value' => 17
                        )
                ),
                'Data type "array": using property "value" (string)' => array(
                        array(
                                'data' => 'array',
                        ),
                        array(
                                'value' => 'bar'
                        )
                ),
                'Data type "xml": using property "field" (string)' => array(
                        array(
                                'data' => 'xml',
                        ),
                        array(
                                'field' => 'foo'
                        )
                ),
                'Data type "xml": using property "value" (number)' => array(
                        array(
                                'data' => 'xml',
                        ),
                        array(
                                'value' => 17
                        )
                ),
                'Data type "xml": using property "value" (string)' => array(
                        array(
                                'data' => 'xml',
                        ),
                        array(
                                'value' => 'bar'
                        )
                ),
                'Data type "xml": using property "attribute" (string)' => array(
                        array(
                                'data' => 'xml',
                        ),
                        array(
                                'field' => 'baz'
                        )
                ),
                'Data type "xml": using property "xpath" (string)' => array(
                        array(
                                'data' => 'xml',
                        ),
                        array(
                                'field' => 'hello'
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
        self::assertTrue(
                $this->subject->isValid(
                        'tt_content',
                        $controlConfiguration,
                        $columnConfiguration
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
                                'value' => 42,
                                'field' => 'foo'
                        ),
                        FlashMessage::WARNING
                ),
                'Data type "xml": conflicting data-setting properties' => array(
                        array(
                                'data' => 'xml'
                        ),
                        array(
                                'value' => 42,
                                'xpath' => 'item'
                        ),
                        FlashMessage::WARNING
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
    public function validateDataSettingPropertiesRaisesMessage($controlConfiguration, $columnConfiguration, $severity)
    {
        $this->subject->isValid(
                'tt_content',
                $controlConfiguration,
                $columnConfiguration
        );
        $result = $this->subject->getResultForProperty('field');
        self::assertSame(
                $severity,
                $result['severity']
        );
    }
}