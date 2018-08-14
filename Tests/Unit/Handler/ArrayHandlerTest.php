<?php

namespace Cobweb\ExternalImport\Tests\Unit\Handler;

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

use Cobweb\ExternalImport\Handler\ArrayHandler;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * Test suite for the ArrayHandler class.
 *
 * @package Cobweb\ExternalImport\Tests\Functional\Step
 */
class ArrayHandlerTest extends UnitTestCase
{
    /**
     * @var ArrayHandler
     */
    protected $subject;

    public function setUp()
    {
        parent::setUp();
        $this->subject = new ArrayHandler();
    }

    public function getValueSuccessProvider()
    {
        return [
                'direct simple value' => [
                        'record' => [
                                'foo' => 'bar'
                        ],
                        'configuration' => [
                                'field' => 'foo'
                        ],
                        'result' => 'bar'
                ],
                'array path value' => [
                        'record' => [
                                'test' => [
                                        'foo' => 'me',
                                        'bar' => 'you'
                                ]
                        ],
                        'configuration' => [
                                'arrayPath' => 'test/foo'
                        ],
                        'result' => 'me'
                ],
                'substructure' => [
                        'record' => [
                                'test' => [
                                        'foo' => 'me',
                                        'bar' => 'you'
                                ]
                        ],
                        'configuration' => [
                                'field' => 'test'
                        ],
                        'result' => [
                                'foo' => 'me',
                                'bar' => 'you'
                        ]
                ]
        ];
    }

    /**
     * @test
     * @dataProvider getValueSuccessProvider
     * @param array $record
     * @param array $configuration
     * @param mixed $result
     */
    public function getValueReturnsValueIfFound($record, $configuration, $result)
    {
        $value = $this->subject->getValue($record, $configuration);
        self::assertSame(
                $result,
                $value
        );
    }

    public function getValueFailWithInvalidArgumentExceptionProvider()
    {
        return [
                'direct simple value' => [
                        'record' => [
                                'foo' => 'bar'
                        ],
                        'configuration' => [
                                'field' => 'baz'
                        ]
                ],
                'array path value' => [
                        'record' => [
                                'test' => [
                                        'test' => [
                                                'foo' => 'me',
                                                'bar' => 'you'
                                        ]
                                ]
                        ],
                        'configuration' => [
                                'arrayPath' => false
                        ]
                ]
        ];
    }

    /**
     * @test
     * @dataProvider getValueFailWithInvalidArgumentExceptionProvider
     * @param array $record
     * @param array $configuration
     * @expectedException \InvalidArgumentException
     */
    public function getValueThrowsInvalidArgumentExceptionIfValueNotFound($record, $configuration) {
        $value = $this->subject->getValue($record, $configuration);
    }

    public function getSubstructureProvider()
    {
        return [
                [
                        'structure' => [
                                [
                                        'foo' => 'me',
                                        'bar' => [
                                                'who' => 'you'
                                        ],
                                        'baz' => 'them'
                                ],
                                [
                                        'foo' => 'me2',
                                        'bar' => [
                                                'who' => 'you2'
                                        ],
                                        'baz' => 'them2'
                                ]
                        ],
                        'configuration' => [
                                'first' => [
                                        'field' => 'foo'
                                ],
                                'second' => [
                                        'arrayPath' => 'bar/who'
                                ],
                                'third' => [
                                        'field' => 'unknown'
                                ]
                        ],
                        'result' => [
                                [
                                        'first' => 'me',
                                        'second' => 'you'
                                ],
                                [
                                        'first' => 'me2',
                                        'second' => 'you2'
                                ]
                        ]
                ]
        ];
    }

    /**
     * @test
     * @dataProvider getSubstructureProvider
     * @param array $structure
     * @param array $configuration
     * @param array $result
     */
    public function getSubstructureValuesReturnsExpectedRows($structure, $configuration, $result)
    {
        self::assertSame(
                $result,
                $this->subject->getSubstructureValues($structure, $configuration)
        );
    }
}