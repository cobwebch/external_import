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

use Cobweb\ExternalImport\Handler\XmlHandler;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * Test suite for the ArrayHandler class.
 *
 * @package Cobweb\ExternalImport\Tests\Functional\Step
 */
class XmlHandlerTest extends UnitTestCase
{
    /**
     * @var XmlHandler
     */
    protected $subject;

    public function setUp()
    {
        parent::setUp();
        $this->subject = new XmlHandler();
    }

    public function getValueSuccessProvider(): array
    {
        return [
                'direct simple value' => [
                        'structure' => '<item>foo</item>',
                        'configuration' => [
                                'field' => 'item'
                        ],
                        'result' => 'foo'
                ],
                'xpath value' => [
                        'structure' => '<item><bar>foo</bar></item>',
                        'configuration' => [
                                'xpath' => 'item/bar'
                        ],
                        'result' => 'foo'
                ],
                'substructure as string' => [
                        'structure' => '<item><foo>me</foo><bar>you</bar></item>',
                        'configuration' => [
                                'field' => 'item'
                        ],
                        'result' => 'meyou'
                ],
                'substructure as xml' => [
                        'structure' => '<item><foo>me</foo><bar>you</bar></item>',
                        'configuration' => [
                                'field' => 'item',
                                'xmlValue' => true
                        ],
                        'result' => '<foo>me</foo><bar>you</bar>'
                ]
        ];
    }

    /**
     * @test
     * @dataProvider getValueSuccessProvider
     * @param string $structure
     * @param array $configuration
     * @param mixed $result
     * @throws \Exception
     */
    public function getValueReturnsValueIfFound($structure, $configuration, $result): void
    {
        // Load the XML into a DOM object
        $dom = new \DOMDocument();
        $dom->loadXML($structure, LIBXML_PARSEHUGE);
        // Instantiate a XPath object and load with any defined namespaces
        $xPathObject = new \DOMXPath($dom);
        $value = $this->subject->getValue($dom, $configuration, $xPathObject);
        self::assertSame(
                $result,
                $value
        );
    }

    public function getSubstructureProvider(): array
    {
        return [
                [
                        // Test elements are always wrapped in an <item> tag
                        'structure' => '<items><item><foo>me</foo><bar><who>you</who></bar><baz>them</baz></item><item><foo>me2</foo><bar><who>you2</who></bar><baz>them2</baz></item></items>',
                        'configuration' => [
                                'first' => [
                                        'field' => 'foo'
                                ],
                                'second' => [
                                        'xpath' => 'bar/who'
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
     * @param string $structure
     * @param array $configuration
     * @param array $result
     * @throws \Exception
     */
    public function getSubstructureValuesReturnsExpectedRows($structure, $configuration, $result): void
    {
        // Load the XML into a DOM object
        $dom = new \DOMDocument();
        $dom->loadXML($structure, LIBXML_PARSEHUGE);
        // Instantiate a XPath object and load with any defined namespaces
        $xPathObject = new \DOMXPath($dom);
        $nodeList = $dom->getElementsByTagName('item');
        self::assertSame(
                $result,
                $this->subject->getSubstructureValues($nodeList, $configuration, $xPathObject)
        );
    }
}