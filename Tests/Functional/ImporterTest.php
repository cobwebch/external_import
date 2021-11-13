<?php

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
use Cobweb\ExternalImport\Step\StoreDataStep;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use \TYPO3\CMS\Core\Localization\LanguageService;

/**
 * Testcase for the External Import importer
 *
 * @author Francois Suter <typo3@cobweb.ch>
 * @package TYPO3
 * @subpackage tx_externalimport
 */
class ImporterTest extends FunctionalTestCase
{
    protected $testExtensionsToLoad = [
            'typo3conf/ext/svconnector',
            'typo3conf/ext/svconnector_csv',
            'typo3conf/ext/svconnector_feed',
            'typo3conf/ext/svconnector_json',
            'typo3conf/ext/external_import',
            'typo3conf/ext/externalimport_test'
    ];

    /**
     * @var Importer
     */
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();
        try {
            $this->setUpBackendUserFromFixture(1);
            // Connector services need a global LanguageService object
            $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageService::class);

            $this->subject = GeneralUtility::makeInstance(Importer::class);
            $this->importDataSet(__DIR__ . '/Fixtures/StoragePage.xml');
            $this->subject->setForcedStoragePid(1);
        }
        catch (\Exception $e) {
            self::markTestSkipped(
                    sprintf(
                            'Some initializations could not be performed (Exception: %s [%d])',
                            $e->getMessage(),
                            $e->getCode()
                    )
            );
        }
    }

    /**
     * Imports the "tag" elements and checks whether we have the right count or not
     * (5 expected on a total of 6, because one is filtered away by
     * \Cobweb\ExternalimportTest\Service\TagsPreprocessor::preprocessRawRecordset())
     *
     * @test
     */
    public function importTagsWithImporterStoresFiveRecords(): void
    {
        $messages = $this->subject->synchronize(
                'tx_externalimporttest_tag',
                0
        );
        $countRecords = $this->getDatabaseConnection()->selectCount(
                'uid',
                'tx_externalimporttest_tag'
        );
        // NOTE: the serializing of the Importer messages is a quick way to debug anything gone wrong
        self::assertEquals(
                5,
                $countRecords,
                serialize($messages)
        );
    }

    /**
     * Imports the "sys_category" elements and checks whether we have the right count or not
     * (3 expected)
     *
     * @test
     */
    public function importCategoriesWithImporterStoresFourRecordsWithOneParentRelation(): void
    {
        $messages = $this->subject->synchronize(
                'sys_category',
                'product_categories'
        );
        // Count imported categories
        $countRecords = $this->getDatabaseConnection()->selectCount(
                'uid',
                'sys_category'
        );
        // Count records having a parent
        $countChildren = $this->getDatabaseConnection()->selectCount(
                'uid',
                'sys_category',
                'parent > 0'
        );

        // NOTE: the serializing of the Importer messages is a quick way to debug anything gone wrong
        self::assertEquals(4, $countRecords, serialize($messages));
        self::assertEquals(1, $countChildren);
    }

    /**
     * Imports the "designers" and checks whether we have the right count or not (3 expected),
     * including relations to products.
     *
     * @test
     */
    public function importDesignersWithImporterStoresThreeRecordsAndCreatesRelations(): void
    {
        $this->subject->synchronize(
                'tx_externalimporttest_product',
                'base'
        );
        $messages = $this->subject->synchronize(
                'tx_externalimporttest_designer',
                0
        );
        // Get the number of designers stored
        $countDesigners = $this->getDatabaseConnection()->selectCount(
                'uid',
                'tx_externalimporttest_designer'
        );
        // Count products relations
        $countRelations = $this->getDatabaseConnection()->selectCount(
                '*',
                'tx_externalimporttest_product_designer_mm'
        );
        // NOTE: the serializing of the Importer messages is a quick way to debug anything gone wrong
        self::assertEquals(3, $countDesigners, serialize($messages));
        self::assertEquals(3, $countRelations);
    }

    /**
     * Imports the "products" with the "base" configuration and checks whether we have the right count or not
     * (2 expected). Furthermore, relations with categories and tags are tested.
     *
     * @test
     */
    public function importBaseProductsWithImporterStoresTwoRecordsAndCreatesRelations(): void
    {
        $resourceFactory = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\ResourceFactory::class);
        $storage = $resourceFactory->getDefaultStorage();
        if (!$storage->hasFolder('imported_images')) {
            $storage->createFolder('imported_images');
        }
        // Import tags and categories first, so that relations can be created to them from products
        $this->subject->synchronize(
                'tx_externalimporttest_tag',
                0
        );
        $this->subject->synchronize(
                'sys_category',
                'product_categories'
        );
        $messages = $this->subject->synchronize(
                'tx_externalimporttest_product',
                'base'
        );
        // Get the number of products stored and their tag relations
        /** @var \Doctrine\DBAL\Driver\Statement $databaseResult */
        $databaseResult = $this->getDatabaseConnection()->getDatabaseInstance()
                ->select('uid', 'tags')
                ->from('tx_externalimporttest_product')
                // Ensure consistent order for safe comparison
                ->orderBy('uid', 'ASC')
                ->execute();
        $countProducts = 0;
        $tagRelations = [];
        while ($row = $databaseResult->fetch()) {
            $countProducts++;
            $tagRelations[$row['uid']] = $row['tags'];
        }
        // Get the number of categories relations created
        $countRelations = $this->getDatabaseConnection()->selectCount(
                'uid_local',
                'sys_category_record_mm',
                'tablenames = \'tx_externalimporttest_product\''
        );
        // Get the number of sys_file_reference records created
        $countFiles = $this->getDatabaseConnection()->selectCount(
                'uid_local',
                'sys_file_reference',
                'tablenames = \'tx_externalimporttest_product\''
        );
        // NOTE: the serializing of the Importer messages is a quick way to debug anything gone wrong
        self::assertEquals(2, $countProducts, serialize($messages));
        self::assertEquals(2, $countRelations);
        self::assertEquals(2, $countFiles);
        self::assertSame(
                [
                        1 => '1,3',
                        2 => '2,3'
                ],
                $tagRelations
        );
    }

    /**
     * Imports the "products" with the "more" configuration and checks whether we have the right count or not
     * (2 expected)
     *
     * @test
     */
    public function importMoreProductsWithImporterStoresTwoRecords(): void
    {
        $messages = $this->subject->synchronize(
                'tx_externalimporttest_product',
                'more'
        );
        // Get the number of products stored
        $countProducts = $this->getDatabaseConnection()->selectCount(
                'uid',
                'tx_externalimporttest_product'
        );
        // NOTE: the serializing of the Importer messages is a quick way to debug anything gone wrong
        self::assertEquals(2, $countProducts, serialize($messages));
    }

    /**
     * Imports the "products" with the "more" configuration and checks whether we have the right count or not
     * (2 expected).
     *
     * This test also checks that old MM relations are removed.
     *
     * @test
     */
    public function importStableProductsWithImporterStoresTwoRecordsAndRemovesOldRelations(): void
    {
        try {
            // Create 1 category and 1 relation to it. The relation should be removed by the import process.
            $this->importDataSet(__DIR__ . '/Fixtures/CategoriesMM.xml');

            $messages = $this->subject->synchronize(
                    'tx_externalimporttest_product',
                    'stable'
            );
            // Get the number of products stored
            $countProducts = $this->getDatabaseConnection()->selectCount(
                    'uid',
                    'tx_externalimporttest_product'
            );
            $products = $this->getDatabaseConnection()->getDatabaseInstance()
                    ->select('uid', 'name', 'categories')
                    ->from('tx_externalimporttest_product')
                    ->execute()
                    ->fetchAll();
            // Get the categories relations
            $relations = $this->getDatabaseConnection()->getDatabaseInstance()
                    ->select('uid_local', 'uid_foreign')
                    ->from('sys_category_record_mm')
                    ->where('tablenames = \'tx_externalimporttest_product\'')
                    ->execute()
                    ->fetchAll();
            $countRelations = count($relations);
            $firstRelation = array_pop($relations);
            // NOTE: the serializing of the Importer messages is a quick way to debug anything gone wrong
            self::assertEquals(
                    2,
                    $countProducts,
                    serialize($messages)
            );
            // There should have been no updates (operation is disabled)
            self::assertEquals(
                    0,
                    $this->subject->getReportingUtility()->getValueForStep(
                            StoreDataStep::class,
                            'updates'
                    )
            );
            // There should be only 1 relation, because the one from the fixture is expected to have been deleted
            self::assertEquals(
                    1,
                    $countRelations
            );
            // The remaining relation should be with the second product (uid: 2)
            self::assertEquals(
                    2,
                    $firstRelation['uid_foreign']
            );
        }
        catch (\Exception $e) {
            self::markTestSkipped(
                    sprintf(
                            'Categories relations fixture could not be loaded (Exception: %s [%d])',
                            $e->getMessage(),
                            $e->getCode()
                    )
            );
        }
    }

    /**
     * Imports the "products" with the "products for stores" configuration and checks whether we have
     * the right number of relations or not (6 expected). Also checks the "stock" which used the
     * "additionalFields" mechanism for MM relations.
     *
     * @test
     */
    public function importProductsForStoresWithImporterCreatesSixRelations(): void
    {
        // First import products and stores, so that relations can be created
        $this->subject->synchronize(
                'tx_externalimporttest_product',
                'base'
        );
        $this->subject->synchronize(
                'tx_externalimporttest_product',
                'stable'
        );
        $this->subject->synchronize(
                'tx_externalimporttest_store',
                0
        );

        $messages = $this->subject->synchronize(
                'tx_externalimporttest_product',
                'products_for_stores'
        );
        // Get the number of relations created
        $databaseResult = $this->getDatabaseConnection()->getDatabaseInstance()
                ->select('stock')
                ->from('tx_externalimporttest_store_product')
                // Ensure consistent order for safe comparison
                ->orderBy('stock', 'ASC')
                ->execute();
        $stocks = [];
        while ($row = $databaseResult->fetchAssociative()) {
            $stocks[] = $row['stock'];
        }
        // NOTE: the serializing of the Importer messages is a quick way to debug anything gone wrong
        self::assertCount(
                6,
                $stocks,
                serialize($messages)
        );
        self::assertSame(
                [5, 6, 8, 10, 12, 20],
                $stocks
        );
    }

    /**
     * Imports the "bundles" and checks whether we have the right count or not
     * (2 expected). Also checks relations between products and bundles,
     * including the order of the products inside the bundles.
     *
     * @test
     */
    public function importBundlesWithImporterStoresThreeRecordsAndCreatesOrderedRelations(): void
    {
        // First import all products, so that relations can be created
        $this->subject->synchronize(
                'tx_externalimporttest_product',
                'base'
        );
        $this->subject->synchronize(
                'tx_externalimporttest_product',
                'more'
        );
        $this->subject->synchronize(
                'tx_externalimporttest_product',
                'stable'
        );

        $messages = $this->subject->synchronize(
                'tx_externalimporttest_bundle',
                0
        );
        // Get the number of bundles stored
        $countBundles = $this->getDatabaseConnection()->selectCount(
                'uid',
                'tx_externalimporttest_bundle'
        );
        // Get the number of relations created
        /** @var \Doctrine\DBAL\Driver\Statement $databaseResult */
        $databaseResult = $this->getDatabaseConnection()->getDatabaseInstance()
                ->select('uid_local', 'uid_foreign', 'sorting')
                ->from('tx_externalimporttest_bundle_product_mm')
                // Ensure consistent order for safe comparison
                ->orderBy('uid_local', 'ASC')
                ->addOrderBy('sorting', 'ASC')
                ->execute();
        $countRelations = 0;
        $sortedProducts = [];
        while ($row = $databaseResult->fetch()) {
            $countRelations++;
            $sortedProducts[] = $row['uid_foreign'];
        }
        // NOTE: the serializing of the Importer messages is a quick way to debug anything gone wrong
        self::assertEquals(3, $countBundles, serialize($messages));
        self::assertEquals(8, $countRelations);
        self::assertSame(
                [3, 4, 1, 2, 6, 1, 5, 2],
                $sortedProducts
        );
    }

    /**
     * Imports the "orders" and checks whether we have the right count or not
     * (2 expected = 2 imported and 1 existing deleted). Also checks relations between products and orders,
     * including the "quantity" additional field.
     *
     * @test
     */
    public function importOrdersWithImporterStoresThreeRecordsAndCreatesRelationsAndDeletesExistingOrder(): void
    {
        try {
            $this->importDataSet(__DIR__ . '/Fixtures/Orders.xml');
        } catch (\Exception $e) {
            self::markTestSkipped(
                    sprintf(
                            'Orders fixture could not be loaded (Exception: %s [%d])',
                            $e->getMessage(),
                            $e->getCode()
                    )
            );
        }
        // First import all products, so that relations can be created
        $this->subject->synchronize(
                'tx_externalimporttest_product',
                'base'
        );
        $this->subject->synchronize(
                'tx_externalimporttest_product',
                'more'
        );
        $this->subject->synchronize(
                'tx_externalimporttest_product',
                'stable'
        );

        $messages = $this->subject->synchronize(
                'tx_externalimporttest_order',
                0
        );
        // Get the number of orders stored
        $countOrders = $this->getDatabaseConnection()->selectCount(
                'uid',
                'tx_externalimporttest_order'
        );
        // Get the number of relations created
        /** @var \Doctrine\DBAL\Driver\Statement $databaseResult */
        $databaseResult = $this->getDatabaseConnection()->getDatabaseInstance()
                ->select('uid_local', 'quantity')
                ->from('tx_externalimporttest_order_items')
                // Ensure consistent order for safe comparison
                ->orderBy('uid_local', 'ASC')
                ->execute();
        $countRelations = 0;
        $quantities = [];
        while ($row = $databaseResult->fetch()) {
            $countRelations++;
            $quantities[] = $row['quantity'];
        }
        // NOTE: the serializing of the Importer messages is a quick way to debug anything gone wrong
        self::assertEquals(3, $countOrders, serialize($messages));
        self::assertEquals(7, $countRelations);
        self::assertSame(
                [3, 1, 10, 2, 1, 2, 1],
                $quantities
        );
    }

    /**
     * Imports the "stores" and checks whether we have the right count or not
     * (2 expected). Also checks relations between products and stores,
     * including the "stock" additional field.
     *
     * @test
     */
    public function importStoresWithImporterStoresTwoRecordsAndCreatesRelations(): void
    {
        // First import products, so that relations can be created
        $this->subject->synchronize(
                'tx_externalimporttest_product',
                'more'
        );

        $messages = $this->subject->synchronize(
                'tx_externalimporttest_store',
                0
        );
        // Get the number of products stored
        $countStores = $this->getDatabaseConnection()->selectCount(
                'uid',
                'tx_externalimporttest_store'
        );
        // Get the number of relations created
        $databaseResult = $this->getDatabaseConnection()->getDatabaseInstance()
                ->select('stock')
                ->from('tx_externalimporttest_store_product')
                // Ensure consistent order for safe comparison
                ->orderBy('stock', 'ASC')
                ->execute();
        $stocks = [];
        while ($row = $databaseResult->fetchAssociative()) {
            $stocks[] = $row['stock'];
        }
        // NOTE: the serializing of the Importer messages is a quick way to debug anything gone wrong
        self::assertEquals(
                2,
                $countStores,
                serialize($messages)
        );
        self::assertCount(
                3,
                $stocks
        );
        self::assertSame(
                [5, 6, 10],
                $stocks
        );
    }

    /**
     * Imports the "invoices" elements and checks whether we have the right count or not
     * (3 expected).
     *
     * @test
     */
    public function importInvoicesWithImporterStoresThreeRecords(): void
    {
        $messages = $this->subject->synchronize(
                'tx_externalimporttest_invoice',
                0
        );
        $countRecords = $this->getDatabaseConnection()->selectCount(
                'uid',
                'tx_externalimporttest_invoice'
        );
        // NOTE: the serializing of the Importer messages is a quick way to debug anything gone wrong
        self::assertEquals(3, $countRecords, serialize($messages));
    }

    /**
     * Imports the products as pages and checks whether the proper page tree has been created.
     *
     * @test
     */
    public function importProductsAsPagesWithImporterCreatesProperPageTree(): void
    {
        $messages = $this->subject->synchronize(
                'pages',
                'product_pages'
        );
        // Three new pages should be attached to the storage page
        $parentPages = $this->getDatabaseConnection()->getDatabaseInstance()
                ->select('uid', 'title')
                ->from('pages')
                ->where('pid = 1')
                ->execute()
                ->fetchAll();
        $countParentPages = $this->getDatabaseConnection()->selectCount(
                'uid',
                'pages',
                'pid = 1'
        );
        // NOTE: the serializing of the Importer messages is a quick way to debug anything gone wrong
        self::assertEquals(3, $countParentPages, serialize($messages));

        // Next, the page called "Product 1" should have 2 child pages, "Product 2" none and "Product 3" 1 child page
        $pageTree = [
                [
                        'title' => 'Product 1',
                        'children' => 2
                ],
                [
                        'title' => 'Product 2',
                        'children' => 0
                ],
                [
                        'title' => 'Product 3',
                        'children' => 1
                ]
        ];
        foreach ($pageTree as $page) {
            $children = $this->getDatabaseConnection()->selectCount(
                    'uid',
                    'pages',
                    'pid IN (SELECT uid FROM pages WHERE title = \'' . $page['title'] . '\')'
            );
            self::assertEquals($page['children'], $children);
        }
    }

    /**
     * Imports a product to a different page, thus moving the product.
     *
     * @test
     */
    public function importUpdatedProductsWithImporterMovesProductsAndUpdatesSlugs(): void
    {
        try {
            $this->importDataSet(__DIR__ . '/Fixtures/ExtraStoragePage.xml');
        } catch (\Exception $e) {
            self::markTestSkipped(
                    sprintf(
                            'Extra storage page fixture could not be loaded (Exception: %s [%d])',
                            $e->getMessage(),
                            $e->getCode()
                    )
            );
        }
        // First import base products
        $this->subject->synchronize(
                'tx_externalimporttest_product',
                'base'
        );
        // Import "updated" products, which is supposed to move one product to a different page
        $messages = $this->subject->synchronize(
                'tx_externalimporttest_product',
                'updated_products'
        );
        $movedProducts = $this->getDatabaseConnection()->select(
                'name',
                'tx_externalimporttest_product',
                'pid = 2'
        )->fetchAll();
        // A single product should have been moved
        self::assertCount(
                1,
                $movedProducts,
                serialize($messages)
        );
        // That product should have an updated slug
        self::assertSame(
                'Long sword (updated)',
                $movedProducts[0]['name']
        );
    }

    /**
     * Provides a list of erroneous import configurations.
     *
     * @return array
     */
    public function wrongConfigurationNames(): array
    {
        return [
                'Wrong general configuration' => [
                        'tx_externalimporttest_product',
                        'general_configuration_errors'
                ],
                'Wrong column configuration' => [
                        'sys_categories',
                        'column_configuration_errors'
                ]
        ];
    }

    /**
     * Checks that running an erroneous configuration exists early with a single error message.
     *
     * @param string $table Name of the table to import into
     * @param string $configuration Name of the configuration to check
     * @test
     * @dataProvider wrongConfigurationNames
     */
    public function importWithErroneousConfigurationReturnsError($table, $configuration): void
    {
        $messages = $this->subject->synchronize(
                $table,
                $configuration
        );
        $messageLevel = key($messages);
        $messagesForLevel = $messages[$messageLevel];

        // With a wrong configuration, we expect the import process to abort with a single message
        // of level "ERROR"
        // NOTE: the serializing of the Importer messages is a quick way to debug anything gone wrong
        self::assertEquals(AbstractMessage::ERROR, $messageLevel, serialize($messages));
        self::assertCount(1, $messagesForLevel);
    }
}