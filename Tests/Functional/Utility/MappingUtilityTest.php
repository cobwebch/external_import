<?php

namespace Cobweb\ExternalImport\Tests\Functional\Utility;

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

use Cobweb\ExternalImport\Utility\MappingUtility;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Test class for the MappingUtility.
 *
 * NOTE: the MappingUtility is also covered by unit tests.
 *
 * @package Cobweb\ExternalImport\Tests\Functional\Utility
 */
class MappingUtilityTest extends FunctionalTestCase
{
    protected $testExtensionsToLoad = [
            'typo3conf/ext/external_import',
            'typo3conf/ext/externalimport_test'
    ];

    /**
     * @var MappingUtility
     */
    protected $subject;

    public function setUp()
    {
        parent::setUp();
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->subject = $objectManager->get(MappingUtility::class);
    }

    public function mappingConfigurationProvider(): array
    {
        return [
                'Value map takes precedence' => [
                        [
                                'valueMap' => [
                                        'foo' => 1,
                                        'bar' => 2
                                ],
                                'table' => 'sys_category',
                                'referenceField' => 'external_key'
                        ],
                        [
                                'foo' => 1,
                                'bar' => 2
                        ]
                ],
                'All records (no valueField property)' => [
                        [
                                'table' => 'sys_category',
                                'referenceField' => 'external_key'
                        ],
                        [
                                'CAT1' => 1,
                                'CAT2' => 2
                        ]
                ],
                'All records (with valueField property)' => [
                        [
                                'table' => 'sys_category',
                                'referenceField' => 'external_key',
                                'valueField' => 'uid'
                        ],
                        [
                                'CAT1' => 1,
                                'CAT2' => 2
                        ]
                ],
                'Filtered records' => [
                        [
                                'table' => 'sys_category',
                                'referenceField' => 'external_key',
                                'whereClause' => 'pid = 1'
                        ],
                        [
                                'CAT1' => 1
                        ]
                ],
        ];
    }

    /**
     * @test
     * @dataProvider mappingConfigurationProvider
     * @param array $mappingConfiguration
     * @param array $results
     * @throws \Nimut\TestingFramework\Exception\Exception
     */
    public function getMappingReturnsRecordsToMap($mappingConfiguration, $results)
    {
        $this->importDataSet(__DIR__ . '/../Fixtures/Mappings.xml');
        $mappings = $this->subject->getMapping($mappingConfiguration);
        self::assertSame(
                $results,
                $mappings
        );
    }
}