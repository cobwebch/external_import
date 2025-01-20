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
use Cobweb\ExternalImport\Importer;
use Cobweb\ExternalImport\Testing\FunctionalTestCaseWithDatabaseTools;
use Cobweb\ExternalImport\Utility\StepUtility;
use Cobweb\ExternalImport\Validator\GeneralConfigurationValidator;
use Cobweb\ExternalImport\Validator\ValidationResult;
use Cobweb\Svconnector\Registry\ConnectorRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class GeneralConfigurationValidatorTest extends FunctionalTestCaseWithDatabaseTools
{
    protected array $coreExtensionsToLoad = [
        'scheduler',
    ];

    protected array $testExtensionsToLoad = [
        'cobweb/external_import',
        'cobweb/svconnector',
    ];

    protected GeneralConfigurationValidator $subject;

    public function setUp(): void
    {
        parent::setUp();
        $this->initializeBackendUser();
        Bootstrap::initializeLanguageObject();

        $this->subject = new GeneralConfigurationValidator(
            new ValidationResult(),
            new StepUtility(),
            new ConnectorRegistry([])
        );
    }

    public static function validConfigurationProvider(): array
    {
        return [
            'Typical configuration for array type' => [
                'configuration' => [
                    'data' => 'array',
                    'referenceUid' => 'external_id',
                    'pid' => 12,
                ],
            ],
            'Typical configuration for xml type (nodetype)' => [
                'configuration' => [
                    'data' => 'xml',
                    'nodetype' => 'foo',
                    'referenceUid' => 'external_id',
                    'pid' => 12,
                ],
            ],
            'Typical configuration for xml type (nodepath)' => [
                'configuration' => [
                    'data' => 'xml',
                    'nodepath' => '//foo',
                    'referenceUid' => 'external_id',
                    'pid' => 12,
                ],
            ],
        ];
    }

    #[Test] #[DataProvider('validConfigurationProvider')]
    public function isValidReturnsTrueForValidConfiguration(array $configuration): void
    {
        self::assertTrue(
            $this->subject->isValid(
                $this->prepareConfigurationObject(
                    'tt_content',
                    $configuration
                )
            )
        );
    }

    public static function invalidConfigurationProvider(): array
    {
        return [
            'Missing data property' => [
                'configuration' => [
                    'reference_uid' => 'external_id',
                ],
            ],
            'Invalid data property' => [
                'configuration' => [
                    'data' => 'foo',
                    'reference_uid' => 'external_id',
                ],
            ],
            'Invalid connector property' => [
                'configuration' => [
                    'data' => 'array',
                    'reference_uid' => 'external_id',
                    'connector' => uniqid('', true),
                ],
            ],
            'Missing reference_uid property' => [
                'configuration' => [
                    'data' => 'array',
                ],
            ],
        ];
    }

    #[Test] #[DataProvider('invalidConfigurationProvider')]
    public function isValidReturnsFalseForInvalidConfiguration(array $configuration): void
    {
        self::assertFalse(
            $this->subject->isValid(
                $this->prepareConfigurationObject(
                    'tt_content',
                    $configuration
                )
            )
        );
    }

    public static function invalidDataPropertyConfigurationProvider(): array
    {
        return [
            'Missing data property' => [
                'configuration' => [],
            ],
            'Invalid data property' => [
                'configuration' => [
                    'data' => 'foo',
                ],
            ],
        ];
    }

    #[Test] #[DataProvider('invalidDataPropertyConfigurationProvider')]
    public function validateDataPropertyWithInvalidValueRaisesError(array $configuration): void
    {
        $this->subject->isValid(
            $this->prepareConfigurationObject(
                'tt_content',
                $configuration
            )
        );
        $results = $this->subject->getResults()->getForProperty('data');
        self::assertSame(
            ContextualFeedbackSeverity::ERROR,
            $results[0]['severity']
        );
    }

    #[Test]
    public function validateConnectorPropertyWithInvalidValueRaisesError(): void
    {
        $this->subject->isValid(
            $this->prepareConfigurationObject(
                'tt_content',
                [
                    'data' => 'array',
                    // Some random connector name
                    'connector' => uniqid('', true),
                ]
            )
        );
        $results = $this->subject->getResults()->getForProperty('connector');
        self::assertSame(
            ContextualFeedbackSeverity::ERROR,
            $results[0]['severity']
        );
    }

    public static function invalidDataHandlerPropertyConfigurationProvider(): array
    {
        return [
            'Not existing class' => [
                'configuration' => [
                    'data' => 'array',
                    'dataHandler' => 'Cobweb\\ExternalImport\\' . time(),
                ],
            ],
            'Class not implementing proper interface' => [
                'configuration' => [
                    'data' => 'array',
                    'dataHandler' => Importer::class,
                ],
            ],
        ];
    }

    #[Test] #[DataProvider('invalidDataHandlerPropertyConfigurationProvider')]
    public function validateDataHandlerPropertyWithInvalidValueRaisesNotice(array $configuration): void
    {
        $this->subject->isValid(
            $this->prepareConfigurationObject(
                'tt_content',
                $configuration
            )
        );
        $results = $this->subject->getResults()->getForProperty('dataHandler');
        self::assertSame(
            ContextualFeedbackSeverity::NOTICE,
            $results[0]['severity']
        );
    }

    #[Test]
    public function validateNodetypePropertyForXmlDataWithEmptyValueRaisesError(): void
    {
        $this->subject->isValid(
            $this->prepareConfigurationObject(
                'tt_content',
                [
                    'data' => 'xml',
                ]
            )
        );
        $results = $this->subject->getResults()->getForProperty('nodetype');
        self::assertSame(
            ContextualFeedbackSeverity::ERROR,
            $results[0]['severity']
        );
    }

    #[Test]
    public function validateReferenceUidPropertyWithEmptyValueRaisesError(): void
    {
        $this->subject->isValid(
            $this->prepareConfigurationObject(
                'tt_content',
                [
                    'data' => 'array',
                ]
            )
        );
        $results = $this->subject->getResults()->getForProperty('referenceUid');
        self::assertSame(
            ContextualFeedbackSeverity::ERROR,
            $results[0]['severity']
        );
    }

    #[Test]
    public function validatePriorityPropertyWithEmptyValueRaisesNotice(): void
    {
        $this->subject->isValid(
            $this->prepareConfigurationObject(
                'tt_content',
                [
                    'connector' => 'csv',
                    'data' => 'array',
                ]
            )
        );
        $results = $this->subject->getResults()->getForProperty('priority');
        self::assertSame(
            ContextualFeedbackSeverity::NOTICE,
            $results[0]['severity']
        );
    }

    #[Test]
    public function validatePidPropertyWithEmptyValueForRootTableRaisesNotice(): void
    {
        $this->subject->isValid(
            $this->prepareConfigurationObject(
                'be_users',
                [
                    'data' => 'array',
                    // NOTE: normally, configuration is parsed by the ConfigurationRepository and pid would
                    // be set to 0 if missing from configuration
                    'pid' => 0,
                ]
            )
        );
        $results = $this->subject->getResults()->getForProperty('pid');
        self::assertSame(
            ContextualFeedbackSeverity::NOTICE,
            $results[0]['severity']
        );
    }

    public static function invalidPidPropertyConfigurationProvider(): array
    {
        return [
            'Missing pid, non-root table' => [
                'table' => 'tt_content',
                'configuration' => [
                    'data' => 'array',
                    // NOTE: normally, configuration is parsed by the ConfigurationRepository and pid would
                    // be set to 0 if missing from configuration
                    'pid' => 0,
                ],
            ],
            'Negative pid' => [
                'table' => 'tt_content',
                'configuration' => [
                    'data' => 'array',
                    'pid' => -12,
                ],
            ],
            'Positive pid, root table' => [
                'table' => 'be_users',
                'configuration' => [
                    'data' => 'array',
                    'pid' => 12,
                ],
            ],
        ];
    }

    #[Test] #[DataProvider('invalidPidPropertyConfigurationProvider')]
    public function validatePidPropertyWithInvalidValueRaisesError(string $table, array $configuration): void
    {
        $this->subject->isValid(
            $this->prepareConfigurationObject(
                $table,
                $configuration
            )
        );
        $results = $this->subject->getResults()->getForProperty('pid');
        self::assertSame(
            ContextualFeedbackSeverity::ERROR,
            $results[0]['severity']
        );
    }

    #[Test]
    public function validateUseColumnIndexPropertyWithInvalidValueRaisesError(): void
    {
        $this->subject->isValid(
            $this->prepareConfigurationObject(
                'tt_content',
                [
                    'data' => 'array',
                    'useColumnIndex' => 'foo',
                ]
            )
        );
        $results = $this->subject->getResults()->getForProperty('useColumnIndex');
        self::assertSame(
            ContextualFeedbackSeverity::ERROR,
            $results[0]['severity']
        );
    }

    #[Test]
    public function validateColumnsOrderPropertyWithDuplicateValuesRaisesNotice(): void
    {
        $this->subject->isValid(
            $this->prepareConfigurationObject(
                'tt_content',
                [
                    'data' => 'array',
                    'columnsOrder' => 'bb, aa, aa',
                ],
                [
                    'aa' => [
                        'foo' => 'bar',
                    ],
                    'bb' => [
                        'foo' => 'bar',
                    ],
                ]
            )
        );
        $results = $this->subject->getResults()->getForProperty('columnsOrder');
        self::assertSame(
            ContextualFeedbackSeverity::NOTICE,
            $results[0]['severity']
        );
    }

    /**
     * Prepares a configuration object with the usual parameters used in this test suite.
     */
    protected function prepareConfigurationObject(string $table, array $configuration, array $columnConfiguration = []): Configuration
    {
        $configurationObject = GeneralUtility::makeInstance(Configuration::class);
        $configurationObject->setTable($table);
        $configurationObject->setGeneralConfiguration($configuration);
        $configurationObject->setColumnConfiguration($columnConfiguration);
        return $configurationObject;
    }

    public function tearDown(): void
    {
        parent::tearDown();
        restore_error_handler();
    }
}
