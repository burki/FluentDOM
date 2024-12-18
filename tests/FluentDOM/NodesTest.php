<?php
/*
 * FluentDOM
 *
 * @link https://thomas.weinert.info/FluentDOM/
 * @copyright Copyright 2009-2021 FluentDOM Contributors
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 */

namespace FluentDOM {

  use FluentDOM\Loader\Result;
  use FluentDOM\DOM\Document;
  use FluentDOM\DOM\Xpath;
  use PHPUnit\Framework\MockObject\MockObject;

  require_once __DIR__.'/TestCase.php';

  class Nodes_TestProxy extends Nodes {
    public function matches(string $selector, ?\DOMNode $context = NULL): bool {
      return parent::matches($selector, $context);
    }
  }

  class NodesTest extends TestCase {

    public static $_fd;

    /**
     * @covers \FluentDOM\Nodes::__construct
     */
    public function testConstructorWithXml(): void {
      $document = new \DOMDocument();
      $document->appendChild($document->createElement('xml'));
      $fd = new Nodes($document);
      $this->assertEquals(
        '<?xml version="1.0"?>'."\n".'<xml/>'."\n",
        (string)$fd
      );
    }

    /**
     * @covers \FluentDOM\Nodes::__construct
     */
    public function testConstructorWithHtml(): void {
      $document = new \DOMDocument();
      $document->appendChild($document->createElement('html'));
      $fd = new Nodes($document, 'html');
      $this->assertEquals(
        '<html></html>'."\n",
        (string)$fd
      );
    }

    /**
     * @covers \FluentDOM\Nodes::__construct
     */
    public function testConstructorWithHtmlFragment(): void {
      $fd = new Nodes('<label>Test</label><input>', 'html-fragment');
      $this->assertEquals(
        '<label>Test</label><input>',
        (string)$fd
      );
    }

    /**
     * @covers \FluentDOM\Nodes::__construct
     */
    public function testConstructorWithoutSourceButWithContentType(): void {
      $fd = new Nodes(NULL, 'text/html');
      $this->assertEquals(
        'text/html',
        $fd->contentType
      );
    }

    /**
     * @group Load
     * @covers \FluentDOM\Nodes::load
     * @covers \FluentDOM\Nodes::prepareSource
     */
    public function testLoadWithNodes(): void {
      $fdOne = new Nodes();
      $fdTwo = new Nodes();
      $fdTwo->load($fdOne);
      $this->assertSame($fdOne->document, $fdTwo->document);
    }

    /**
     * @group Load
     * @covers \FluentDOM\Nodes::load
     * @covers \FluentDOM\Nodes::prepareSource
     */
    public function testLoadWithDocument(): void {
      $fd = new Nodes();
      $fd->load($document = new \DOMDocument());
      $this->assertSame(
        $document,
        $fd->document
      );
    }

    /**
     * @group Load
     * @covers \FluentDOM\Nodes::load
     * @covers \FluentDOM\Nodes::prepareSource
     */
    public function testLoadWithDomNode(): void {
      $document = new \DOMDocument();
      $document->appendChild($document->createElement('test'));
      $fd = new Nodes();
      $fd->load($document->documentElement);
      $this->assertSame($document, $fd->document);
      $this->assertCount(1, $fd);
    }

    /**
     * @group Load
     * @covers \FluentDOM\Nodes::load
     * @covers \FluentDOM\Nodes::prepareSource
     */
    public function testLoadWithCustomLoader(): void {
      $result = $this->createMock(Result::class);
      $result
        ->method('getDocument')
        ->willReturn($document = new Document());
      $fd = $this->createLoaderMock($result);
      $this->assertSame(
        $document,
        $fd->document
      );
    }

    /**
     * @group Load
     * @covers \FluentDOM\Nodes::load
     * @covers \FluentDOM\Nodes::prepareSource
     * @covers \FluentDOM\Nodes::getLoadingOptions
     */
    public function testLoadWithCustomLoaderAndOptions(): void {
      $result = $this->createMock(Result::class);
      $result
        ->method('getDocument')
        ->willReturn($document = new Document());
      $loader = $this->createMock(Loadable::class);
      $loader
        ->expects($this->once())
        ->method('supports')
        ->with('text/xml')
        ->willReturn(TRUE);
      $loader
        ->expects($this->once())
        ->method('load')
        ->with('DATA', 'text/xml', ['foo' => 'bar'])
        ->willReturn($result);
      $fd = new Nodes();
      $fd->loaders($loader);
      $fd->load('DATA', 'text/xml', ['foo' => 'bar']);
      $this->assertSame(
        $document, $fd->document
      );
      $this->assertEquals(['foo' => 'bar'], $fd->getLoadingOptions('text/xml'));
    }

    /**
     * @group Load
     * @covers \FluentDOM\Nodes::load
     * @covers \FluentDOM\Nodes::prepareSource
     */
    public function testLoadWithCustomLoaderReturningLoaderResult(): void {
      $document = new Document();
      $document->appendElement('dummy');
      $result = $this->createLoaderResultMock($document);
      $fd = $this->createLoaderMock($result);
      $this->assertSame(
        $document,
        $fd->document
      );
      $this->assertCount(
        0, $fd
      );
    }

    /**
     * @group Load
     * @covers \FluentDOM\Nodes::load
     * @covers \FluentDOM\Nodes::prepareSource
     */
    public function testLoadWithCustomLoaderReturningLoaderResultWithSelection(): void {
      $document = new Document();
      $document->appendElement('dummy');
      $result = $this->createLoaderResultMock($document, $document->documentElement);
      $fd = $this->createLoaderMock($result);
      $this->assertSame(
        $document,
        $fd->document
      );
      $this->assertSame(
        $document->documentElement,
        $fd[0]
      );
    }

    /**
     * @group Load
     * @covers \FluentDOM\Nodes::load
     * @covers \FluentDOM\Nodes::prepareSource
     * @covers \FluentDOM\Nodes::setContentType
     */
    public function testLoadWithUnknownContentType(): void {
      $document = new Document();
      $fd = new Nodes();
      $fd->load($document, 'unknown');
      $this->assertEquals('unknown', $fd->contentType);
    }

    /**
     * @group Load
     * @covers \FluentDOM\Nodes::load
     * @covers \FluentDOM\Nodes::prepareSource
     * @covers \FluentDOM\Nodes::setContentType
     */
    public function testLoadWithInvalidSourceExpectingException(): void {
      $fd = new Nodes();
      $this->expectException(Exceptions\InvalidSource\Variable::class);
      $fd->load(NULL, 'text');
    }

    /**
     * @group Load
     * @covers \FluentDOM\Nodes::loaders
     */
    public function testLoadersGetAfterSet(): void {
      $loader = $this->createMock(Loadable::class);
      $fd = new Nodes();
      $fd->loaders($loader);
      $this->assertSame($loader, $fd->loaders());
    }

    /**
     * @group Load
     * @covers \FluentDOM\Nodes::loaders
     */
    public function testLoadersGetImplicitCreate(): void {
      $fd = new Nodes();
      $this->assertInstanceOf(Loaders::class, $fd->loaders());
    }

    /**
     * @group Load
     * @covers \FluentDOM\Nodes::loaders
     */
    public function testLoadersGetCreateFromArray(): void {
      $fd = new Nodes();
      $fd->loaders([$loader = $this->createMock(Loadable::class)]);
      /** @var Loaders $loaders */
      $loaders = $fd->loaders();
      $this->assertSame([$loader], iterator_to_array($loaders));
    }

    /**
     * @group Load
     * @covers \FluentDOM\Nodes::loaders
     */
    public function testLoadersGetWithInvalidLoaderExpectingException(): void {
      $fd = new Nodes();
      $this->expectException(\InvalidArgumentException::class);
      $fd->loaders('FOO');
    }

    /**
     * @group Load
     * @covers \FluentDOM\Nodes::push
     */
    public function testPushWithNode(): void {
      $fd = new Nodes();
      $fd->document->appendElement('test');
      $fd->push($fd->document->documentElement);
      $this->assertCount(1, $fd);
    }

    /**
     * @group Load
     * @covers \FluentDOM\Nodes::push
     */
    public function testPushWithTraversable(): void {
      $fd = new Nodes();
      $fd->document->appendElement('test');
      $fd->push([$fd->document->documentElement]);
      $this->assertCount(1, $fd);
    }

    /**
     * @group Load
     * @covers \FluentDOM\Nodes::push
     */
    public function testPushWithInvalidArgumentExpectingException(): void {
      $fd = new Nodes();
      $this->expectException(\InvalidArgumentException::class);
      /** @noinspection PhpParamsInspection */
      $fd->push(FALSE);
    }

    /**
     * @group Load
     * @covers \FluentDOM\Nodes::push
     */
    public function testPushNodeFromDifferentDocumentExpectingException(): void {
      $document = new Document();
      $document->appendElement('test');
      $fd = new Nodes();
      $this->expectException(\OutOfBoundsException::class);
      $fd->push($document->documentElement);
    }

    /**
     * @group Load
     * @covers \FluentDOM\Nodes::push
     */
    public function testPushListWithNodesFromDifferentDocumentExpectingException(): void {
      $document = new Document();
      $document->appendElement('test');
      $fd = new Nodes();
      $this->expectException(\OutOfBoundsException::class);
      $fd->push([$document->documentElement]);
    }

    /**
     * @group CoreFunctions
     * @covers \FluentDOM\Nodes::formatOutput
     */
    public function testFormatOutput(): void {
      $fd = new Nodes();
      $fd->document->loadXML('<html><body><br/></body></html>');
      $fd->formatOutput();
      $expected =
        "<?xml version=\"1.0\"?>\n".
        "<html>\n".
        "  <body>\n".
        "    <br/>\n".
        "  </body>\n".
        "</html>\n";
      $this->assertEquals('text/xml', $fd->contentType);
      $this->assertSame($expected, (string)$fd);
    }

    /**
     * @group CoreFunctions
     * @covers \FluentDOM\Nodes::formatOutput
     */
    public function testFormatOutputWithEmptyDocument(): void {
      $fd = new Nodes();
      $fd->formatOutput();
      $this->assertEquals('text/xml', $fd->contentType);
    }

    /**
     * @group CoreFunctions
     * @covers \FluentDOM\Nodes::formatOutput
     */
    public function testFormatOutputWithContentTypeHtml(): void {
      $fd = new Nodes();
      $fd->document->loadXML('<html><body><br/></body></html>');
      $fd->formatOutput('text/html');
      $expected = "<html><body><br></body></html>\n";
      $this->assertEquals('text/html', $fd->contentType);
      $this->assertSame($expected, (string)$fd);
    }

    /**
     * @group CoreFunctions
     * @covers \FluentDOM\Nodes::item
     */
    public function testItem(): void {
      $fd = new Nodes();
      $fd = $fd->find('/*');
      $this->assertEquals($fd->document->documentElement, $fd->item(0));
    }

    /**
     * @group CoreFunctions
     * @covers \FluentDOM\Nodes::item
     */
    public function testItemExpectingNull(): void {
      $fd = new Nodes();
      $this->assertNull($fd->item(0));
    }

    /**
     * @group CoreFunctions
     * @covers \FluentDOM\Nodes::spawn
     */
    public function testSpawn(): void {
      $fdParent = new Nodes;
      $fdChild = $fdParent->spawn();
      $this->assertSame(
        $fdParent,
        $fdChild->end()
      );
    }

    /**
     * @group CoreFunctions
     * @covers \FluentDOM\Nodes::spawn
     */
    public function testSpawnWithElements(): void {
      $document = new \DOMDocument;
      $node = $document->createElement('test');
      $document->appendChild($node);
      $fdParent = new Nodes();
      $fdParent->load($document);
      $fdChild = $fdParent->spawn($node);
      $this->assertSame(
        [$node],
        iterator_to_array($fdChild)
      );
    }

    /**
     * @group CoreFunctions
     * @covers \FluentDOM\Nodes::unique
     */
    public function testUnique(): void {
      $fd = new Nodes();
      $fd->document->appendElement('test');
      $nodes = $fd->unique(
        [$fd->document->documentElement, $fd->document->documentElement]
      );
      $this->assertCount(1, $nodes);
    }

    /**
     * @group CoreFunctions
     * @covers \FluentDOM\Nodes::unique
     */
    public function testUniqueWithASingleNode(): void {
      $fd = new Nodes();
      $fd->document->appendElement('test');
      $nodes = $fd->unique(
        [$fd->document->documentElement]
      );
      $this->assertCount(1, $nodes);
    }

    /**
     * @group CoreFunctions
     * @covers \FluentDOM\Nodes::unique
     */
    public function testUniqueWithUnattachedNodes(): void {
      $fd = new Nodes();
      $node = $fd->document->createElement("test");
      $nodes = $fd->unique([$node, $node]);
      $this->assertCount(1, $nodes);
    }

    /**
     * @group CoreFunctions
     * @covers \FluentDOM\Nodes::unique
     */
    public function testUniqueWithInvalidElementInList(): void {
      $fd = new Nodes();
      $this->expectException(\InvalidArgumentException::class);
      /** @noinspection PhpParamsInspection */
      $fd->unique(['Invalid']);
    }

    /**
     * @group CoreFunctions
     * @covers \FluentDOM\Nodes::matches
     * @covers \FluentDOM\Nodes::prepareSelector
     */
    public function testMatchesWithNodeListExpectingTrue(): void {
      $fd = new Nodes_TestProxy(self::XML);
      $this->assertTrue($fd->matches('/*'));
    }

    /**
     * @group CoreFunctions
     * @covers \FluentDOM\Nodes::matches
     * @covers \FluentDOM\Nodes::prepareSelector
     */
    public function testMatchesWithSelectorCallbackExpectingTrue(): void {
      $fd = new Nodes_TestProxy(self::XML);
      $fd->onPrepareSelector = function($selector) { return '/'.$selector; };
      $this->assertTrue($fd->matches('*'));
    }

    /**
     * @group CoreFunctions
     * @covers \FluentDOM\Nodes::matches
     * @covers \FluentDOM\Nodes::prepareSelector
     */
    public function testMatchesWithNodeListExpectingFalse(): void {
      $fd = new Nodes_TestProxy(self::XML);
      $this->assertFalse($fd->matches('invalid'));
    }

    /**
     * @group CoreFunctions
     * @covers \FluentDOM\Nodes::matches
     * @covers \FluentDOM\Nodes::prepareSelector
     */
    public function testMatchesWithScalarExpectingTrue(): void {
      $fd = new Nodes_TestProxy(self::XML);
      $this->assertTrue(
        $fd->matches('count(/items)')
      );
    }

    /**
     * @group CoreFunctions
     * @covers \FluentDOM\Nodes::matches
     * @covers \FluentDOM\Nodes::prepareSelector
     */
    public function testMatchesWithScalarAndContextExpectingTrue(): void {
      $fd = new Nodes_TestProxy(self::XML);
      $this->assertTrue(
        $fd->matches(
          'count(item)',
          $fd->xpath()->evaluate('//group')->item(0)
        )
      );
    }

    /**
     * @group CoreFunctions
     * @covers \FluentDOM\Nodes::matches
     * @covers \FluentDOM\Nodes::prepareSelector
     */
    public function testMatchesWithScalarExpectingFalse(): void {
      $fd = new Nodes_TestProxy(self::XML);
      $this->assertFalse(
        $fd->matches('count(item)')
      );
    }

    /**
     * @group CoreFunctions
     * @covers \FluentDOM\Nodes::matches
     * @covers \FluentDOM\Nodes::prepareSelector
     */
    public function testMatchesWithPreparedSelectorExpectingTrue(): void {
      $fd = new Nodes_TestProxy(self::XML);
      $fd->onPrepareSelector = function($selector) {
        return 'count(//group[1]'.$selector.')';
      };
      $this->assertTrue(
        $fd->matches('/item')
      );
    }

    /**
     * @group CoreFunctions
     * @covers \FluentDOM\Nodes::matches
     * @covers \FluentDOM\Nodes::prepareSelector
     */
    public function testMatchesWithPreparedSelectorExpectingFalse(): void {
      $fd = new Nodes_TestProxy(self::XML);
      $fd->onPrepareSelector = function($selector) {
        return 'count('.$selector.')';
      };
      $this->assertFalse(
        $fd->matches('/item')
      );
    }

    /**
     * @group CoreFunctions
     * @covers \FluentDOM\Nodes::__get
     * @covers \FluentDOM\Nodes::__set
     */
    public function testGetAfterSetOnPrepareSelector(): void {
      $fd = new Nodes();
      $fd->onPrepareSelector = $callback = static function() {};
      $this->assertSame($callback, $fd->onPrepareSelector);
      $spawn = $fd->spawn();
      $this->assertSame($callback, $spawn->onPrepareSelector);
    }

    /**
     * @group CoreFunctions
     * @covers \FluentDOM\Nodes::__set
     */
    public function testSetOnPrepareSelectorExpectingException(): void {
      $fd = new Nodes();
      $this->expectException(\InvalidArgumentException::class);
      $fd->onPrepareSelector = FALSE;
    }

    /**
     * @group Interfaces
     * @group IteratorAggregate
     * @covers \FluentDOM\Nodes::getIterator
     */
    public function testIterator(): void {
      $fd = new Nodes(self::XML);
      $fd = $fd->find('//item');
      $this->assertCount(3, $fd);
    }

    /**
     * @group Interfaces
     * @group ArrayAccess
     * @covers \FluentDOM\Nodes::offsetExists
     *
     */
    public function testOffsetExistsExpectingTrue(): void {
      $fd = new Nodes(self::XML);
      $fd = $fd->find('//item');
      $this->assertTrue(isset($fd[1]));
    }

    /**
     * @group Interfaces
     * @group ArrayAccess
     * @covers \FluentDOM\Nodes::offsetExists
     *
     */
    public function testOffsetExistsExpectingFalse(): void {
      $fd = new Nodes();
      $fd = $fd->find('//item');
      $this->assertFalse(isset($fd[99]));
    }

    /**
     * @group Interfaces
     * @group ArrayAccess
     * @covers \FluentDOM\Nodes::offsetGet
     */
    public function testOffsetGet(): void {
      $fd = new Nodes(self::XML);
      $fd = $fd->find('//item');
      $this->assertEquals('text2', $fd[1]->nodeValue);
    }

    /**
     * @group Interfaces
     * @group ArrayAccess
     * @covers \FluentDOM\Nodes::offsetSet
     */
    public function testOffsetSetExpectingException(): void {
      $fd = new Nodes(self::XML);
      $fd = $fd->find('//item');
      $this->expectException(\BadMethodCallException::class);
      $fd[2] = '123';
    }

    /**
     * @group Interfaces
     * @group ArrayAccess
     * @covers \FluentDOM\Nodes::offsetUnset
     */
    public function testOffsetUnsetExpectingException(): void {
      $fd = new Nodes();
      $fd = $fd->find('//item');
      $this->expectException(\BadMethodCallException::class);
      unset($fd[2]);
    }

    /**
     * @group Interfaces
     * @group Countable
     * @covers \FluentDOM\Nodes::count
     */
    public function testInterfaceCountableExpecting3(): void {
      $fd = new Nodes(self::XML);
      $fd = $fd->find('//item');
      $this->assertCount(3, $fd);
    }

    /**
     * @group Interfaces
     * @group Countable
     * @covers \FluentDOM\Nodes::count
     */
    public function testInterfaceCountableExpectingZero(): void {
      $fd = new Nodes(self::XML);
      $this->assertCount(0, $fd);
    }

    /**
     * @group Properties
     * @covers \FluentDOM\Nodes::__isset
     * @covers \FluentDOM\Nodes::__get
     * @covers \FluentDOM\Nodes::__set
     * @covers \FluentDOM\Nodes::__unset
     */
    public function testDynamicProperty(): void {
      $fd = new Nodes();
      $this->assertEquals(FALSE, isset($fd->dynamicProperty));
      $this->assertEquals(NULL, $fd->dynamicProperty);
      $fd->dynamicProperty = 'test';
      $this->assertEquals(TRUE, isset($fd->dynamicProperty));
      $this->assertEquals('test', $fd->dynamicProperty);
      unset($fd->dynamicProperty);
    }

    /**
     * @group Properties
     * @covers \FluentDOM\Nodes::__unset
     */
    public function testDynamicPropertyUnsetOnNonExistingPropertyExpectingException(): void {
      $fd = new Nodes();
      $this->expectException(\BadMethodCallException::class);
      unset($fd->dynamicProperty);
    }

    /**
     * @covers \FluentDOM\Nodes::__set
     */
    public function testSetPropertyXpath(): void {
      $fd = new Nodes(self::XML);
      $this->expectException(\BadMethodCallException::class);
      $fd->xpath = $fd->xpath();
    }

    /**
     * @group CoreFunctions
     * @covers \FluentDOM\Nodes::xpath
     */
    public function testUseXpathToCallEvaluate(): void {
      $fd = new Nodes_TestProxy(self::XML);
      $this->assertEquals(
        3,
        $fd->xpath('count(//item)')
      );
    }

    /**
     * @group Properties
     * @covers \FluentDOM\Nodes::__isset
     */
    public function testIssetPropertyLength(): void {
      $fd = new Nodes();
      $this->assertTrue(isset($fd->length));
    }

    /**
     * @group Properties
     * @covers \FluentDOM\Nodes::__get
     */
    public function testGetPropertyLength(): void {
      $fd = new Nodes(self::XML);
      $fd = $fd->find('//item');
      $this->assertEquals(3, $fd->length);
    }

    /**
     * @group Properties
     * @covers \FluentDOM\Nodes::__set
     */
    public function testSetPropertyLength(): void {
      $fd = new Nodes();
      $this->expectException(\BadMethodCallException::class);
      $fd->length = 50;
    }

    /**
     * @group Properties
     * @covers \FluentDOM\Nodes::__unset
     */
    public function testUnsetPropertyLength(): void {
      $fd = new Nodes;
      $this->expectException(\BadMethodCallException::class);
      unset($fd->length);
    }

    /**
     * @group Properties
     * @covers \FluentDOM\Nodes::__isset
     */
    public function testIssetPropertyDocumentExpectingFalse(): void {
      $fd = new Nodes();
      $this->assertFalse(isset($fd->document));
    }

    /**
     * @group Properties
     * @covers \FluentDOM\Nodes::__isset
     */
    public function testIssetPropertyDocumentExpectingTrue(): void {
      $fd = new Nodes();
      /** @noinspection PhpExpressionResultUnusedInspection */
      $fd->document;
      $this->assertTrue(isset($fd->document));
    }

    /**
     * @group Properties
     * @covers \FluentDOM\Nodes::__get
     * @covers \FluentDOM\Nodes::getDocument
     */
    public function testGetPropertyDocumentImplicitCreate(): void {
      $fd = new Nodes;
      $document = $fd->document;
      $this->assertInstanceOf(Document::class, $document);
      $this->assertSame($document, $fd->document);
    }

    /**
     * @group Properties
     * @covers \FluentDOM\Nodes::__isset
     */
    public function testIssetPropertyContentType(): void {
      $fd = new Nodes();
      $this->assertTrue(isset($fd->contentType));
    }

    /**
     * @group Properties
     * @covers \FluentDOM\Nodes::__get
     */
    public function testGetPropertyContentType(): void {
      $fd = new Nodes();
      $this->assertEquals('text/xml', $fd->contentType);
    }

    /**
     * @group Properties
     * @covers \FluentDOM\Nodes::__set
     * @covers \FluentDOM\Nodes::setContentType
     * @dataProvider getContentTypeSamples
     * @param string $contentType
     * @param string $expected
     */
    public function testSetPropertyContentType(string $contentType, string $expected): void {
      $fd = new Nodes();
      $fd->contentType = $contentType;
      $this->assertEquals($expected, $fd->contentType);
    }

    public function getContentTypeSamples(): array {
      return [
        ['text/xml', 'text/xml'],
        ['text/html', 'text/html'],
        ['xml', 'text/xml'],
        ['html', 'text/html'],
        ['TEXT/XML', 'text/xml'],
        ['TEXT/HTML', 'text/html'],
        ['XML', 'text/xml'],
        ['HTML', 'text/html']
      ];
    }

    /**
     * @group Properties
     * @covers \FluentDOM\Nodes::__set
     * @covers \FluentDOM\Nodes::setContentType
     */
    public function testSetPropertyContentTypeChaining(): void {
      $fdParent = new Nodes();
      $fdChild = $fdParent->spawn();
      $fdChild->contentType = 'text/html';
      $this->assertEquals(
        'text/html',
        $fdParent->contentType
      );
    }

    /**
     * @group Properties
     * @covers \FluentDOM\Nodes::__get
     */
    public function testGetPropertyXpath(): void {
      $fd = new Nodes();
      $this->assertInstanceOf(Xpath::class, $fd->xpath);
    }

    /**
     * @group MagicFunctions
     * @group StringCastable
     * @covers \FluentDOM\Nodes::toString
     * @covers \FluentDOM\Nodes::__toString
     */
    public function testMagicToString(): void {
      $fd = new Nodes(self::XML);
      $this->assertEquals($fd->document->saveXML(), (string)$fd);
    }

    /**
     * @group MagicFunctions
     * @group StringCastable
     * @covers \FluentDOM\Nodes::toString
     * @covers \FluentDOM\Nodes::__toString
     */
    public function testMagicToStringWithExceptionInSerializerFactory(): void {
      $factory = $this->createMock(Serializer\Factory\Group::class);
      $factory
        ->expects($this->once())
        ->method('createSerializer')
        ->willThrowException(new \LogicException);

      $fd = new Nodes(self::XML);
      $fd->serializerFactories($factory);
      $this->assertEquals('', (string)$fd);
    }

    /**
     * @group MagicFunctions
     * @group StringCastable
     * @covers \FluentDOM\Nodes::toString
     * @covers \FluentDOM\Nodes::__toString
     */
    public function testMagicToStringWithSerializerFactoryReturningNull(): void {
      $factory = $this->createMock(Serializer\Factory\Group::class);
      $factory
        ->expects($this->once())
        ->method('createSerializer')
        ->willReturn(NULL);

      $fd = new Nodes(self::XML);
      $fd->serializerFactories($factory);
      $this->assertEquals('', (string)$fd);
    }

    /**
     * @group MagicFunctions
     * @group StringCastable
     * @covers \FluentDOM\Nodes::toString
     * @covers \FluentDOM\Nodes::__toString
     */
    public function testStringWithSerializerFactoryExpectingException(): void {
      $factory = $this->createMock(Serializer\Factory\Group::class);
      $factory
        ->expects($this->once())
        ->method('createSerializer')
        ->willReturn(NULL);

      $fd = new Nodes(self::XML);
      $fd->serializerFactories($factory);
      $this->expectException(Exceptions\NoSerializer::class);
      $fd->toString();
    }

    /**
     * @group MagicFunctions
     * @group StringCastable
     * @covers \FluentDOM\Nodes::__toString
     */
    public function testMagicToStringHtml(): void {
      $document = new \DOMDocument();
      $document->loadHTML(self::HTML);
      $fd = new Nodes();
      $fd = $fd->load($document);
      $fd->contentType = 'html';
      $this->assertEquals($document->saveHTML(), (string)$fd);
    }



    /**
     * @group Core
     * @covers \FluentDOM\Nodes::xpath()
     * @covers \FluentDOM\Nodes::getXpath()
     */
    public function testXpathGetFromDocument(): void {
      $document = new Document();
      $fd = new Nodes();
      $fd = $fd->load($document);
      $this->assertSame(
        $document->xpath(), $fd->xpath()
      );
    }

    /**
     * @group Core
     * @covers \FluentDOM\Nodes::xpath()
     * @covers \FluentDOM\Nodes::getXpath()
     */
    public function testXpathGetImplicitCreate(): void {
      $document = new \DOMDocument();
      $fd = new Nodes();
      $fd = $fd->load($document);
      $xpath = $fd->xpath();
      $this->assertSame(
        $xpath, $fd->xpath()
      );
    }

    /**
     * @group Core
     * @covers \FluentDOM\Nodes::xpath()
     * @covers \FluentDOM\Nodes::getXpath()
     * @covers \FluentDOM\Nodes::registerNamespace()
     * @covers \FluentDOM\Nodes::applyNamespaces
     */
    public function testXpathGetImplicitCreateWithNamespace(): void {
      $document = new \DOMDocument();
      $fd = new Nodes();
      $fd = $fd->load($document);
      $fd->registerNamespace('foo', 'urn:foo');
      $xpath = $fd->xpath();
      $this->assertSame(
        $xpath, $fd->xpath()
      );
    }

    /**
     * @group Core
     * @covers \FluentDOM\Nodes::registerNamespace
     * @covers \FluentDOM\Nodes::applyNamespaces
     */
    public function testRegisterNamespaceBeforeLoad(): void {
      $fd = new Nodes();
      $fd->registerNamespace('f', 'urn:foo');
      $fd->load('<foo:foo xmlns:foo="urn:foo"/>');
      $this->assertEquals(1, $fd->xpath()->evaluate('count(/f:foo)'));
    }

    /**
     * @group Core
     * @covers \FluentDOM\Nodes::registerNamespace
     * @covers \FluentDOM\Nodes::applyNamespaces
     */
    public function testRegisterNamespaceAfterLoad(): void {
      $fd = new Nodes();
      $fd->load('<foo:foo xmlns:foo="urn:foo"/>', 'text/xml');
      $fd->registerNamespace('f', 'urn:foo');
      $this->assertEquals(1, $fd->xpath()->evaluate('count(/f:foo)'));
    }

    /**
     * @group Core
     * @covers \FluentDOM\Nodes::registerNamespace
     * @covers \FluentDOM\Nodes::applyNamespaces
     */
    public function testRegisterNamespaceAfterLoadOnCreatedXpath(): void {
      $document = new \DOMDocument();
      $document->loadXML('<foo:foo xmlns:foo="urn:foo"/>');
      $fd = new Nodes();
      $fd->load($document, 'text/xml');
      $fd->xpath();
      $fd->registerNamespace('f', 'urn:foo');
      $this->assertEquals(1, $fd->xpath()->evaluate('count(/f:foo)'));
    }

    /**
     * @covers \FluentDOM\Nodes::getSelectorCallback
     */
    public function testGetSelectorCallbackWithNullExpectingNull(): void {
      $fd = new Nodes(self::XML);
      $this->assertNull(
        $fd->getSelectorCallback(NULL)
      );
    }

    /**
     * @covers \FluentDOM\Nodes::getSelectorCallback
     */
    public function testGetSelectorCallbackWithStringExpectingTrue(): void {
      $fd = new Nodes(self::XML);
      $this->assertInstanceOf(
        'Closure',
        $callback = $fd->getSelectorCallback('name() = "items"')
      );
      $this->assertTrue(
        $callback($fd->document->documentElement)
      );
    }

    /**
     * @covers \FluentDOM\Nodes::getSelectorCallback
     */
    public function testGetSelectorCallbackWithNodeExpectingTrue(): void {
      $fd = new Nodes(self::XML);
      $this->assertInstanceOf(
        'Closure',
        $callback = $fd->getSelectorCallback($fd->document->documentElement)
      );
      $this->assertTrue(
        $callback($fd->document->documentElement)
      );
    }

    /**
     * @covers \FluentDOM\Nodes::getSelectorCallback
     */
    public function testGetSelectorCallbackWithNodeArrayExpectingTrue(): void {
      $fd = new Nodes(self::XML);
      $this->assertInstanceOf(
        'Closure',
        $callback = $fd->getSelectorCallback(
          [$fd->document->documentElement]
        )
      );
      $this->assertTrue(
        $callback($fd->document->documentElement)
      );
    }

    /**
     * @covers \FluentDOM\Nodes::getSelectorCallback
     */
    public function testGetSelectorCallbackWithCallableExpectingTrue(): void {
      $fd = new Nodes(self::XML);
      $this->assertInstanceOf(
        'Closure',
        $callback = $fd->getSelectorCallback(
          function (\DOMNode $node) {
            return $node instanceof \DOMElement;
          }
        )
      );
      $this->assertTrue(
        $callback($fd->document->documentElement)
      );
    }

    /**
     * @covers \FluentDOM\Nodes::getSelectorCallback
     */
    public function testGetSelectorCallbackWithEmptyArrayExpectingFalse(): void {
      $fd = new Nodes(self::XML);
      $this->assertInstanceOf(
        'Closure',
        $callback = $fd->getSelectorCallback(
          []
        )
      );
      $this->assertFalse(
        $callback($fd->document->documentElement)
      );
    }

    /**
     * @covers \FluentDOM\Nodes::getSelectorCallback
     */
    public function testGetSelectorCallbackWithInvalidSelectorExpectingException(): void {
      $fd = new Nodes(self::XML);
      $this->expectException(\InvalidArgumentException::class);
      $this->expectErrorMessage('Invalid selector argument.');
      $fd->getSelectorCallback('');
    }

    /**
     * @covers \FluentDOM\Nodes::serializerFactories
     */
    public function testGetSerializerFactoriesAfterSet(): void {
      $factory = $this->createMock(Serializer\Factory\Group::class);
      $fd = new Nodes();
      $this->assertSame(
        $factory, $fd->serializerFactories($factory)
      );
    }

    /**
     * @covers \FluentDOM\Nodes::serializerFactories
     */
    public function testGetSerializerFactoriesInitializesFromStaticClass(): void {
      $fd = new Nodes();
      $this->assertSame(
        \FluentDOM::getSerializerFactories(), $fd->serializerFactories()
      );
    }

    /**
     * @param Document $document
     * @param \DOMNode|NULL $selection
     * @return Result|MockObject
     */
    public function createLoaderResultMock(Document $document, ?\DOMNode $selection = NULL) {
      $result = $this
        ->getMockBuilder(Loader\Result::class)
        ->disableOriginalConstructor()
        ->getMock();
      $result
        ->expects($this->once())
        ->method('getDocument')
        ->willReturn($document);
      $result
        ->expects($this->once())
        ->method('getSelection')
        ->willReturn($selection ?: FALSE);
      $result
        ->expects($this->once())
        ->method('getContentType')
        ->willReturn('text/xml');
      return $result;
    }

    /**
     * @param $result
     * @return Nodes
     */
    public function createLoaderMock($result): Nodes {
      $loader = $this->createMock(Loadable::class);
      $loader
        ->expects($this->once())
        ->method('supports')
        ->with('text/xml')
        ->willReturn(TRUE);
      $loader
        ->expects($this->once())
        ->method('load')
        ->with('DATA', 'text/xml')
        ->willReturn($result);
      $fd = new Nodes();
      $fd->loaders($loader);
      $fd->load('DATA');
      return $fd;
    }
  }
}
