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
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Testcase for the External Import importer
 *
 * @author Francois Suter <typo3@cobweb.ch>
 * @package TYPO3
 * @subpackage tx_externalimport
 */
class ImporterTest extends \Nimut\TestingFramework\TestCase\FunctionalTestCase
{
    /**
     * @var Importer
     */
    protected $subject = null;

    protected function setUp()
    {
        if (!ExtensionManagementUtility::isLoaded('externalimport_test')) {
            self::markTestSkipped('Test extension "externalimport_test" is not loaded.');
        }
        if (!$this->createDatabase()) {
            self::markTestSkipped('Test database could not be created.');
        }
        $this->importExtensions(['core']);
        $this->importExtensions(['externalimport_test']);

        $objectManager = new ObjectManager();
        $this->subject = $objectManager->get(Importer::class);
    }

    protected function tearDown()
    {
//        $this->dropDatabase();
    }

    /**
     * Imports the "tag" elements and checks whether we have the right count or not
     * (5 expected on a total of 6, because one is filtered away by
     * \Cobweb\ExternalimportTest\Service\TagsPreprocessor::preprocessRawRecordset())
     *
     * @test
     */
    public function importTagsWithImporterStoresFiveRecords() {
        $this->importDataSet(__DIR__ . '/Fixtures/StoragePage.xml');
        $this->subject->setForcedStoragePid(1);

        $messages = $this->subject->synchronizeData(
                'tx_externalimporttest_tag',
                0
        );
        $databaseResult = \Tx_Phpunit_Service_Database::select(
                'uid',
                'tx_externalimporttest_tag'
        );
        $countRecords = 0;
        while ($row = $databaseResult->fetch_assoc()) {
            $countRecords++;
        }
        // NOTE: the serializing of the Importer messages is a quick way to debug anything gone wrong
        self::assertEquals(5, $countRecords, serialize($messages));
    }

    /**
     * Imports the "sys_category" elements and checks whether we have the right count or not
     * (3 expected)
     *
     * @test
     */
    public function importCategoriesWithImporterStoresFourRecordsWithOneParentRelation() {
        $this->importDataSet(__DIR__ . '/Fixtures/StoragePage.xml');
        $this->subject->setForcedStoragePid(1);

        $messages = $this->subject->synchronizeData(
                'sys_category',
                'product_categories'
        );
        $databaseResult = \Tx_Phpunit_Service_Database::select(
                'uid',
                'sys_category'
        );
        $countRecords = 0;
        while ($row = $databaseResult->fetch_assoc()) {
            $countRecords++;
        }
        $databaseResult = \Tx_Phpunit_Service_Database::select(
                'uid',
                'sys_category',
                'parent > 0'
        );
        // Count records having a parent
        $countChildren = 0;
        while ($row = $databaseResult->fetch_assoc()) {
            $countChildren++;
        }

        // NOTE: the serializing of the Importer messages is a quick way to debug anything gone wrong
        self::assertEquals(4, $countRecords, serialize($messages));
        self::assertEquals(1, $countChildren);
    }

    /**
     * Imports the "products" with the "base" configuration and checks whether we have the right count or not
     * (2 expected). Furthermore relations with categories and tags are tested.
     *
     * @test
     */
    public function importBaseProductsWithImporterStoresTwoRecordsAndCreatesRelations() {
        $this->importDataSet(__DIR__ . '/Fixtures/StoragePage.xml');
        $this->subject->setForcedStoragePid(1);

        // Import tags categories first, so that relations can be created to them from products
        $messages = $this->subject->synchronizeData(
                'tx_externalimporttest_tag',
                0
        );
        $this->subject->synchronizeData(
                'sys_category',
                'product_categories'
        );
        $messages = $this->subject->synchronizeData(
                'tx_externalimporttest_product',
                'base'
        );
        // Get the number of products stored
        $databaseResult = \Tx_Phpunit_Service_Database::select(
                'uid,tags',
                'tx_externalimporttest_product',
                '',
                '',
                // Ensure consistent order for safe comparison
                'uid ASC'
        );
        $countProducts = 0;
        $tagRelations = array();
        while ($row = $databaseResult->fetch_assoc()) {
            $countProducts++;
            $tagRelations[] = $row['tags'];
        }
        // Get the number of categories relations created
        $databaseResult = \Tx_Phpunit_Service_Database::select(
                'uid_local',
                'sys_category_record_mm',
                'tablenames = \'tx_externalimporttest_product\''
        );
        $countRelations = 0;
        while ($row = $databaseResult->fetch_assoc()) {
            $countRelations++;
        }
        // NOTE: the serializing of the Importer messages is a quick way to debug anything gone wrong
        self::assertEquals(2, $countProducts, serialize($messages));
        self::assertEquals(2, $countRelations);
        self::assertSame(
                array(
                        '1,3',
                        '2,3'
                ),
                $tagRelations
        );
    }

    /**
     * Imports the "products" with the "more" configuration and checks whether we have the right count or not
     * (2 expected)
     *
     * @test
     */
    public function importMoreProductsWithImporterStoresTwoRecords() {
        $this->importDataSet(__DIR__ . '/Fixtures/StoragePage.xml');
        $this->subject->setForcedStoragePid(1);

        $messages = $this->subject->synchronizeData(
                'tx_externalimporttest_product',
                'more'
        );
        // Get the number of products stored
        $databaseResult = \Tx_Phpunit_Service_Database::select(
                'uid',
                'tx_externalimporttest_product'
        );
        $countProducts = 0;
        while ($row = $databaseResult->fetch_assoc()) {
            $countProducts++;
        }
        // NOTE: the serializing of the Importer messages is a quick way to debug anything gone wrong
        self::assertEquals(2, $countProducts, serialize($messages));
    }

    /**
     * Imports the "products" with the "more" configuration and checks whether we have the right count or not
     * (2 expected)
     *
     * TODO: this configuration is supposed to test a "no update" scenario. Cannot be tested for now due to missing reporting.
     *
     * @test
     */
    public function importStableProductsWithImporterStoresTwoRecords() {
        $this->importDataSet(__DIR__ . '/Fixtures/StoragePage.xml');
        $this->subject->setForcedStoragePid(1);

        $messages = $this->subject->synchronizeData(
                'tx_externalimporttest_product',
                'stable'
        );
        // Get the number of products stored
        $databaseResult = \Tx_Phpunit_Service_Database::select(
                'uid',
                'tx_externalimporttest_product'
        );
        $countProducts = 0;
        while ($row = $databaseResult->fetch_assoc()) {
            $countProducts++;
        }
        // NOTE: the serializing of the Importer messages is a quick way to debug anything gone wrong
        self::assertEquals(2, $countProducts, serialize($messages));
    }

    /**
     * Imports the "products" with the "products for stores" configuration and checks whether we have
     * the right number of relations or not (6 expected). Also checks the "stock" which used the
     * "additionalFields" mechanism for MM relations.
     *
     * TODO: this configuration is supposed to test a "no update" scenario. Cannot be tested for now due to missing reporting.
     *
     * @test
     */
    public function importProductsForStoresWithImporterCreatesSixRelations() {
        $this->importDataSet(__DIR__ . '/Fixtures/StoragePage.xml');
        $this->subject->setForcedStoragePid(1);

        // First import products and stores, so that relations can be created
        $this->subject->synchronizeData(
                'tx_externalimporttest_product',
                'base'
        );
        $this->subject->synchronizeData(
                'tx_externalimporttest_product',
                'stable'
        );
        $messages = $this->subject->synchronizeData(
                'tx_externalimporttest_store',
                0
        );

        $messages = $this->subject->synchronizeData(
                'tx_externalimporttest_product',
                'products_for_stores'
        );
        // Get the number of relations created
        $databaseResult = \Tx_Phpunit_Service_Database::select(
                'uid_local,stock',
                'tx_externalimporttest_store_product_mm',
                '',
                '',
                // Ensure consistent order for safe comparison
                'stock ASC'
        );
        $countRelations = 0;
        $stocks = array();
        while ($row = $databaseResult->fetch_assoc()) {
            $countRelations++;
            $stocks[] = $row['stock'];
        }
        // NOTE: the serializing of the Importer messages is a quick way to debug anything gone wrong
        self::assertEquals(6, $countRelations, serialize($messages));
        self::assertSame(
                array('5', '6', '8', '10', '12', '20'),
                $stocks
        );
    }

    /**
     * Provides a list of erroneous import configurations.
     *
     * @return array
     */
    public function wrongConfigurationNames()
    {
        return array(
            'Wrong ctrl configuration' => array(
                    'control_configuration_errors'
            ),
            'Wrong column configuration' => array(
                    'column_configuration_errors'
            )
        );
    }

    /**
     * Checks that running an erroneous configuration exists early with a single error message.
     *
     * @param string $configuration Name of the configuration to check
     * @test
     * @dataProvider wrongConfigurationNames
     */
    public function importProductsWithErroneousConfigurationReturnsError($configuration) {
        $messages = $this->subject->synchronizeData(
                'tx_externalimporttest_product',
                $configuration
        );
        $messageLevel = key($messages);
        $messagesForLevel = $messages[$messageLevel];

        // With a wrong configuration, we expect the import process to abort with a single message
        // of level "ERROR"
        // NOTE: the serializing of the Importer messages is a quick way to debug anything gone wrong
        self::assertEquals(FlashMessage::ERROR, $messageLevel, serialize($messages));
        self::assertCount(1, $messagesForLevel);
    }

    /**
     * Imports the "stores" and checks whether we have the right count or not
     * (2 expected). Also checks relations between products and stores,
     * including the "stock" additional field.
     *
     * @test
     */
    public function importBundlesWithImporterStoresThreeRecordsAndCreatesOrderedRelations() {
        $this->importDataSet(__DIR__ . '/Fixtures/StoragePage.xml');
        $this->subject->setForcedStoragePid(1);

        // First import all products, so that relations can be created
        $this->subject->synchronizeData(
                'tx_externalimporttest_product',
                'base'
        );
        $this->subject->synchronizeData(
                'tx_externalimporttest_product',
                'more'
        );
        $this->subject->synchronizeData(
                'tx_externalimporttest_product',
                'stable'
        );

        $messages = $this->subject->synchronizeData(
                'tx_externalimporttest_bundle',
                0
        );
        // Get the number of products stored
        $databaseResult = \Tx_Phpunit_Service_Database::select(
                'uid',
                'tx_externalimporttest_bundle'
        );
        $countBundles = 0;
        while ($row = $databaseResult->fetch_assoc()) {
            $countBundles++;
        }
        // Get the number of relations created
        $databaseResult = \Tx_Phpunit_Service_Database::select(
                'uid_local,uid_foreign,sorting',
                'tx_externalimporttest_bundle_product_mm',
                '',
                '',
                // Ensure consistent order for safe comparison
                'uid_local ASC,sorting ASC'
        );
        $countRelations = 0;
        $sortedProducts = array();
        while ($row = $databaseResult->fetch_assoc()) {
            $countRelations++;
            $sortedProducts[] = $row['uid_foreign'];
        }
        // NOTE: the serializing of the Importer messages is a quick way to debug anything gone wrong
        self::assertEquals(3, $countBundles, serialize($messages));
        self::assertEquals(8, $countRelations);
        self::assertSame(
                array('3', '4', '1', '2', '6', '1', '5', '2'),
                $sortedProducts
        );
    }

    /**
     * Imports the "stores" and checks whether we have the right count or not
     * (2 expected). Also checks relations between products and stores,
     * including the "stock" additional field.
     *
     * @test
     */
    public function importOrdersWithImporterStoresTwoRecordsAndCreatesRelations() {
        $this->importDataSet(__DIR__ . '/Fixtures/StoragePage.xml');
        $this->subject->setForcedStoragePid(1);

        // First import all products, so that relations can be created
        $this->subject->synchronizeData(
                'tx_externalimporttest_product',
                'base'
        );
        $this->subject->synchronizeData(
                'tx_externalimporttest_product',
                'more'
        );
        $this->subject->synchronizeData(
                'tx_externalimporttest_product',
                'stable'
        );

        $messages = $this->subject->synchronizeData(
                'tx_externalimporttest_order',
                0
        );
        // Get the number of products stored
        $databaseResult = \Tx_Phpunit_Service_Database::select(
                'uid',
                'tx_externalimporttest_order'
        );
        $countOrders = 0;
        while ($row = $databaseResult->fetch_assoc()) {
            $countOrders++;
        }
        // Get the number of relations created
        $databaseResult = \Tx_Phpunit_Service_Database::select(
                'uid_local,quantity',
                'tx_externalimporttest_order_items_mm',
                '',
                '',
                // Ensure consistent order for safe comparison
                'uid_local ASC,sorting ASC'
        );
        $countRelations = 0;
        $quantities = array();
        while ($row = $databaseResult->fetch_assoc()) {
            $countRelations++;
            $quantities[] = $row['quantity'];
        }
        // NOTE: the serializing of the Importer messages is a quick way to debug anything gone wrong
        self::assertEquals(2, $countOrders, serialize($messages));
        self::assertEquals(7, $countRelations);
        self::assertSame(
                array('3', '1', '10', '2', '1', '2', '1'),
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
    public function importStoresWithImporterStoresTwoRecordsAndCreatesRelations() {
        $this->importDataSet(__DIR__ . '/Fixtures/StoragePage.xml');
        $this->subject->setForcedStoragePid(1);

        // First import products, so that relations can be created
        $this->subject->synchronizeData(
                'tx_externalimporttest_product',
                'more'
        );

        $messages = $this->subject->synchronizeData(
                'tx_externalimporttest_store',
                0
        );
        // Get the number of products stored
        $databaseResult = \Tx_Phpunit_Service_Database::select(
                'uid',
                'tx_externalimporttest_store'
        );
        $countStores = 0;
        while ($row = $databaseResult->fetch_assoc()) {
            $countStores++;
        }
        // Get the number of relations created
        $databaseResult = \Tx_Phpunit_Service_Database::select(
                'uid_local,stock',
                'tx_externalimporttest_store_product_mm',
                '',
                '',
                // Ensure consistent order for safe comparison
                'stock ASC'
        );
        $countRelations = 0;
        $stocks = array();
        while ($row = $databaseResult->fetch_assoc()) {
            $countRelations++;
            $stocks[] = $row['stock'];
        }
        // NOTE: the serializing of the Importer messages is a quick way to debug anything gone wrong
        self::assertEquals(2, $countStores, serialize($messages));
        self::assertEquals(3, $countRelations);
        self::assertSame(
                array('5', '6', '10'),
                $stocks
        );
    }

    /**
     * Imports the "invoices" elements and checks whether we have the right count or not
     * (3 expected).
     *
     * @test
     */
    public function importInvoicesWithImporterStoresThreeRecords() {
        $this->importDataSet(__DIR__ . '/Fixtures/StoragePage.xml');
        $this->subject->setForcedStoragePid(1);

        $messages = $this->subject->synchronizeData(
                'tx_externalimporttest_invoice',
                0
        );
        $databaseResult = \Tx_Phpunit_Service_Database::select(
                'uid',
                'tx_externalimporttest_invoice'
        );
        $countRecords = 0;
        while ($row = $databaseResult->fetch_assoc()) {
            $countRecords++;
        }
        // NOTE: the serializing of the Importer messages is a quick way to debug anything gone wrong
        self::assertEquals(3, $countRecords, serialize($messages));
    }

    /**
     * Imports the "invoices" elements and checks whether we have the right count or not
     * (3 expected).
     *
     * NOTE: This test fails as this feature has actually never worked properly.
     * See: https://github.com/cobwebch/external_import/issues/11
     *
     * @test
     */
    public function importProductsAsPagesWithImporterCreatesProperPageTree() {
        $this->importDataSet(__DIR__ . '/Fixtures/StoragePage.xml');
        $this->subject->setForcedStoragePid(1);

        $messages = $this->subject->synchronizeData(
                'pages',
                'product_pages'
        );
        // Three new pages should be attached to the storage page
        $databaseResult = \Tx_Phpunit_Service_Database::select(
                'uid',
                'pages',
                'pid = 1'
        );
        $countParentPages = 0;
        while ($row = $databaseResult->fetch_assoc()) {
            $countParentPages++;
        }
        // NOTE: the serializing of the Importer messages is a quick way to debug anything gone wrong
        self::assertEquals(3, $countParentPages, serialize($messages));

        // Next, the page called "Product 1" should have 2 child pages, "Product 2" none and "Product 3" 1 child page
        $pageTree = array(
                array(
                        'title' => 'Product 1',
                        'children' => 2
                ),
                array(
                        'title' => 'Product 2',
                        'children' => 0
                ),
                array(
                        'title' => 'Product 3',
                        'children' => 1
                )
        );
        foreach ($pageTree as $page) {
            $databaseResult = \Tx_Phpunit_Service_Database::select(
                    'COUNT(uid) AS total',
                    'pages',
                    'uid IN (SELECT uid FROM pages WHERE title = \'' . $page['title'] . '\')'
            );
            if ($row = $databaseResult->fetch_assoc()) {
                $children = $row['total'];
            } else {
                $children = 0;
            }
            self::assertEquals($page['children'], $children);
        }
    }
}