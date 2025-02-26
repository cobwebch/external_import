<?php

declare(strict_types=1);

namespace Cobweb\ExternalImport\Tests\Functional;

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
use Cobweb\ExternalImport\Step\CheckPermissionsStep;
use Cobweb\ExternalImport\Step\ClearCacheStep;
use Cobweb\ExternalImport\Step\ConnectorCallbackStep;
use Cobweb\ExternalImport\Step\HandleDataStep;
use Cobweb\ExternalImport\Step\ReadDataStep;
use Cobweb\ExternalImport\Step\StoreDataStep;
use Cobweb\ExternalImport\Step\TransformDataStep;
use Cobweb\ExternalImport\Step\ValidateConfigurationStep;
use Cobweb\ExternalImport\Step\ValidateConnectorStep;
use Cobweb\ExternalImport\Step\ValidateDataStep;
use Cobweb\ExternalImport\Testing\FunctionalTestCaseWithDatabaseTools;
use Cobweb\ExternalImport\Transformation\ImageTransformation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test suite for the preview feature of the Importer class.
 */
class ImporterPreviewTest extends FunctionalTestCaseWithDatabaseTools
{
    protected array $coreExtensionsToLoad = [
        'scheduler',
    ];

    protected array $testExtensionsToLoad = [
        'cobweb/svconnector',
        'cobweb/svconnector_csv',
        'cobweb/svconnector_feed',
        'cobweb/svconnector_json',
        'cobweb/external_import',
        'cobweb/externalimport_test',
    ];

    protected Importer $subject;

    protected function setUp(): void
    {
        parent::setUp();
        try {
            $this->initializeBackendUser();
            Bootstrap::initializeLanguageObject();

            $this->subject = GeneralUtility::makeInstance(Importer::class);
            $this->subject->setTestMode(true);
            $this->importCSVDataSet(__DIR__ . '/Fixtures/StoragePage.csv');
            $this->subject->setForcedStoragePid(1);
        } catch (\Exception $e) {
            self::markTestSkipped(
                sprintf(
                    'Some initializations could not be performed (Exception: %s [%d])',
                    $e->getMessage(),
                    $e->getCode()
                )
            );
        }
    }

    #[Test]
    public function runPreviewWithWrongPreviewStepIssuesWarning(): void
    {
        $this->subject->setPreviewStep('foo');
        $messages = $this->subject->synchronize(
            'tx_externalimporttest_tag',
            0
        );
        self::assertCount(
            1,
            $messages[ContextualFeedbackSeverity::WARNING->value]
        );
    }

    #[Test]
    public function runPreviewOnCheckPermissionsStepReturnsNull(): void
    {
        $this->subject->setPreviewStep(CheckPermissionsStep::class);
        $messages = $this->subject->synchronize(
            'tx_externalimporttest_tag',
            0
        );
        self::assertNull(
            $this->subject->getPreviewData()
        );
    }

    #[Test]
    public function runPreviewOnValidateConfigurationStepReturnsNull(): void
    {
        $this->subject->setPreviewStep(ValidateConfigurationStep::class);
        $messages = $this->subject->synchronize(
            'tx_externalimporttest_tag',
            0
        );
        self::assertNull(
            $this->subject->getPreviewData()
        );
    }

    #[Test]
    public function runPreviewOnValidateConnectorStepReturnsNull(): void
    {
        $this->subject->setPreviewStep(ValidateConnectorStep::class);
        $messages = $this->subject->synchronize(
            'tx_externalimporttest_tag',
            0
        );
        self::assertNull(
            $this->subject->getPreviewData()
        );
    }

    public static function readPreviewProvider(): array
    {
        return [
            'xml-type data' => [
                'table' => 'tx_externalimporttest_product',
                'index' => 'base',
                'result' => 'EXT:externalimport_test/Resources/Private/ImportData/Test/Products.xml',
            ],
            'array-type data' => [
                'table' => 'tx_externalimporttest_tag',
                'index' => 0,
                'result' => [
                    [
                        'Code' => 'attack',
                        'Name' => 'Weapons ',
                    ],
                    [
                        'Code' => 'defense',
                        'Name' => 'Armor & Shields',
                    ],
                    [
                        'Code' => 'rude',
                        'Name' => 'F**k',
                    ],
                    [
                        'Code' => 'metal',
                        'Name' => 'Metallic objects',
                    ],
                    [
                        'Code' => 'fun',
                        'Name' => ' Fun, free time',
                    ],
                    [
                        'Code' => 'food',
                        'Name' => 'Food & Drinks',
                    ],
                ],
            ],
        ];
    }

    #[Test] #[DataProvider('readPreviewProvider')]
    public function runPreviewOnReadDataStepReturnsRawData(string $table, mixed $index, array|string $result): void
    {
        $this->subject->setPreviewStep(ReadDataStep::class);
        $messages = $this->subject->synchronize(
            $table,
            $index
        );
        // The result variable may be pointing to a file, in which case we want to read it
        if (is_string($result) && str_starts_with($result, 'EXT:')) {
            $result = file_get_contents(
                GeneralUtility::getFileAbsFileName($result)
            );
        }
        self::assertSame(
            $result,
            $this->subject->getPreviewData()
        );
    }

    public static function handlePreviewProvider(): array
    {
        return [
            'xml-type data' => [
                'table' => 'tx_externalimporttest_product',
                'index' => 'base',
                'result' => [
                    [
                        'attributes' => "\n\t\t\t<quality>Robust</quality>\n\t\t\t<quality>Stylish</quality>\n\t\t\t<quality>Reliable</quality>\n\t\t",
                        'categories' => 'USEFUL',
                        'created' => '2021-10-15 08:29:00',
                        'name' => 'Long sword',
                        'pictures' => 'https://loremflickr.com/320/240/scotland',
                        'sku' => '000001',
                        'tags' => 'attack,metal',
                        'picture_title' => 'View from the left',
                        'picture_order' => '2',
                    ],
                    [
                        'attributes' => "\n\t\t\t<quality>Robust</quality>\n\t\t\t<quality>Stylish</quality>\n\t\t\t<quality>Reliable</quality>\n\t\t",
                        'categories' => 'USEFUL',
                        'created' => '2021-10-15 08:29:00',
                        'name' => 'Long sword',
                        'pictures' => 'https://loremflickr.com/320/240/volcano',
                        'sku' => '000001',
                        'tags' => 'attack,metal',
                        'picture_title' => 'View from above',
                        'picture_order' => '1',
                    ],
                    [
                        'attributes' => "\n\t\t\t<quality>Robust</quality>\n\t\t\t<quality>Stylish</quality>\n\t\t\t<quality>Reliable</quality>\n\t\t",
                        'categories' => 'USEFUL',
                        'created' => '2021-10-15 08:29:00',
                        'name' => 'Long sword',
                        'pictures' => 'https://sdnfjwrthioweorg.gdsg/wtf',
                        'sku' => '000001',
                        'tags' => 'attack,metal',
                        'picture_title' => 'View that does not exist',
                    ],
                    [
                        'attributes' => '',
                        'categories' => 'USEFUL',
                        'created' => '2021-08-26 12:43:00',
                        'name' => 'Chain mail',
                        'pictures' => '',
                        'sku' => '000005',
                        'tags' => 'defense,metal',
                        'picture_title' => '',
                    ],
                ],
            ],
            'array-type data' => [
                'table' => 'tx_externalimporttest_tag',
                'index' => 0,
                'result' => [
                    [
                        'code' => 'attack',
                        'name' => 'Weapons ',
                    ],
                    [
                        'code' => 'defense',
                        'name' => 'Armor & Shields',
                    ],
                    [
                        'code' => 'rude',
                        'name' => 'F**k',
                    ],
                    [
                        'code' => 'metal',
                        'name' => 'Metallic objects',
                    ],
                    [
                        'code' => 'fun',
                        'name' => ' Fun, free time',
                    ],
                    [
                        'code' => 'food',
                        'name' => 'Food & Drinks',
                    ],
                ],
            ],
            'array-type data with sub-structure and array path' => [
                'table' => 'tx_externalimporttest_order',
                'index' => 0,
                'result' => [
                    [
                        'client_id' => 'Conan the Barbarian',
                        'order_date' => '2020-08-07 14:32',
                        'order_id' => '000001',
                        'products' => '000001',
                        'quantity' => 3,
                    ],
                    [
                        'client_id' => 'Conan the Barbarian',
                        'order_date' => '2020-08-07 14:32',
                        'order_id' => '000001',
                        'products' => '000005',
                        'quantity' => 1,
                    ],
                    [
                        'client_id' => 'Conan the Barbarian',
                        'order_date' => '2020-08-07 14:32',
                        'order_id' => '000001',
                        'products' => '000101',
                        'quantity' => 10,
                    ],
                    [
                        'client_id' => 'Conan the Barbarian',
                        'order_date' => '2020-08-07 14:32',
                        'order_id' => '000001',
                        'products' => '000102',
                        'quantity' => 2,
                    ],
                    [
                        'client_id' => 'Empty basket',
                        'order_date' => '2021-03-07 17:56',
                        'order_id' => '000003',
                        'products' => null,
                    ],
                    [
                        'client_id' => 'Sonja the Red',
                        'order_date' => '2020-08-08 06:48',
                        'order_id' => '000002',
                        'products' => '000001',
                        'quantity' => 1,
                    ],
                    [
                        'client_id' => 'Sonja the Red',
                        'order_date' => '2020-08-08 06:48',
                        'order_id' => '000002',
                        'products' => '000005',
                        'quantity' => 2,
                    ],
                    [
                        'client_id' => 'Sonja the Red',
                        'order_date' => '2020-08-08 06:48',
                        'order_id' => '000002',
                        'products' => '000202',
                        'quantity' => 1,
                    ],
                ],
            ],
        ];
    }

    #[Test] #[DataProvider('handlePreviewProvider')]
    public function runPreviewOnHandleDataStepReturnsHandledData(string $table, mixed $index, array $result): void
    {
        $this->subject->setPreviewStep(HandleDataStep::class);
        $messages = $this->subject->synchronize(
            $table,
            $index
        );
        self::assertSame(
            $result,
            $this->subject->getPreviewData()
        );
    }

    #[Test]
    public function runPreviewOnValidateDataStepReturnsNull(): void
    {
        $this->subject->setPreviewStep(ValidateDataStep::class);
        $messages = $this->subject->synchronize(
            'tx_externalimporttest_tag',
            0
        );
        self::assertNull(
            $this->subject->getPreviewData()
        );
    }

    public static function transformPreviewProvider(): array
    {
        return [
            'tags' => [
                'table' => 'tx_externalimporttest_tag',
                'index' => 0,
                'result' => [
                    [
                        'code' => 'attack',
                        'name' => 'Weapons',
                    ],
                    [
                        'code' => 'defense',
                        'name' => 'Armor & Shields',
                    ],
                    [
                        'code' => 'metal',
                        'name' => 'Metallic objects',
                    ],
                    [
                        'code' => 'fun',
                        'name' => 'Fun, free time',
                    ],
                    [
                        'code' => 'food',
                        'name' => 'Food & Drinks',
                    ],
                ],
            ],
            'base products' => [
                'table' => 'tx_externalimporttest_product',
                'index' => 'base',
                'result' => [
                    [
                        'attributes' => "PREVIEW: <ul>\n\t\t\t<li>Robust</li>\n\t\t\t<li>Stylish</li>\n\t\t\t<li>Reliable</li>\n\t\t</ul>",
                        'categories' => '',
                        'created' => 1634286540,
                        'name' => 'Long sword (base)',
                        'pictures' => ImageTransformation::$previewMessage,
                        'sku' => '000001',
                        'picture_title' => 'View from the left',
                        'picture_order' => '2',
                    ],
                    [
                        'attributes' => "PREVIEW: <ul>\n\t\t\t<li>Robust</li>\n\t\t\t<li>Stylish</li>\n\t\t\t<li>Reliable</li>\n\t\t</ul>",
                        'categories' => '',
                        'created' => 1634286540,
                        'name' => 'Long sword (base)',
                        'pictures' => ImageTransformation::$previewMessage,
                        'sku' => '000001',
                        'picture_title' => 'View from above',
                        'picture_order' => '1',
                    ],
                    [
                        'attributes' => "PREVIEW: <ul>\n\t\t\t<li>Robust</li>\n\t\t\t<li>Stylish</li>\n\t\t\t<li>Reliable</li>\n\t\t</ul>",
                        'categories' => '',
                        'created' => 1634286540,
                        'name' => 'Long sword (base)',
                        'pictures' => ImageTransformation::$previewMessage,
                        'sku' => '000001',
                        'picture_title' => 'View that does not exist',
                    ],
                    [
                        'attributes' => null,
                        'categories' => '',
                        'created' => 1629981780,
                        'name' => 'Chain mail (base)',
                        'pictures' => null,
                        'sku' => '000005',
                        'picture_title' => '',
                    ],
                ],
            ],
        ];
    }

    #[Test] #[DataProvider('transformPreviewProvider')]
    public function runPreviewOnTransformDataStepReturnsTransformedData(string $table, mixed $index, array $result): void
    {
        $this->subject->setPreviewStep(TransformDataStep::class);
        $messages = $this->subject->synchronize(
            $table,
            $index
        );
        self::assertSame(
            $result,
            $this->subject->getPreviewData()
        );
    }

    public static function storePreviewProvider(): array
    {
        return [
            'tags' => [
                'fixtures' => [
                    __DIR__ . '/Fixtures/StoreDataStepPreviewTest.csv',
                ],
                'prerequisites' => [],
                'table' => 'tx_externalimporttest_tag',
                'index' => 0,
                'testDatabase' => true,
                'recordsCount' => 1,
                'result' => [
                    'data' => [
                        'tx_externalimporttest_tag' => [
                            'NEW1' => [
                                'code' => 'attack',
                                'name' => 'Weapons',
                                'pid' => 1,
                            ],
                            'NEW2' => [
                                'code' => 'defense',
                                'name' => 'Armor & Shields',
                                'pid' => 1,
                            ],
                            'NEW3' => [
                                'code' => 'metal',
                                'name' => 'Metallic objects',
                                'pid' => 1,
                            ],
                            'NEW4' => [
                                'code' => 'fun',
                                'name' => 'Fun, free time',
                                'pid' => 1,
                            ],
                            'NEW5' => [
                                'code' => 'food',
                                'name' => 'Food & Drinks',
                                'pid' => 1,
                            ],
                        ],
                    ],
                    'commands-delete' => [
                        'tx_externalimporttest_tag' => [
                            2 => [
                                'delete' => 1,
                            ],
                        ],
                    ],
                    'commands-move' => [
                        'tx_externalimporttest_tag' => [],
                    ],
                ],
            ],
            'tags (only delete)' => [
                'fixtures' => [
                    __DIR__ . '/Fixtures/StoreDataStepPreviewTest.csv',
                ],
                'prerequisites' => [],
                'table' => 'tx_externalimporttest_tag',
                'index' => 'only-delete',
                'testDatabase' => true,
                'recordsCount' => 1,
                'result' => [
                    'data' => [
                        'tx_externalimporttest_tag' => [],
                    ],
                    'commands-delete' => [
                        'tx_externalimporttest_tag' => [
                            2 => [
                                'delete' => 1,
                            ],
                        ],
                    ],
                    'commands-move' => [
                        'tx_externalimporttest_tag' => [],
                    ],
                ],
            ],
            'base products (insert)' => [
                'fixtures' => [],
                'prerequisites' => [
                    [
                        'table' => 'tx_externalimporttest_tag',
                        'index' => 0,
                    ],
                ],
                'table' => 'tx_externalimporttest_product',
                'index' => 'base',
                'testDatabase' => true,
                'recordsCount' => 0,
                'result' => [
                    'data' => [
                        'tx_externalimporttest_product' => [
                            'NEW1' => [
                                'attributes' => "PREVIEW: <ul>\n\t\t\t<li>Robust</li>\n\t\t\t<li>Stylish</li>\n\t\t\t<li>Reliable</li>\n\t\t</ul>",
                                'categories' => '',
                                'created' => 1634286540,
                                'name' => 'Long sword (base)',
                                'pictures' => 'NEW2,NEW3,NEW4',
                                'sku' => '000001',
                                'tags' => '1,3',
                                'pid' => 1,
                            ],
                            'NEW5' => [
                                'attributes' => null,
                                'categories' => '',
                                'created' => 1629981780,
                                'name' => 'Chain mail (base)',
                                'pictures' => '',
                                'sku' => '000005',
                                'tags' => '2,3',
                                'pid' => 1,
                            ],
                        ],
                        'sys_file_reference' => [
                            'NEW2' => [
                                'uid_local' => ImageTransformation::$previewMessage,
                                'uid_foreign' => 'NEW1',
                                'title' => 'View from the left',
                                'tablenames' => 'tx_externalimporttest_product',
                                'fieldname' => 'pictures',
                                'pid' => 1,
                            ],
                            'NEW3' => [
                                'uid_local' => ImageTransformation::$previewMessage,
                                'uid_foreign' => 'NEW1',
                                'title' => 'View from above',
                                'tablenames' => 'tx_externalimporttest_product',
                                'fieldname' => 'pictures',
                                'pid' => 1,
                            ],
                            'NEW4' => [
                                'uid_local' => ImageTransformation::$previewMessage,
                                'uid_foreign' => 'NEW1',
                                'title' => 'View that does not exist',
                                'tablenames' => 'tx_externalimporttest_product',
                                'fieldname' => 'pictures',
                                'pid' => 1,
                            ],
                        ],
                    ],
                    'commands-delete' => [],
                    'commands-move' => [
                        'tx_externalimporttest_product' => [],
                    ],
                ],
            ],
            'base products (update)' => [
                'fixtures' => [],
                'prerequisites' => [
                    [
                        'table' => 'tx_externalimporttest_product',
                        'index' => 'base',
                    ],
                ],
                'table' => 'tx_externalimporttest_product',
                'index' => 'base',
                'testDatabase' => false,
                'recordsCount' => 0,
                'result' => [
                    'data' => [
                        'tx_externalimporttest_product' => [
                            '1' => [
                                'attributes' => "PREVIEW: <ul>\n\t\t\t<li>Robust</li>\n\t\t\t<li>Stylish</li>\n\t\t\t<li>Reliable</li>\n\t\t</ul>",
                                'categories' => '',
                                'created' => 1634286540,
                                'name' => 'Long sword (base)',
                                'pictures' => 'NEW1,NEW2,NEW3',
                                'sku' => '000001',
                            ],
                            '2' => [
                                'attributes' => null,
                                'categories' => '',
                                'created' => 1629981780,
                                'name' => 'Chain mail (base)',
                                'sku' => '000005',
                            ],
                        ],
                        // Normally this should not contain new records, as images have been imported during the prerequisite
                        // import. However, since we are in preview mode, the ImageTransformation class does not
                        // return proper uids for the sys_file (since it does not really save the images) and
                        // thus the sys_file_references cannot be matched
                        'sys_file_reference' => [
                            'NEW1' => [
                                'uid_local' => 'Preview mode. Image not handled, nor saved.',
                                'uid_foreign' => 1,
                                'title' => 'View from the left',
                                'tablenames' => 'tx_externalimporttest_product',
                                'fieldname' => 'pictures',
                                'pid' => 1,
                            ],
                            'NEW2' => [
                                'uid_local' => 'Preview mode. Image not handled, nor saved.',
                                'uid_foreign' => 1,
                                'title' => 'View from above',
                                'tablenames' => 'tx_externalimporttest_product',
                                'fieldname' => 'pictures',
                                'pid' => 1,
                            ],
                            'NEW3' => [
                                'uid_local' => ImageTransformation::$previewMessage,
                                'uid_foreign' => 1,
                                'title' => 'View that does not exist',
                                'tablenames' => 'tx_externalimporttest_product',
                                'fieldname' => 'pictures',
                                'pid' => 1,
                            ],
                        ],
                    ],
                    'commands-delete' => [
                        'sys_file_reference' => [],
                    ],
                    'commands-move' => [
                        'tx_externalimporttest_product' => [],
                    ],
                ],
            ],
            'update products with move' => [
                'fixtures' => [
                    __DIR__ . '/Fixtures/ExtraStoragePage.csv',
                ],
                'prerequisites' => [
                    [
                        'table' => 'tx_externalimporttest_product',
                        'index' => 'base',
                    ],
                ],
                'table' => 'tx_externalimporttest_product',
                'index' => 'updated_products',
                'testDatabase' => false,
                'recordsCount' => 0,
                'result' => [
                    'data' => [
                        'tx_externalimporttest_product' => [
                            '1' => [
                                'name' => 'Long sword (updated)',
                                'pid' => '2',
                                'sku' => '000001',
                            ],
                        ],
                    ],
                    'commands-delete' => [],
                    'commands-move' => [
                        'tx_externalimporttest_product' => [
                            '1' => [
                                'move' => 2,
                            ],
                        ],
                    ],
                ],
            ],
            'bundles' => [
                'fixtures' => [],
                'prerequisites' => [
                    [
                        'table' => 'tx_externalimporttest_product',
                        'index' => 'base',
                    ],
                    [
                        'table' => 'tx_externalimporttest_product',
                        'index' => 'more',
                    ],
                    [
                        'table' => 'tx_externalimporttest_product',
                        'index' => 'stable',
                    ],
                ],
                'table' => 'tx_externalimporttest_bundle',
                'index' => 0,
                'testDatabase' => true,
                'recordsCount' => 0,
                'result' => [
                    'data' => [
                        'tx_externalimporttest_bundle' => [
                            'NEW1' => [
                                'bundle_code' => 'JOY01',
                                'maker' => 'Doctor Strange',
                                'name' => 'Bundle of joy',
                                'notes' => 'This will make you very happy',
                                'products' => '3,4',
                                'pid' => 1,
                            ],
                            'NEW2' => [
                                'bundle_code' => 'PAIN01',
                                'maker' => 'Loki',
                                'name' => 'Bundle of pain',
                                'notes' => 'Maybe you don\'t want this bundle at all',
                                'products' => '1,2',
                                'pid' => 1,
                            ],
                            'NEW3' => [
                                'bundle_code' => 'PAIN02',
                                'maker' => 'Mad Max',
                                'name' => 'Bundle of extra pain',
                                'notes' => null,
                                'products' => '6,1,5,2',
                                'pid' => 1,
                            ],
                        ],
                    ],
                    'commands-delete' => [],
                    'commands-move' => [
                        'tx_externalimporttest_bundle' => [],
                    ],
                ],
            ],
            'orders' => [
                'fixtures' => [
                    __DIR__ . '/Fixtures/Orders.csv',
                ],
                'prerequisites' => [
                    [
                        'table' => 'tx_externalimporttest_product',
                        'index' => 'base',
                    ],
                    [
                        'table' => 'tx_externalimporttest_product',
                        'index' => 'more',
                    ],
                    [
                        'table' => 'tx_externalimporttest_product',
                        'index' => 'stable',
                    ],
                ],
                'table' => 'tx_externalimporttest_order',
                'index' => 0,
                'testDatabase' => true,
                'recordsCount' => 2,
                'result' => [
                    'data' => [
                        'tx_externalimporttest_order' => [
                            1 => [
                                'client_id' => 'Conan the Barbarian',
                                'order_date' => 1596810720,
                                'order_id' => '000001',
                                'products' => '1,NEW2,NEW3,NEW4',
                            ],
                            'NEW5' => [
                                'client_id' => 'Empty basket',
                                'order_date' => 1615139760,
                                'order_id' => '000003',
                                'products' => '',
                                'pid' => 1,
                            ],
                            'NEW6' => [
                                'client_id' => 'Sonja the Red',
                                'order_date' => 1596869280,
                                'order_id' => '000002',
                                'products' => 'NEW7,NEW8,NEW9',
                                'pid' => 1,
                            ],
                        ],
                        'tx_externalimporttest_order_items' => [
                            1 => [
                                'uid_local' => 1,
                                'uid_foreign' => '1',
                                'quantity' => 3,
                                'pid' => 1,
                            ],
                            'NEW2' => [
                                'uid_local' => 1,
                                'uid_foreign' => '2',
                                'quantity' => 1,
                                'pid' => 1,
                            ],
                            'NEW3' => [
                                'uid_local' => 1,
                                'uid_foreign' => '3',
                                'quantity' => 10,
                                'pid' => 1,
                            ],
                            'NEW4' => [
                                'uid_local' => 1,
                                'uid_foreign' => '4',
                                'quantity' => 2,
                                'pid' => 1,
                            ],
                            'NEW7' => [
                                'uid_local' => 'NEW6',
                                'uid_foreign' => '1',
                                'quantity' => 1,
                                'pid' => 1,
                            ],
                            'NEW8' => [
                                'uid_local' => 'NEW6',
                                'uid_foreign' => '2',
                                'quantity' => 2,
                                'pid' => 1,
                            ],
                            'NEW9' => [
                                'uid_local' => 'NEW6',
                                'uid_foreign' => '6',
                                'quantity' => 1,
                                'pid' => 1,
                            ],
                        ],
                    ],
                    'commands-delete' => [
                        'tx_externalimporttest_order_items' => [
                            2 => [
                                'delete' => 1,
                            ],
                        ],
                        'tx_externalimporttest_order' => [
                            12 => [
                                'delete' => 1,
                            ],
                        ],
                    ],
                    'commands-move' => [
                        'tx_externalimporttest_order' => [],
                    ],
                ],
            ],
        ];
    }

    #[Test] #[DataProvider('storePreviewProvider')]
    public function runPreviewOnStoreDataStepReturnsStorageDataAndWritesNothingToDatabase(array $fixtures, array $prerequisites, string $table, mixed $index, bool $testDatabase, int $recordsCount, array $result): void
    {
        // Load designated fixture files
        if (count($fixtures) > 0) {
            foreach ($fixtures as $fixture) {
                try {
                    $this->importCSVDataSet($fixture);
                } catch (\Exception $e) {
                    self::markTestSkipped(
                        sprintf(
                            'Could not load fixture file %s (error: %s [%d)',
                            $fixture,
                            $e->getMessage(),
                            $e->getCode()
                        )
                    );
                }
            }
        }
        // Run necessary synchronizations
        if (count($prerequisites) > 0) {
            foreach ($prerequisites as $prerequisite) {
                $messages = $this->subject->synchronize(
                    $prerequisite['table'],
                    $prerequisite['index']
                );
            }
        }
        // Run the actual test
        $this->subject->setPreviewStep(StoreDataStep::class);
        $this->subject->setTestMode(true);
        $this->subject->getTemporaryKeyRepository()->resetForcedTemporaryKeySerial();
        $messages = $this->subject->synchronize(
            $table,
            $index
        );
        // Test the preview data
        self::assertSame(
            $result,
            $this->subject->getPreviewData()
        );
        // Test that nothing was written to the database, i.e. the record count is unchanged
        // (if appropriate, not for update scenarios)
        if ($testDatabase) {
            $countItems = $this->selectCount(
                'uid',
                $table
            );
            self::assertEquals($recordsCount, $countItems);
        }
    }

    public static function clearCachePreviewProvider(): array
    {
        return [
            'tags' => [
                'table' => 'tx_externalimporttest_tag',
                'index' => 0,
                'result' => [
                    'caches' => [],
                ],
            ],
            'product pages' => [
                'table' => 'pages',
                'index' => 'product_pages',
                'result' => [
                    'caches' => [
                        'pages',
                    ],
                ],
            ],
        ];
    }

    #[Test] #[DataProvider('clearCachePreviewProvider')]
    public function runPreviewOnClearCacheStepReturnsCacheListAndClearsNothing(string $table, mixed $index, array $result): void
    {
        $this->subject->setPreviewStep(ClearCacheStep::class);
        $this->subject->setTestMode(true);
        $messages = $this->subject->synchronize(
            $table,
            $index
        );
        self::assertSame(
            $result,
            $this->subject->getPreviewData()
        );
    }

    #[Test]
    public function runPreviewOnConnectorCallbackStepReturnsNull(): void
    {
        $this->subject->setPreviewStep(ConnectorCallbackStep::class);
        $messages = $this->subject->synchronize(
            'tx_externalimporttest_tag',
            0
        );
        self::assertNull(
            $this->subject->getPreviewData()
        );
    }

    // TODO: add ReportStep test (maybe)
}
