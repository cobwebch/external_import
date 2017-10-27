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

use Cobweb\ExternalImport\Validator\ControlConfigurationValidator;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ControlConfigurationValidatorTest extends FunctionalTestCase
{
    /**
     * @var array List of globals to exclude (contain closures which cannot be serialized)
     */
    protected $backupGlobalsBlacklist = array('TYPO3_LOADED_EXT', 'TYPO3_CONF_VARS');

    /**
     * @var ControlConfigurationValidator
     */
    protected $subject;

    public function setUp()
    {
        parent::setUp();
        $this->subject = GeneralUtility::makeInstance(ControlConfigurationValidator::class);
    }

    public function validConfigurationProvider()
    {
        return array(
                'Typical configuration for array type' => array(
                        array(
                                'data' => 'array',
                                'referenceUid' => 'external_id',
                                'pid' => 12
                        )
                ),
                'Typical configuration for xml type' => array(
                        array(
                                'data' => 'xml',
                                'nodetype' => 'foo',
                                'referenceUid' => 'external_id',
                                'pid' => 12
                        )
                )
        );
    }

    /**
     * @param array $configuration
     * @test
     * @dataProvider validConfigurationProvider
     */
    public function isValidReturnsTrueForValidConfiguration($configuration)
    {
        self::assertTrue(
                $this->subject->isValid(
                        'tt_content',
                        $configuration
                )
        );
    }

    public function invalidConfigurationProvider()
    {
        return array(
                'Missing data property' => array(
                        array(
                                'reference_uid' => 'external_id'
                        )
                ),
                'Invalid data property' => array(
                        array(
                                'data' => 'foo',
                                'reference_uid' => 'external_id'
                        )
                ),
                'Invalid connector property' => array(
                        array(
                                'data' => 'array',
                                'reference_uid' => 'external_id',
                                'connector' => time()
                        )
                ),
                'Missing reference_uid property' => array(
                        array(
                                'data' => 'array'
                        )
                )
        );
    }

    /**
     * @param array $configuration
     * @test
     * @dataProvider invalidConfigurationProvider
     */
    public function isValidReturnsFalseForInvalidConfiguration($configuration)
    {
        self::assertFalse(
                $this->subject->isValid(
                        'tt_content',
                        $configuration
                )
        );
    }

    public function invalidDataPropertyConfigurationProvider()
    {
        return array(
                'Missing data property' => array(
                        array()
                ),
                'Invalid data property' => array(
                        array(
                                'data' => 'foo'
                        )
                )
        );
    }

    /**
     * @param array $configuration
     * @test
     * @dataProvider invalidDataPropertyConfigurationProvider
     */
    public function validateDataPropertyWithInvalidValueRaisesError($configuration)
    {
        $this->subject->isValid(
                'tt_content',
                $configuration
        );
        $result = $this->subject->getResultForProperty('data');
        self::assertSame(
                FlashMessage::ERROR,
                $result['severity']
        );
    }

    /**
     * @test
     */
    public function validateConnectorPropertyWithInvalidValueRaisesError()
    {
        $this->subject->isValid(
                'tt_content',
                array(
                    // Some random connector name
                    'connector' => time()
                )
        );
        $result = $this->subject->getResultForProperty('connector');
        self::assertSame(
                FlashMessage::ERROR,
                $result['severity']
        );
    }

    public function invalidDataHandlerPropertyConfigurationProvider()
    {
        return array(
                'Not existing class' => array(
                        array(
                                'dataHandler' => 'Cobweb\\ExternalImport\\' . time()
                        )
                ),
                'Class not implementing proper interface' => array(
                        array(
                                'dataHandler' => \Cobweb\ExternalImport\Importer::class
                        )
                )
        );
    }

    /**
     * @param array $configuration
     * @test
     * @dataProvider invalidDataHandlerPropertyConfigurationProvider
     */
    public function validateDataHandlerPropertyWithInvalidValueRaisesNotice($configuration)
    {
        $this->subject->isValid(
                'tt_content',
                $configuration
        );
        $result = $this->subject->getResultForProperty('dataHandler');
        self::assertSame(
                FlashMessage::NOTICE,
                $result['severity']
        );
    }

    /**
     * @test
     */
    public function validateNodetypePropertyForXmlDataWithEmptyValueRaisesError()
    {
        $this->subject->isValid(
                'tt_content',
                array(
                        'data' => 'xml'
                )
        );
        $result = $this->subject->getResultForProperty('nodetype');
        self::assertSame(
                FlashMessage::ERROR,
                $result['severity']
        );
    }

    /**
     * @test
     */
    public function validateReferenceUidPropertyWithEmptyValueRaisesError()
    {
        $this->subject->isValid(
                'tt_content',
                array()
        );
        $result = $this->subject->getResultForProperty('referenceUid');
        self::assertSame(
                FlashMessage::ERROR,
                $result['severity']
        );
    }

    /**
     * @test
     */
    public function validatePriorityPropertyWithEmptyValueRaisesNotice()
    {
        $this->subject->isValid(
                'tt_content',
                array(
                        'connector' => 'foo'
                )
        );
        $result = $this->subject->getResultForProperty('priority');
        self::assertSame(
                FlashMessage::NOTICE,
                $result['severity']
        );
    }

    /**
     * @test
     */
    public function validatePidPropertyWithEmptyValueForRootTableRaisesNotice()
    {
        $this->subject->isValid(
                'be_users',
                array(
                    // NOTE: normally, configuration is parsed by the ConfigurationRepository and pid would
                    // be set to 0 if missing from configuration
                    'pid' => 0
                )
        );
        $result = $this->subject->getResultForProperty('pid');
        self::assertSame(
                FlashMessage::NOTICE,
                $result['severity']
        );
    }

    public function invalidPidPropertyConfigurationProvider()
    {
        return array(
                'Missing pid, non-root table' => array(
                        'tt_content',
                        array(
                            // NOTE: normally, configuration is parsed by the ConfigurationRepository and pid would
                            // be set to 0 if missing from configuration
                            'pid' => 0
                        )
                ),
                'Negative pid' => array(
                        'tt_content',
                        array(
                                'pid' => -12
                        )
                ),
                'Positive pid, root table' => array(
                        'be_users',
                        array(
                                'pid' => 12
                        )
                )
        );
    }

    /**
     * @param string $table Table name
     * @param array $configuration Configuration
     * @test
     * @dataProvider invalidPidPropertyConfigurationProvider
     */
    public function validatePidPropertyWithInvalidValueRaisesError($table, $configuration)
    {
        $this->subject->isValid(
                $table,
                $configuration
        );
        $result = $this->subject->getResultForProperty('pid');
        self::assertSame(
                FlashMessage::ERROR,
                $result['severity']
        );
    }

    /**
     * @test
     */
    public function validateUseColumnIndexPropertyWithInvalidValueRaisesError()
    {
        $this->subject->isValid(
                'tt_content',
                array(
                        'useColumnIndex' => 'foo'
                )
        );
        $result = $this->subject->getResultForProperty('useColumnIndex');
        self::assertSame(
                FlashMessage::ERROR,
                $result['severity']
        );
    }

    /**
     * @test
     */
    public function addResultAddsResults()
    {
        $results = array(
                'foo' => array(
                        'severity' => FlashMessage::WARNING,
                        'message' => 'Something went wrong'
                )
        );
        $this->subject->addResult(
                'foo',
                $results['foo']['message']
        );
        self::assertSame(
                $results,
                $this->subject->getAllResults()
        );
    }

    /**
     * @test
     */
    public function addResultForPropertyAddsResultsForProperty()
    {
        $results = array(
                'foo' => array(
                        'severity' => FlashMessage::WARNING,
                        'message' => 'Something went wrong'
                )
        );
        $this->subject->addResult(
                'foo',
                $results['foo']['message']
        );
        $resultForProperty = $this->subject->getResultForProperty('foo');
        self::assertSame(
                $results['foo'],
                $resultForProperty
        );
    }

    /**
     * @test
     */
    public function addResultForSeverityAddsResultsForSeverity()
    {
        $results = array(
                'foo' => array(
                        'severity' => FlashMessage::WARNING,
                        'message' => 'Something went wrong'
                )
        );
        $this->subject->addResult(
                'foo',
                $results['foo']['message']
        );
        $resultForProperty = $this->subject->getResultsForSeverity(FlashMessage::WARNING);
        self::assertSame(
                array(
                        'foo' => $results['foo']['message']
                ),
                $resultForProperty
        );
    }
}