<?php

declare(strict_types=1);

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
use Cobweb\ExternalImport\Step;
use Cobweb\ExternalImport\Utility\StepUtility;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case for the Step utility.
 */
class StepUtilityTest extends UnitTestCase
{
    protected StepUtility $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = GeneralUtility::makeInstance(StepUtility::class);
    }

    public static function customStepsGoodConfigurationProvider(): array
    {
        return [
            'insert step before first step' => [
                'currentSteps' => Importer::SYNCHRONYZE_DATA_STEPS,
                'configuration' => [
                    'class' => Step\HandleDataStep::class,
                    'position' => 'before:' . Step\CheckPermissionsStep::class,
                ],
                'resultingSteps' => [
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
                    Step\ConnectorCallbackStep::class,
                    Step\ReportStep::class,
                ],
            ],
            'insert step after transform data' => [
                'currentSteps' => Importer::SYNCHRONYZE_DATA_STEPS,
                'configuration' => [
                    'class' => Step\HandleDataStep::class,
                    'position' => 'after:' . Step\TransformDataStep::class,
                ],
                'resultingSteps' => [
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
                    Step\ConnectorCallbackStep::class,
                    Step\ReportStep::class,
                ],
            ],
            'insert step before validate data step' => [
                'currentSteps' => Importer::IMPORT_DATA_STEPS,
                'configuration' => [
                    'class' => Step\HandleDataStep::class,
                    'position' => 'before:' . Step\ValidateDataStep::class,
                ],
                'resultingSteps' => [
                    Step\CheckPermissionsStep::class,
                    Step\ValidateConfigurationStep::class,
                    Step\HandleDataStep::class,
                    Step\HandleDataStep::class,
                    Step\ValidateDataStep::class,
                    Step\TransformDataStep::class,
                    Step\StoreDataStep::class,
                    Step\ClearCacheStep::class,
                    Step\ReportStep::class,
                ],
            ],
            'insert step after last step' => [
                'currentSteps' => Importer::SYNCHRONYZE_DATA_STEPS,
                'configuration' => [
                    'class' => Step\HandleDataStep::class,
                    'position' => 'after:' . Step\ReportStep::class,
                ],
                'resultingSteps' => [
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
                    Step\ReportStep::class,
                    Step\HandleDataStep::class,
                ],
            ],
        ];
    }

    #[Test] #[DataProvider('customStepsGoodConfigurationProvider')]
    public function insertStepInsertsCustomStepAtCorrectLocation(
        array $currentSteps,
        array $configuration,
        array $resultingSteps
    ): void {
        self::assertSame(
            $resultingSteps,
            $this->subject->insertStep(
                $currentSteps,
                $configuration
            )
        );
    }

    public static function customStepsWrongConfigurationProvider(): array
    {
        return [
            'insert step with missing class information' => [
                'currentSteps' => Importer::SYNCHRONYZE_DATA_STEPS,
                'configuration' => [
                    'position' => 'after:' . Step\TransformDataStep::class,
                ],
            ],
            'insert step with missing position information' => [
                'currentSteps' => Importer::SYNCHRONYZE_DATA_STEPS,
                'configuration' => [
                    'class' => Step\HandleDataStep::class,
                ],
            ],
            'insert step with wrong syntax for position' => [
                'currentSteps' => Importer::SYNCHRONYZE_DATA_STEPS,
                'configuration' => [
                    'class' => Step\HandleDataStep::class,
                    'position' => Step\TransformDataStep::class,
                ],
            ],
            'insert step with wrong keyword for position' => [
                'currentSteps' => Importer::SYNCHRONYZE_DATA_STEPS,
                'configuration' => [
                    'class' => Step\HandleDataStep::class,
                    'position' => 'next:' . Step\TransformDataStep::class,
                ],
            ],
            'insert step with unknown class' => [
                'currentSteps' => Importer::IMPORT_DATA_STEPS,
                'configuration' => [
                    'class' => 'Foo\\Bar\\Baz',
                    'position' => 'after:' . Step\TransformDataStep::class,
                ],
            ],
            'insert step after unregistered step' => [
                'currentSteps' => Importer::IMPORT_DATA_STEPS,
                'configuration' => [
                    'class' => Step\HandleDataStep::class,
                    'position' => 'after:' . Step\ReadDataStep::class,
                ],
            ],
            'insert step after unknown step' => [
                'currentSteps' => Importer::SYNCHRONYZE_DATA_STEPS,
                'configuration' => [
                    'class' => Step\HandleDataStep::class,
                    'position' => 'before:Not\\Known\\Step',
                ],
            ],
            // TODO: to be complete the instantiation of an improper class should be tested, but this would be a functional test
        ];
    }

    #[Test] #[DataProvider('customStepsWrongConfigurationProvider')]
    public function validateCustomStepConfigurationWithWrongInformationThrowsException(
        array $currentSteps,
        array $configuration
    ): void {
        $this->expectException(\Cobweb\ExternalImport\Exception\InvalidCustomStepConfiguration::class);
        $this->subject->validateCustomStepConfiguration(
            $currentSteps,
            $configuration
        );
    }

    #[Test] #[DataProvider('customStepsWrongConfigurationProvider')]
    public function insertStepWithWrongInformationReturnsCurrentSteps(array $currentSteps, array $configuration): void
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
