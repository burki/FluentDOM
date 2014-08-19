<?php
/**
 * Allow an object to be appendable to a FluentDOM\Element
 *
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * @copyright Copyright (c) 2009-2014 Bastian Feder, Thomas Weinert
 */

namespace FluentDOM\Transformer\Namespaces {

  use FluentDOM\Attribute;
  use FluentDOM\Document;
  use FluentDOM\Element;

  class Optimize {

    /**
     * @var Document
     */
    private $_document = NULL;

    /**
     * @var array
     */
    private $_namespaceUris = [];

    /**
     * Create a namepsace optimizer for the provided document. The provided
     * document will be copied.
     *
     * The second argument allows to provide namespaces and prefixes. The
     * keys of the array are the namespace uri, the values are the prefixes.
     *
     * If a namespace is not provided it is read from the source node.
     *
     * You can use the same prefix for muiltiple namespace uris. Empty prefixes
     * are possible (default namespace for an element).
     *
     * It is highly recommed that you always use a non-empty prefix if the
     * here are attributes in that namespace. Attributes always need a prefix
     * to make use of the namespace.
     *
     * @param \DOMDocument $document
     * @param array $namespaces
     */
    public function __construct(\DOMDocument $document, array $namespaces = []) {
      $this->_document = new Document();
      foreach ($document->childNodes as $node) {
        $this->_document->appendChild(
          $this->_document->importNode($node, TRUE)
        );
      }
      $this->_namespaceUris = $namespaces;
    }

    /**
     * Create a document with opimized namespaces and return it as xml string
     *
     * @return string
     */
    public function __toString() {
      return $this->getDocument()->saveXml();
    }

    /**
     * Create and return a document with optimized namespaces.
     *
     * @return Document
     */
    public function getDocument() {
      $document = new Document();
      foreach ($this->_document->childNodes as $node) {
        $this->addNode($document, $node);
      }
      return $document;
    }

    /**
     * Add a node to the target element, just copies any child nodes
     * except elements. Element nodes are recreated with mapped/optimized
     * namespaces.
     *
     * @param \DOMNode $target
     * @param \DOMNode $source
     */
    private function addNode(\DOMNode $target, \DOMNode $source) {
      if ($source instanceof Element) {
        $this->addElement($target, $source);
      } else {
        $document = $target instanceof Document ? $target : $target->ownerDocument;
        $target->appendChild($document->importNode($source));
      }
    }

    /**
     * Add an element node to the target (document or element)
     *
     * Namespaces are mapped and added to the mote remote ancestor possible.
     *
     * @param \DOMNode $target
     * @param Element $source
     */
    private function addElement(\DOMNode $target, Element $source) {
      list($prefix, $name, $uri) = $this->getNodeDefinition($source);
      if ($target instanceof Element) {
        $this->addNamespaceAttribute($target, $prefix, $uri);
      }
      $document = $target instanceof Document ? $target : $target->ownerDocument;
      $newNodeName = empty($prefix) ? $name : $prefix.':'.$name;
      if (empty($uri) && NULL == $target->lookupNamespaceUri(NULL)) {
        $newNode = $document->createElement($newNodeName);
      } else {
        $newNode = $document->createElementNS((string)$uri, $newNodeName);
      }
      $target->appendChild($newNode);
      if ($source instanceof Element) {
        foreach ($source->attributes as $attribute) {
          $this->addAttribute($newNode, $attribute);
        }
      }
      foreach ($source->childNodes as $childNode) {
        $this->addNode($newNode, $childNode);
      }
    }

    /**
     * Add an attribute to the target element node.
     *
     * @param \DOMElement $target
     * @param Attribute $source
     */
    private function addAttribute(\DOMElement $target, Attribute $source) {
      list($prefix, $name, $uri) = $this->getNodeDefinition($source);
      if (empty($prefix)) {
        $target->setAttribute($name, $source->value);
      } else {
        $target->setAttributeNS(
          $uri, $prefix.':'.$name, $source->value
        );
      }
    }

    /**
     * Get the node name definition (prefix, namespace, local name) for
     * the target node
     *
     * @param \DOMNode $node
     * @return array
     */
    private function getNodeDefinition(\DOMNode $node) {
      $isElement = $node instanceof Element;
      $prefix = $isElement && $node->prefix == 'default'
        ? NULL : $node->prefix;
      $name = $node->localName;
      $uri = $node->namespaceURI;
      if (
        (
          ($isElement && isset($this->_namespaceUris[$uri])) ||
          !empty($this->_namespaceUris[$uri])
        ) &&
        $this->_namespaceUris[$uri] !== '#default'
      ) {
        $prefix = $this->_namespaceUris[$uri];
      }
      return [
        $prefix, $name, $uri
      ];
    }

    /**
     * @param Element $node
     * @param string|NULL $prefix
     * @param string $uri
     */
    private function addNamespaceAttribute(Element $node, $prefix, $uri) {
      if ($node->parentNode instanceof Element) {
        $this->addNamespaceAttribute($node->parentNode, $prefix, $uri);
      }
      $currentUri = $node->lookupNamespaceUri(
        empty($prefix) ? NULL : $prefix
      );
      if ($currentUri != $uri) {
        if (empty($prefix) && empty($currentUri)) {
          $node->setAttribute('xmlns', $uri);
        } elseif (!empty($prefix)) {
          $attributeName = 'xmlns:'.$prefix;
          if (!$node->hasAttribute($attributeName)) {
            $node->setAttribute($attributeName, $uri);
          }
        }
      }
    }
  }
}