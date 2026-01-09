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

use Cobweb\ExternalImport\Exception\XpathSelectionFailedException;
use Cobweb\ExternalImport\Handler\XmlHandler;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test suite for the XmlHandler class.
 */
class XmlHandlerTest extends UnitTestCase
{
    protected XmlHandler $subject;

    public function setUp(): void
    {
        parent::setUp();
        $this->subject = new XmlHandler(
            $this->getMockBuilder(EventDispatcher::class)->disableOriginalConstructor()->getMock()
        );
    }

    public static function selectWithXpathValidProvider(): array
    {
        return [
            'node selection without context' => [
                'structure' => <<<'XML'
<items>
    <item>foo</item>
    <item>bar</item>
</items>
XML,
                'xpath' => 'item',
                'context' => '',
                'result' => <<<XML
<?xml version="1.0"?>
<item>foo</item>
<item>bar</item>

XML,
            ],
            'node selection with context' => [
                'structure' => <<<'XML'
<items>
    <good>
        <item>foo</item>
    </good>
    <bad>
        <item>bar</item>
    </bad>
</items>
XML,
                'xpath' => 'item',
                'context' => 'good',
                'result' => <<<XML
<?xml version="1.0"?>
<item>foo</item>

XML,
            ],
            'string selection' => [
                'structure' => <<<'XML'
<items>
    <item id="foo1" quality="poor">foo</item>
</items>
XML,
                'xpath' => 'concat(@id, \'-\', @quality)',
                'context' => 'item',
                'result' => 'foo1-poor',
            ],
        ];
    }

    #[Test] #[DataProvider('selectWithXpathValidProvider')]
    public function selectWithXpathReturnsNodeListOrStringWithValidPath(string $structure, string $xpath, string $context, string $result): void
    {
        $dom = new \DOMDocument();
        $dom->loadXML($structure, LIBXML_PARSEHUGE);
        $xPathObject = new \DOMXPath($dom);
        $contextNode = null;
        if (!empty($context)) {
            $contextNode = $dom->getElementsByTagName($context)->item(0);
        }
        $nodeList = $this->subject->selectWithXpath($xPathObject, $xpath, $contextNode);
        if (is_string($nodeList)) {
            $effectiveResult = $nodeList;
        } else {
            $resultingDocument = new \DOMDocument();
            // Test the result by writing the selected nodes to a new document
            foreach ($nodeList as $node) {
                $node = $resultingDocument->importNode($node, true);
                $resultingDocument->appendChild($node);
            }
            $effectiveResult = $resultingDocument->saveXML();
        }
        self::assertSame(
            $result,
            $effectiveResult,
        );
    }

    public static function selectWithXpathInvalidProvider(): array
    {
        return [
            'not existing node' => [
                'structure' => <<<'XML'
<items>
    <item>foo</item>
    <item>bar</item>
</items>
XML,
                'xpath' => 'blob',
                'context' => '',
            ],
            'wrong context' => [
                'structure' => <<<'XML'
<items>
    <good>
        <goodItem>foo</goodItem>
    </good>
    <bad>
        <badItem>bar</badItem>
    </bad>
</items>
XML,
                'xpath' => 'badItem',
                'context' => 'good',
            ],
        ];
    }

    #[Test] #[DataProvider('selectWithXpathInvalidProvider')]
    public function selectWithXpathThrowsExceptionWithInvalidPath(string $structure, string $xpath, string $context): void
    {
        $this->expectException(XpathSelectionFailedException::class);
        $dom = new \DOMDocument();
        $dom->loadXML($structure, LIBXML_PARSEHUGE);
        $xPathObject = new \DOMXPath($dom);
        $contextNode = null;
        if (!empty($context)) {
            $contextNode = $dom->getElementsByTagName($context)->item(0);
        }
        $this->subject->selectWithXpath($xPathObject, $xpath, $contextNode);
    }

    public static function getValueSuccessProvider(): array
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
            'attribute value' => [
                'structure' => '<item id="1">foo</item>',
                'configuration' => [
                    'field' => 'item',
                    'attribute' => 'id',
                ],
                'result' => '1',
            ],
            'xpath value' => [
                'structure' => '<item><bar>foo</bar></item>',
                'configuration' => [
                    'xpath' => 'item/bar',
                ],
                'result' => 'foo',
            ],
            'xpath value, with function' => [
                'structure' => '<item id="bar1" name="Foo">foo</item>',
                'configuration' => [
                    'field' => 'item',
                    'xpath' => 'concat(@id, \'-\', @name)',
                ],
                'result' => 'bar1-Foo',
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
     * @throws \Exception
     */
    #[Test] #[DataProvider('getValueSuccessProvider')]
    public function getValueReturnsValueIfFound(string $structure, array $configuration, mixed $result): void
    {
        $dom = new \DOMDocument();
        $dom->loadXML($structure, LIBXML_PARSEHUGE);
        $xPathObject = new \DOMXPath($dom);
        $value = $this->subject->getValue($dom, $configuration, $xPathObject);
        self::assertSame(
            $result,
            $value
        );
    }

    public static function getSubstructureProvider(): array
    {
        return [
            [
                // Test elements are always wrapped in an <item> tag
                'structure' => <<<'EOF'
<items>
    <item>
        <foo id="1">me</foo>
        <bar><who>you</who></bar>
        <baz>them</baz>
    </item>
    <item>
        <foo id="2">me2</foo>
        <bar><who>you2</who></bar>
        <baz>them2</baz>
    </item>
</items>
EOF
                ,
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
                    'fourth' => [
                        'field' => 'foo',
                        'attribute' => 'id',
                    ],
                ],
                'result' => [
                    [
                        'first' => 'me',
                        'second' => 'you',
                        'fourth' => '1',
                    ],
                    [
                        'first' => 'me2',
                        'second' => 'you2',
                        'fourth' => '2',
                    ],
                ],
            ],
        ];
    }

    /**
     * @throws \Exception
     */
    #[Test] #[DataProvider('getSubstructureProvider')]
    public function getSubstructureValuesReturnsExpectedRows(string $structure, array $configuration, array $result): void
    {
        $dom = new \DOMDocument();
        $dom->loadXML($structure, LIBXML_PARSEHUGE);
        $xPathObject = new \DOMXPath($dom);
        $nodeList = $dom->getElementsByTagName('item');
        self::assertSame(
            $result,
            $this->subject->getSubstructureValues($nodeList, $configuration, $xPathObject)
        );
    }

    public static function getNodeListProvider(): array
    {
        return [
            'node itself' => [
                'structure' => '<?xml version="1.0" encoding="UTF-8"?><item>Foo</item>',
                'configuration' => [],
                'result' => <<<XML
<?xml version="1.0"?>
<item>Foo</item>

XML
                ,
            ],
            'only xpath' => [
                'structure' => <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<item>
    <media>
      <images>
        <image name="xxl_1234_01.jpg" position="1" updatedDateTime="2025-02-20 15:44:57"/>
        <image name="xxl_1234_02.jpg" position="2" updatedDateTime="2025-02-20 15:44:57"/>
        <image name="xxl_1234_03.jpg" position="3" updatedDateTime="2025-02-20 15:44:58"/>
      </images>
    </media>
</item>
XML
                ,
                'configuration' => [
                    'xpath' => 'media/images/image',
                ],
                'result' => <<<XML
<?xml version="1.0"?>
<image name="xxl_1234_01.jpg" position="1" updatedDateTime="2025-02-20 15:44:57"/>
<image name="xxl_1234_02.jpg" position="2" updatedDateTime="2025-02-20 15:44:57"/>
<image name="xxl_1234_03.jpg" position="3" updatedDateTime="2025-02-20 15:44:58"/>

XML
                ,
            ],
            'field and xpath' => [
                'structure' => <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<item>
    <media>
      <images>
        <image name="xxl_1234_01.jpg" position="1" updatedDateTime="2025-02-20 15:44:57"/>
        <image name="xxl_1234_02.jpg" position="2" updatedDateTime="2025-02-20 15:44:57"/>
        <image name="xxl_1234_03.jpg" position="3" updatedDateTime="2025-02-20 15:44:58"/>
      </images>
    </media>
</item>
XML
                ,
                'configuration' => [
                    'field' => 'media',
                    'xpath' => 'images/image',
                ],
                'result' => <<<XML
<?xml version="1.0"?>
<image name="xxl_1234_01.jpg" position="1" updatedDateTime="2025-02-20 15:44:57"/>
<image name="xxl_1234_02.jpg" position="2" updatedDateTime="2025-02-20 15:44:57"/>
<image name="xxl_1234_03.jpg" position="3" updatedDateTime="2025-02-20 15:44:58"/>

XML
                ,
            ],
            'field and xpath with function' => [
                'structure' => <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<item>
    <media>
      <images>
        <image name="xxl_1234_01.jpg" position="1" updatedDateTime="2025-02-20 15:44:57"/>
        <image name="xxl_1234_02.jpg" position="2" updatedDateTime="2025-02-20 15:44:57"/>
        <image name="xxl_1234_03.jpg" position="3" updatedDateTime="2025-02-20 15:44:58"/>
      </images>
    </media>
</item>
XML
                ,
                'configuration' => [
                    'field' => 'media',
                    'xpath' => 'images/image[contains(@position, "2")]',
                ],
                'result' => <<<XML
<?xml version="1.0"?>
<image name="xxl_1234_02.jpg" position="2" updatedDateTime="2025-02-20 15:44:57"/>

XML
                ,
            ],
        ];
    }

    #[Test] #[DataProvider('getNodeListProvider')]
    public function getNodeListReturnsExpectedList(string $structure, array $configuration, string $result): void
    {
        $dom = new \DOMDocument();
        $dom->loadXML($structure, LIBXML_PARSEHUGE);
        $records = $dom->getElementsByTagName('item');
        $xPathObject = new \DOMXPath($dom);
        $nodeList = $this->subject->getNodeList($records->item(0), $configuration, $xPathObject);
        $resultingDocument = new \DOMDocument();
        // Test the result by writing the selected nodes to a new document
        foreach ($nodeList as $node) {
            $node = $resultingDocument->importNode($node, true);
            $resultingDocument->appendChild($node);
        }
        self::assertSame(
            $result,
            $resultingDocument->saveXML()
        );
    }
}
