<?php

namespace Cobweb\ExternalImport\Tests\Functional\Handler;

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
use Cobweb\ExternalImport\Handler\ArrayHandler;
use Cobweb\ExternalImport\Importer;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;

/**
 * Test suite for the ArrayHandler class.
 *
 * @package Cobweb\ExternalImport\Tests\Functional\Step
 */
class ArrayHandlerTest extends FunctionalTestCase
{
    /**
     * @var ArrayHandler
     */
    protected $subject;

    /**
     * @var Importer Mocked Importer instance
     */
    protected $importer;

    public function setUp()
    {
        parent::setUp();
        $configuration = $this->createMock(Configuration::class);
        $configuration->method('getColumnConfiguration')
                ->willReturn(
                        [
                                'name' => [
                                        'field' => 'normal_field'
                                ],
                                'brand' => [
                                        'arrayPath' => 'brand|name',
                                        'arrayPathSeparator' => '|'
                                ],
                                'special' => [
                                        'field' => 'special_field',
                                        Configuration::DO_NOT_SAVE_KEY => true
                                ]
                        ]
                );
        $this->importer = $this->createMock(Importer::class);
        $this->importer->method('getExternalConfiguration')
                ->willReturn($configuration);
        $this->subject = new ArrayHandler();
    }

    public function rawDataProvider(): array
    {
        return [
                'empty raw data' => [
                        [],
                        []
                ],
                'raw data with non matching fields' => [
                        [
                                [
                                        'crazy_field' => 2
                                ],
                                [
                                        'crazy_field' => 1
                                ]
                        ],
                        []
                ],
                'raw data with no additional field' => [
                        [
                                [
                                        'normal_field' => 2
                                ],
                                [
                                        'normal_field' => 1
                                ],
                                [
                                        'crazy_field' => 42
                                ]
                        ],
                        [
                                [
                                        'name' => 2
                                ],
                                [
                                        'name' => 1
                                ]
                        ]
                ],
                'raw data with array path' => [
                        [
                                [
                                        'brand' => [
                                                'name' => 'foo'
                                        ]
                                ],
                                [
                                        'brand' => [
                                                'name' => 'bar'
                                        ]
                                ],
                                [
                                        'crazy_field' => 42
                                ]
                        ],
                        [
                                [
                                        'brand' => 'foo'
                                ],
                                [
                                        'brand' => 'bar'
                                ]
                        ]
                ],
                'raw data with additional field' => [
                        [
                                [
                                        'normal_field' => 2,
                                        'special_field' => 'foo'
                                ],
                                [
                                        'normal_field' => 1,
                                        'special_field' => 'bar'
                                ]
                        ],
                        [
                                [
                                        'name' => 2,
                                        'special' => 'foo'
                                ],
                                [
                                        'name' => 1,
                                        'special' => 'bar'
                                ]
                        ]
                ]
        ];
    }

    /**
     * @test
     * @param array $rawData
     * @param array $expectedStructure
     * @dataProvider rawDataProvider
     */
    public function handleDataReturnsStructureData($rawData, $expectedStructure): void
    {
        $structuredData = $this->subject->handleData($rawData, $this->importer);
        self::assertSame(
                $expectedStructure,
                $structuredData
        );
    }
}