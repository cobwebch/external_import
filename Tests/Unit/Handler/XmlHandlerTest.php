<?php

declare(strict_types=1);

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
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;

/**
 * Test suite for the XmlHandler class.
 */
class XmlHandlerTest extends UnitTestCase
{
    /**
     * @var XmlHandler
     */
    protected XmlHandler $subject;

    public function setUp(): void
    {
        parent::setUp();
        $this->subject = new XmlHandler(
            $this->getMockBuilder(EventDispatcher::class)->disableOriginalConstructor()->getMock()
        );
    }

    public function getValueSuccessProvider(): array
    {
        return [
            'fixed value - number zero' => [
                'structure' => '<item>foo</item>',
                'configuration' => [
                    'value' => 0,
                ],
                'result' => 0,
            ],
            'fixed value - number non-zero' => [
                'structure' => '<item>foo</item>',
                'configuration' => [
                    'value' => 12,
                ],
                'result' => 12,
            ],
            'fixed value - string empty' => [
                'structure' => '<item>foo</item>',
                'configuration' => [
                    'value' => '',
                ],
                'result' => '',
            ],
            'fixed value - string not empty' => [
                'structure' => '<item>foo</item>',
                'configuration' => [
                    'value' => 'hey',
                ],
                'result' => 'hey',
            ],
            'direct simple value' => [
                'structure' => '<item>foo</item>',
                'configuration' => [
                    'field' => 'item',
                ],
                'result' => 'foo',
            ],
            'xpath value' => [
                'structure' => '<item><bar>foo</bar></item>',
                'configuration' => [
                    'xpath' => 'item/bar',
                ],
                'result' => 'foo',
            ],
            'substructure as string' => [
                'structure' => '<item><foo>me</foo><bar>you</bar></item>',
                'configuration' => [
                    'field' => 'item',
                ],
                'result' => 'meyou',
            ],
            'substructure as xml' => [
                'structure' => '<item><foo>me</foo><bar>you</bar></item>',
                'configuration' => [
                    'field' => 'item',
                    'xmlValue' => true,
                ],
                'result' => '<foo>me</foo><bar>you</bar>',
            ],
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
    public function getValueReturnsValueIfFound(string $structure, array $configuration, $result): void
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
                        'field' => 'foo',
                    ],
                    'second' => [
                        'xpath' => 'bar/who',
                    ],
                    'third' => [
                        'field' => 'unknown',
                    ],
                ],
                'result' => [
                    [
                        'first' => 'me',
                        'second' => 'you',
                    ],
                    [
                        'first' => 'me2',
                        'second' => 'you2',
                    ],
                ],
            ],
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
    public function getSubstructureValuesReturnsExpectedRows(string $structure, array $configuration, array $result): void
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
