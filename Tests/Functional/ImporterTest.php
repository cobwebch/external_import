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
use Cobweb\ExternalImport\Step\StoreDataStep;
use Cobweb\ExternalImport\Testing\FunctionalTestCaseWithDatabaseTools;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Testcase for the External Import importer
 */
class ImporterTest extends FunctionalTestCaseWithDatabaseTools
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

    /**
     * Imports the "tag" elements and checks whether we have the right count or not
     * (5 expected on a total of 6, because one is filtered away by
     * \Cobweb\ExternalimportTest\Service\TagsPreprocessor::preprocessRawRecordset())
     */
    #[Test]
    public function importTagsWithImporterStoresFiveRecords(): void
    {
        $messages = $this->subject->synchronize(
            'tx_externalimporttest_tag',
            0
        );
        $countRecords = $this->selectCount(
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
     */
    #[Test]
    public function importCategoriesWithImporterStoresFourRecordsWithOneParentRelation(): void
    {
        $messages = $this->subject->synchronize(
            'sys_category',
            'product_categories'
        );
        // Count imported categories
        $countRecords = $this->selectCount(
            'uid',
            'sys_category'
        );
        // Count records having a parent
        $countChildren = $this->selectCount(
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
     */
    #[Test]
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
        $countDesigners = $this->selectCount(
            'uid',
            'tx_externalimporttest_designer'
        );
        // Count products relations
        $countRelations = $this->selectCount(
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
     */
    #[Test]
    public function importBaseProductsWithImporterStoresTwoRecordsAndCreatesRelations(): void
    {
        $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
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
        $databaseResult = $this->getConnectionPool()->getQueryBuilderForTable('tx_externalimporttest_product')
            ->select('uid', 'tags')
            ->from('tx_externalimporttest_product')
            // Ensure consistent order for safe comparison
            ->orderBy('uid', 'ASC')
            ->executeQuery();
        $countProducts = 0;
        $tagRelations = [];
        while ($row = $databaseResult->fetchAssociative()) {
            $countProducts++;
            $tagRelations[$row['uid']] = $row['tags'];
        }
        // Get the number of categories relations created
        $countRelations = $this->selectCount(
            'uid_local',
            'sys_category_record_mm',
            'tablenames = \'tx_externalimporttest_product\''
        );
        // Get the number and order of sys_file_reference records created
        $databaseResult = $this->getConnectionPool()->getQueryBuilderForTable('sys_file_reference')
            ->select('uid', 'sorting_foreign')
            ->from('sys_file_reference')
            // Ensure consistent order for safe comparison
            ->orderBy('sorting_foreign', 'ASC')
            ->executeQuery();
        $countFiles = 0;
        $sorting = [];
        while ($row = $databaseResult->fetchAssociative()) {
            $countFiles++;
            $sorting[$row['uid']] = $row['sorting_foreign'];
        }
        // NOTE: the serializing of the Importer messages is a quick way to debug anything gone wrong
        self::assertEquals(2, $countProducts, serialize($messages));
        self::assertEquals(2, $countRelations);
        self::assertSame(
            [
                1 => '1,3',
                2 => '2,3',
            ],
            $tagRelations
        );
        self::assertEquals(2, $countFiles);
        self::assertSame(
            [
                2 => 1,
                1 => 2,
            ],
            $sorting
        );
    }

    /**
     * Imports the "products" with the "more" configuration and checks whether we have the right count or not
     * (2 expected)
     */
    #[Test]
    public function importMoreProductsWithImporterStoresTwoRecords(): void
    {
        $messages = $this->subject->synchronize(
            'tx_externalimporttest_product',
            'more'
        );
        // Get the number of products stored
        $countProducts = $this->selectCount(
            'uid',
            'tx_externalimporttest_product'
        );
        // NOTE: the serializing of the Importer messages is a quick way to debug anything gone wrong
        self::assertEquals(2, $countProducts, serialize($messages));
    }

    /**
     * Imports the "products" with the "stable" configuration and checks whether we have the right count or not
     * (2 expected).
     *
     * This test also checks that old MM relations are removed and that the useColumnIndex override has been taken
     * into account (product names are uppercase)
     */
    #[Test]
    public function importStableProductsWithImporterStoresTwoRecordsAndRemovesOldRelations(): void
    {
        // Create 1 category and 1 relation to it. The relation should be removed by the import process.
        $this->importCSVDataSet(__DIR__ . '/Fixtures/CategoriesMM.csv');

        $messages = $this->subject->synchronize(
            'tx_externalimporttest_product',
            'stable'
        );
        // Get the number of products stored
        $countProducts = $this->selectCount(
            'uid',
            'tx_externalimporttest_product'
        );
        $products = $this->getConnectionPool()->getQueryBuilderForTable('tx_externalimporttest_product')
            ->select('name')
            ->from('tx_externalimporttest_product')
            ->orderBy('sku', 'asc')
            ->executeQuery()
            ->fetchAllAssociative();
        // Get the categories relations
        $relations = $this->getConnectionPool()->getQueryBuilderForTable('sys_category_record_mm')
            ->select('uid_local', 'uid_foreign')
            ->from('sys_category_record_mm')
            ->where('tablenames = \'tx_externalimporttest_product\'')
            ->executeQuery()
            ->fetchAllAssociative();
        $countRelations = count($relations);
        $firstRelation = array_pop($relations);
        // NOTE: the serializing of the Importer messages is a quick way to debug anything gone wrong
        self::assertEquals(
            2,
            $countProducts,
            serialize($messages)
        );
        self::assertEquals(
            'OLD IRON KETTLE',
            $products[0]['name']
        );
        self::assertEquals(
            'OLD FRYING PAN',
            $products[1]['name']
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

    /**
     * Imports the "products" with the "products for stores" configuration and checks whether we have
     * the right number of relations or not (6 expected). Also checks the "stock" which used the
     * "additionalFields" mechanism for MM relations.
     */
    #[Test]
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
        $databaseResult = $this->getConnectionPool()->getQueryBuilderForTable('tx_externalimporttest_store_product')
            ->select('stock')
            ->from('tx_externalimporttest_store_product')
            // Ensure consistent order for safe comparison
            ->orderBy('stock', 'ASC')
            ->executeQuery();
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
     * (3 expected out of 4, one does not have a referenceUid). Also checks relations between products and bundles,
     * including the order of the products inside the bundles.
     */
    #[Test]
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
        $countBundles = $this->selectCount(
            'uid',
            'tx_externalimporttest_bundle'
        );
        // Get the number of relations created
        /** @var \Doctrine\DBAL\Driver\Statement $databaseResult */
        $databaseResult = $this->getConnectionPool()->getQueryBuilderForTable('tx_externalimporttest_bundle_product_mm')
            ->select('uid_local', 'uid_foreign', 'sorting')
            ->from('tx_externalimporttest_bundle_product_mm')
            // Ensure consistent order for safe comparison
            ->orderBy('uid_local', 'ASC')
            ->addOrderBy('sorting', 'ASC')
            ->executeQuery();
        $countRelations = 0;
        $sortedProducts = [];
        while ($row = $databaseResult->fetchAssociative()) {
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
     * Imports the bundles with one bundle already existing in the database.
     * The notes field of that bundle are expected to be nulled.
     */
    #[Test]
    public function importBundlesWithImporterOnExistingBundleSetNull(): void
    {
        try {
            $this->importCSVDataSet(__DIR__ . '/Fixtures/Bundles.csv');
        } catch (\Exception $e) {
            self::markTestSkipped(
                sprintf(
                    'Orders fixture could not be loaded (Exception: %s [%d])',
                    $e->getMessage(),
                    $e->getCode()
                )
            );
        }
        $messages = $this->subject->synchronize(
            'tx_externalimporttest_bundle',
            0
        );
        $updatedBundle = $this->getConnectionPool()->getQueryBuilderForTable('tx_externalimporttest_bundle')
            ->select('notes')
            ->from('tx_externalimporttest_bundle')
            ->where('bundle_code = \'PAIN02\'')
            ->executeQuery()
            ->fetchOne();
        self::assertNull($updatedBundle['notes']  ?? null, serialize($messages));
    }

    /**
     * Imports the "orders" and checks whether we have the right count or not
     * (2 expected = 2 imported and 1 existing deleted). Also checks relations between products and orders,
     * including the "quantity" additional field.
     */
    #[Test]
    public function importOrdersWithImporterStoresThreeRecordsAndCreatesRelationsAndDeletesExistingOrder(): void
    {
        try {
            $this->importCSVDataSet(__DIR__ . '/Fixtures/Orders.csv');
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
        $countOrders = $this->selectCount(
            'uid',
            'tx_externalimporttest_order'
        );
        // Get the number of relations created
        $databaseResult = $this->getConnectionPool()->getQueryBuilderForTable('tx_externalimporttest_order_items')
            ->select('uid_local', 'quantity')
            ->from('tx_externalimporttest_order_items')
            // Ensure consistent order for safe comparison
            ->orderBy('uid_local', 'ASC')
            ->executeQuery();
        $countRelations = 0;
        $quantities = [];
        while ($row = $databaseResult->fetchAssociative()) {
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
     * (3 expected). Also checks relations between products and stores,
     * including the "stock" additional field. One existing product for a given store
     * is expected to be deleted, because absent from the import.
     */
    #[Test]
    public function importStoresWithImporterStoresThreeRecordsAndCreatesRelations(): void
    {
        // Prepare one product in a store that has none in the imported data. It should get deleted.
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProductsInStores.csv');

        // First import products, so that relations can be created
        $this->subject->synchronize(
            'tx_externalimporttest_product',
            'more'
        );

        $messages = $this->subject->synchronize(
            'tx_externalimporttest_store',
            0
        );
        // Get the number of stores stored (NOTE: one already exists in the fixture)
        $countStores = $this->selectCount(
            'uid',
            'tx_externalimporttest_store'
        );
        // Get the number of relations created
        $databaseResult = $this->getConnectionPool()->getQueryBuilderForTable('tx_externalimporttest_store_product')
            ->select('stock')
            ->from('tx_externalimporttest_store_product')
            // Ensure consistent order for safe comparison
            ->orderBy('stock', 'ASC')
            ->executeQuery();
        $stocks = [];
        while ($row = $databaseResult->fetchAssociative()) {
            $stocks[] = $row['stock'];
        }
        // NOTE: the serializing of the Importer messages is a quick way to debug anything gone wrong
        self::assertEquals(
            3,
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
     */
    #[Test]
    public function importInvoicesWithImporterStoresThreeRecords(): void
    {
        $messages = $this->subject->synchronize(
            'tx_externalimporttest_invoice',
            0
        );
        $countRecords = $this->selectCount(
            'uid',
            'tx_externalimporttest_invoice'
        );
        // NOTE: the serializing of the Importer messages is a quick way to debug anything gone wrong
        self::assertEquals(3, $countRecords, serialize($messages));
    }

    /**
     * Imports the products as pages and checks whether the proper page tree has been created.
     */
    #[Test]
    public function importProductsAsPagesWithImporterCreatesProperPageTree(): void
    {
        $messages = $this->subject->synchronize(
            'pages',
            'product_pages'
        );
        // Three new pages should be attached to the storage page
        $countParentPages = $this->selectCount(
            'uid',
            'pages',
            'pid = 1',
            true
        );
        // NOTE: the serializing of the Importer messages is a quick way to debug anything gone wrong
        self::assertEquals(3, $countParentPages, serialize($messages));

        // Next, the page called "Product 1" should have 2 child pages, "Product 2" none and "Product 3" 1 child page
        $pageTree = [
            [
                'title' => 'Product 1',
                'children' => 2,
            ],
            [
                'title' => 'Product 2',
                'children' => 0,
            ],
            [
                'title' => 'Product 3',
                'children' => 1,
            ],
        ];
        foreach ($pageTree as $page) {
            $children = $this->selectCount(
                'uid',
                'pages',
                'pid IN (SELECT uid FROM pages WHERE title = \'' . $page['title'] . '\')',
                true
            );
            self::assertEquals($page['children'], $children);
        }
    }

    /**
     * Imports a product to a different page, thus moving the product.
     */
    #[Test]
    public function importUpdatedProductsWithImporterMovesProductsAndUpdatesSlugs(): void
    {
        try {
            $this->importCSVDataSet(__DIR__ . '/Fixtures/ExtraStoragePage.csv');
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
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_externalimporttest_product');
        $queryBuilder->select('name')
            ->from('tx_externalimporttest_product')
            ->where(
                $queryBuilder->expr()->eq('pid', 2)
            );
        $movedProducts = $queryBuilder->executeQuery()->fetchAllAssociative();
        // A single product should have been moved
        self::assertCount(
            1,
            $movedProducts,
            serialize($messages)
        );
        // That product should have an updated name and slug
        self::assertSame(
            'Long sword (updated)',
            $movedProducts[0]['name']
        );
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_externalimporttest_product');
        $queryBuilder->select('path_segment')
            ->from('tx_externalimporttest_product')
            ->where(
                $queryBuilder->expr()->eq('pid', 2)
            );
        $movedProducts = $queryBuilder->executeQuery()->fetchAllAssociative();
        self::assertSame(
            'long-sword-updated',
            $movedProducts[0]['path_segment']
        );
    }

    /**
     * Provides a list of erroneous import configurations.
     */
    public static function wrongConfigurationNamesProvider(): array
    {
        return [
            'Wrong general configuration' => [
                'table' => 'tx_externalimporttest_product',
                'configuration' => 'general_configuration_errors',
            ],
            'Wrong column configuration' => [
                'table' => 'sys_categories',
                'configuration' => 'column_configuration_errors',
            ],
        ];
    }

    /**
     * Checks that running an erroneous configuration exits early with a single error message.
     */
    #[Test] #[DataProvider('wrongConfigurationNamesProvider')]
    public function importWithErroneousConfigurationReturnsError(string $table, string $configuration): void
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
        self::assertEquals(ContextualFeedbackSeverity::ERROR->value, $messageLevel, serialize($messages));
        self::assertCount(1, $messagesForLevel);
    }
}
