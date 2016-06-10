<?php
namespace Cobweb\ExternalImport\ViewHelpers\Be;

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
use TYPO3\CMS\Core\Tests\BaseTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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

    /**
     * @test
     */
    public function validateFieldPropertyWithEmptyValueRaisesError()
    {
        $this->subject->isValid(
                'tt_content',
                array(
                        'data' => 'array'
                ),
                array()
        );
        $result = $this->subject->getResultForProperty('field');
        self::assertSame(
                FlashMessage::ERROR,
                $result['severity']
        );
    }
}