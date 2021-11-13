<?php
namespace Cobweb\ExternalImport\Tests\Unit\Utility;

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
use Cobweb\ExternalImport\Utility\StepUtility;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Cobweb\ExternalImport\Step;

/**
 * Test case for the Step utility.
 *
 * @package Cobweb\ExternalImport\Tests\Unit
 */
class StepUtilityTest extends UnitTestCase
{
    /**
     * @var StepUtility
     */
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = GeneralUtility::makeInstance(StepUtility::class);
    }

    /**
     * @return array
     */
    public function customStepsGoodConfigurationProvider(): array
    {
        return [
                'insert step before first step' => [
                        // Current steps
                        Importer::SYNCHRONYZE_DATA_STEPS,
                        // New step configuration
                        [
                                'class' => Step\HandleDataStep::class,
                                'position' => 'before:' . Step\CheckPermissionsStep::class
                        ],
                        // Resulting steps
                        [
                                Step\HandleDataStep::class,
                                Step\CheckPermissionsStep::class,
                                Step\ValidateConfigurationStep::class,
                                Step\ValidateConnectorStep::class,
                                Step\ReadDataStep::class,
                                Step\HandleDataStep::class,
                                Step\ValidateDataStep::class,
                                Step\TransformDataStep::class,
                                Step\StoreDataStep::class,
                                Step\ClearCacheStep::class,
                                Step\ConnectorCallbackStep::class
                        ]
                ],
                'insert step after transform data' => [
                        // Current steps
                        Importer::SYNCHRONYZE_DATA_STEPS,
                        // New step configuration
                        [
                                'class' => Step\HandleDataStep::class,
                                'position' => 'after:' . Step\TransformDataStep::class
                        ],
                        // Resulting steps
                        [
                                Step\CheckPermissionsStep::class,
                                Step\ValidateConfigurationStep::class,
                                Step\ValidateConnectorStep::class,
                                Step\ReadDataStep::class,
                                Step\HandleDataStep::class,
                                Step\ValidateDataStep::class,
                                Step\TransformDataStep::class,
                                Step\HandleDataStep::class,
                                Step\StoreDataStep::class,
                                Step\ClearCacheStep::class,
                                Step\ConnectorCallbackStep::class
                        ]
                ],
                'insert step before validate data step' => [
                        Importer::IMPORT_DATA_STEPS,
                        [
                                'class' => Step\HandleDataStep::class,
                                'position' => 'before:' . Step\ValidateDataStep::class
                        ],
                        [
                                Step\CheckPermissionsStep::class,
                                Step\ValidateConfigurationStep::class,
                                Step\HandleDataStep::class,
                                Step\HandleDataStep::class,
                                Step\ValidateDataStep::class,
                                Step\TransformDataStep::class,
                                Step\StoreDataStep::class,
                                Step\ClearCacheStep::class
                        ]
                ],
                'insert step after last step' => [
                        // Current steps
                        Importer::SYNCHRONYZE_DATA_STEPS,
                        // New step configuration
                        [
                                'class' => Step\HandleDataStep::class,
                                'position' => 'after:' . Step\ConnectorCallbackStep::class
                        ],
                        // Resulting steps
                        [
                                Step\CheckPermissionsStep::class,
                                Step\ValidateConfigurationStep::class,
                                Step\ValidateConnectorStep::class,
                                Step\ReadDataStep::class,
                                Step\HandleDataStep::class,
                                Step\ValidateDataStep::class,
                                Step\TransformDataStep::class,
                                Step\StoreDataStep::class,
                                Step\ClearCacheStep::class,
                                Step\ConnectorCallbackStep::class,
                                Step\HandleDataStep::class
                        ]
                ]
        ];
    }

    /**
     * @test
     * @dataProvider customStepsGoodConfigurationProvider
     * @param array $currentSteps
     * @param array $configuration
     * @param array $resultingSteps
     */
    public function insertStepInsertsCustomStepAtCorrectLocation($currentSteps, $configuration, $resultingSteps): void
    {
        self::assertSame(
                $resultingSteps,
                $this->subject->insertStep(
                        $currentSteps,
                        $configuration
                )
        );
    }

    /**
     * @return array
     */
    public function customStepsWrongConfigurationProvider(): array
    {
        return [
                'insert step with missing class information' => [
                    // Current steps
                    Importer::SYNCHRONYZE_DATA_STEPS,
                    // New step configuration
                    [
                            'position' => 'after:' . Step\TransformDataStep::class
                    ]
                ],
                'insert step with missing position information' => [
                    // Current steps
                    Importer::SYNCHRONYZE_DATA_STEPS,
                    // New step configuration
                    [
                            'class' => Step\HandleDataStep::class
                    ]
                ],
                'insert step with wrong syntax for position' => [
                    // Current steps
                    Importer::SYNCHRONYZE_DATA_STEPS,
                    // New step configuration
                    [
                            'class' => Step\HandleDataStep::class,
                            'position' => Step\TransformDataStep::class
                    ]
                ],
                'insert step with wrong keyword for position' => [
                    // Current steps
                    Importer::SYNCHRONYZE_DATA_STEPS,
                    // New step configuration
                    [
                            'class' => Step\HandleDataStep::class,
                            'position' => 'next:' . Step\TransformDataStep::class
                    ]
                ],
                'insert step with unknown class' => [
                    // Current steps
                    Importer::IMPORT_DATA_STEPS,
                    // New step configuration
                    [
                            'class' => 'Foo\\Bar\\Baz',
                            'position' => 'after:' . Step\TransformDataStep::class
                    ]
                ],
                'insert step after unregistered step' => [
                    // Current steps
                    Importer::IMPORT_DATA_STEPS,
                    // New step configuration
                    [
                            'class' => Step\HandleDataStep::class,
                            'position' => 'after:' . Step\ReadDataStep::class
                    ]
                ],
                'insert step after unknown step' => [
                    // Current steps
                    Importer::SYNCHRONYZE_DATA_STEPS,
                    // New step configuration
                    [
                            'class' => Step\HandleDataStep::class,
                            'position' => 'before:Not\\Known\\Step'
                    ]
                ],
                // TODO: to be complete the instantiation of an improper class should be tested, but this would be a functional test
        ];
    }

    /**
     * @test
     * @dataProvider customStepsWrongConfigurationProvider
     * @param array $currentSteps
     * @param array $configuration
     * @expectedException \Cobweb\ExternalImport\Exception\InvalidCustomStepConfiguration
     */
    public function validateCustomStepConfigurationWithWrongInformationThrowsException($currentSteps, $configuration): void
    {
        $this->subject->validateCustomStepConfiguration(
                $currentSteps,
                $configuration
        );
    }

    /**
     * @test
     * @dataProvider customStepsWrongConfigurationProvider
     * @param array $currentSteps
     * @param array $configuration
     */
    public function insertStepWithWrongInformationReturnsCurrentSteps($currentSteps, $configuration): void
    {
        self::assertSame(
                $currentSteps,
                $this->subject->insertStep(
                        $currentSteps,
                        $configuration
                )
        );
    }
}