<?php
/**
* FluentDOMCore implements the core and interface functions for FluentDOM
*
* @version $Id: FluentDOM.php 372 2010-01-18 06:48:24Z subjective $
* @license http://www.opensource.org/licenses/mit-license.php The MIT License
* @copyright Copyright (c) 2009 Bastian Feder, Thomas Weinert
*
* @tutorial FluentDOM.pkg
* @package FluentDOM
*/

/**
* Include the external iterator class.
*/
require_once(dirname(__FILE__).'/Iterator.php');
/**
* Include the loader interface.
*/
require_once(dirname(__FILE__).'/Loader.php');

/**
* FluentDOMCore implements the core and interface functions for FluentDOM
*
* @property string $contentType Output type - text/xml or text/html
* @property-read integer $length The amount of elements found by selector.
* @property-read DOMDocument $document Internal DOMDocument object
* @property-read DOMXPath $xpath Internal XPath object
*
* @package FluentDOM
*/
class FluentDOMCore implements IteratorAggregate, Countable, ArrayAccess {

  /**
  * Associated DOMDocument object.
  * @var DOMDocument
  * @access protected
  */
  protected $_document = NULL;

  /**
  * XPath object used to execute selectors
  * @var DOMXPath
  * @access protected
  */
  protected $_xpath = NULL;

  /**
  * Use document context for expression (not selected nodes).
  * @var boolean
  * @access protected
  */
  protected $_useDocumentContext = TRUE;

  /**
  * Content type for output (xml, text/xml, html, text/html).
  * @var string
  * @access protected
  */
  protected $_contentType = 'text/xml';

  /**
  * Parent FluentDOM object (previous selection in chain).
  * @var FluentDOM
  * @access protected
  */
  protected $_parent = NULL;

  /**
  * Seleted element and text nodes
  * @var array
  * @access protected
  */
  protected $_array = array();

  /**
  * Document loader list.
  *
  * @see _initLoaders
  * @see _setLoader
  *
  * @var array
  * @access protected
  */
  protected $_loaders = NULL;

  /**
  * Constructor
  *
  * @access public
  * @return FluentDOM
  */
  public function __construct() {
    $this->_document = new DOMDocument();
  }

  /**
  * Load a $source. The type of the source depends on the loaders. If no explicit loaders are set
  * FluentDOM will use a set of default loaders for xml/html and DOM.
  *
  * @param mixed $source
  * @param string $contentType optional, default value 'text/xml'
  * @access public
  */
  public function load($source, $contentType = 'text/xml') {
    $this->_array = array();
    $this->_setContentType($contentType);
    if ($source instanceof FluentDOMCore) {
      $this->_useDocumentContext = FALSE;
      $this->_document = $source->document;
      $this->_xpath = $source->_xpath;
      $this->_contentType = $source->_contentType;
      $this->_parent = $source;
      return $this;
    } else {
      $this->_parent = NULL;
      $this->_initLoaders();
      foreach ($this->_loaders as $loader) {
        if ($loaded = $loader->load($source, $this->_contentType)) {
          if ($loaded instanceof DOMDocument) {
            $this->_useDocumentContext = TRUE;
            $this->_document = $loaded;
          } elseif (is_array($loaded) &&
                    isset($loaded[0]) &&
                    isset($loaded[1]) &&
                    $loaded[0] instanceof DOMDocument &&
                    is_array($loaded[1])) {
            $this->_document = $loaded[0];
            $this->push($loaded[1]);
            $this->_useDocumentContext = FALSE;
          }
          return $this;
        }
      }
      throw new InvalidArgumentException('Invalid source object.');
    }
    return $this;
  }

  /**
  * Initialize default loaders if they are not already initialized
  *
  * @access protected
  * @return void
  */
  protected function _initLoaders() {
    if (!is_array($this->_loaders)) {
      $path = dirname(__FILE__).'/';
      include_once($path.'/Loader/DOMNode.php');
      include_once($path.'/Loader/DOMDocument.php');
      include_once($path.'/Loader/StringXML.php');
      include_once($path.'/Loader/FileXML.php');
      include_once($path.'/Loader/StringHTML.php');
      include_once($path.'/Loader/FileHTML.php');
      $this->_loaders = array(
        new FluentDOMLoaderDOMNode(),
        new FluentDOMLoaderDOMDocument(),
        new FluentDOMLoaderStringXML(),
        new FluentDOMLoaderFileXML(),
        new FluentDOMLoaderStringHTML(),
        new FluentDOMLoaderFileHTML(),
      );
    }
  }

  /**
  * Define own loading handlers
  *
  * @example iniloader/iniToXML.php Usage Example: Own loader object
  * @param $loaders
  * @access public
  * @return FluentDOM
  */
  public function setLoaders($loaders) {
    foreach ($loaders as $loader) {
      if (!($loader instanceof FluentDOMLoader)) {
        throw new InvalidArgumentException('Array contains invalid loader object');
      }
    }
    $this->_loaders = $loaders;
    return $this;
  }

  /**
  * Setter for FluentDOM::_contentType property
  *
  * @param string $value
  * @access protected
  * @return void
  */
  protected function _setContentType($value) {
    switch (strtolower($value)) {
    case 'xml' :
    case 'application/xml' :
    case 'text/xml' :
      $newContentType = 'text/xml';
      break;
    case 'html' :
    case 'text/html' :
      $newContentType = 'text/html';
      break;
    default :
      throw new UnexpectedValueException('Invalid content type value');
    }
    if ($this->_contentType != $newContentType) {
      $this->_contentType = $newContentType;
      if (isset($this->_parent)) {
        $this->_parent->contentType = $newContentType;
      }
    }
  }

  /**
  * implement dynamic properties using magic methods
  *
  * @param string $name
  * @access public
  * @return mixed
  */
  public function __get($name) {
    switch ($name) {
    case 'contentType' :
      return $this->_contentType;
    case 'document' :
      return $this->_document;
    case 'length' :
      return count($this->_array);
    case 'xpath' :
      return $this->_xpath();
    default :
      return NULL;
    }
  }

  /**
  * block changes of dynamic readonly property length
  *
  * @param string $name
  * @param mixed $value
  * @access public
  * @return void
  */
  public function __set($name, $value) {
    switch ($name) {
    case 'contentType' :
      $this->_setContentType($value);
      break;
    case 'document' :
    case 'length' :
    case 'xpath' :
      throw new BadMethodCallException('Can not set readonly value.');
    default :
      $this->$name = $value;
      break;
    }
  }

  /**
  * support isset for dynamic properties length and document
  *
  * @param string $name
  * @access public
  * @return boolean
  */
  public function __isset($name) {
    switch ($name) {
    case 'length' :
    case 'xpath' :
    case 'contentType' :
      return TRUE;
    case 'document' :
      return isset($this->_document);
    }
    return FALSE;
  }


  /**
  * Return the XML output of the internal dom document
  *
  * @access public
  * @return string
  */
  public function __toString() {
    switch ($this->_contentType) {
    case 'html' :
    case 'text/html' :
      return $this->_document->saveHTML();
    default :
      return $this->_document->saveXML();
    }
  }

  /**
  * The item() method is used to access elements in the node list,
  * like in a DOMNodelist.
  *
  * @param integer $position
  * @access public
  * @return DOMNode
  */
  public function item($position) {
    if (isset($this->_array[$position])) {
      return $this->_array[$position];
    }
    return NULL;
  }

  /**
  * Formats the current document, resets internal node array and other properties.
  *
  * The document is saved and reloaded, all variables with DOMNodes
  * of this document will get invalid.
  *
  * @access public
  * @return FluentDOM
  */
  public function formatOutput($contentType = NULL) {
    if (isset($contentType)) {
      $this->_setContentType($contentType);
    }
    $this->_array = array();
    $this->_position = 0;
    $this->_useDocumentContext = TRUE;
    $this->_parent = NULL;
    $this->_document->preserveWhiteSpace = FALSE;
    $this->_document->formatOutput = TRUE;
    if (!empty($this->_document->documentElement)) {
      $this->_document->loadXML($this->_document->saveXML());
    }
    return $this;
  }

  /*
  * Interface - IteratorAggregate
  */

  /**
  * Get an iterator for this object.
  *
  * @example interfaces/Iterator.php Usage Example: Iterator Interface
  * @example interfaces/RecursiveIterator.php Usage Example: Recursive Iterator Interface
  * @return FluentDOMIterator
  */
  public function getIterator() {
    return new FluentDOMIterator($this);
  }

  /*
  * Interface - Countable
  */

  /**
  * Get element count (Countable interface)
  *
  * @example interfaces/Countable.php Usage Example: Countable Interface
  * @access public
  * @return integer
  */
  public function count() {
    return count($this->_array);
  }

  /*
  * Interface - ArrayAccess
  */

  /**
  * If somebody tries to modify the internal array throw an exception.
  *
  * @example interfaces/ArrayAccess.php Usage Example: ArrayAccess Interface
  * @param integer $offset
  * @param mixed $value
  * @access public
  * @return void
  */
  public function offsetSet($offset, $value) {
    throw new BadMethodCallException('List is read only');
  }

  /**
  * Check if index exists in internal array
  *
  * @example interfaces/ArrayAccess.php Usage Example: ArrayAccess Interface
  * @param integer $offset
  * @access public
  * @return boolean
  */
  public function offsetExists($offset) {
    return isset($this->_array[$offset]);
  }

  /**
  * If somebody tries to remove an element from the internal array throw an exception.
  *
  * @example interfaces/ArrayAccess.php Usage Example: ArrayAccess Interface
  * @param integer $offset
  * @access public
  * @return void
  */
  public function offsetUnset($offset) {
    throw new BadMethodCallException('List is read only');
  }

  /**
  * Get element from internal array
  *
  * @example interfaces/ArrayAccess.php Usage Example: ArrayAccess Interface
  * @param $offset
  * @access public
  * @return void
  */
  public function offsetGet($offset) {
    return isset($this->_array[$offset]) ? $this->_array[$offset] : NULL;
  }

  /*
  * Core functions
  */

  /**
  * Create a new instance of the same class with $this as the parent. This is used for the chaining.
  *
  * @access public
  * @return  FluentDOM
  */
  public function spawn() {
    $className = get_class($this);
    $result = new $className();
    return $result->load($this);
  }

  /**
  * Push new element(s) an the internal element list
  *
  * @uses _inList
  * @param DOMElement|DOMNodeList|FluentDOM $elements
  * @param boolean $unique ignore duplicates
  * @access protected
  * @return void
  */
  public function push($elements, $unique = FALSE) {
    if ($this->_isNode($elements)) {
      if ($elements->ownerDocument === $this->_document) {
        if (!$unique || !$this->_inList($elements, $this->_array)) {
          $this->_array[] = $elements;
        }
      } else {
        throw new OutOfBoundsException('Node is not a part of this document');
      }
    } elseif ($elements instanceof DOMNodeList ||
              $elements instanceof DOMDocumentFragment ||
              $elements instanceof Iterator ||
              $elements instanceof IteratorAggregate ||
              is_array($elements)) {
      foreach ($elements as $node) {
        if ($this->_isNode($node)) {
          if ($node->ownerDocument === $this->_document) {
            if (!$unique || !$this->_inList($node, $this->_array)) {
              $this->_array[] = $node;
            }
          } else {
            throw new OutOfBoundsException('Node is not a part of this document');
          }
        }
      }
    }
  }

  /**
  * Get a XPath object associated with the internal DOMDocument and register
  * default namespaces from the document element if availiable.
  *
  * @access protected
  * @return DOMXPath
  */
  protected function _xpath() {
    if (empty($this->_xpath) || $this->_xpath->document != $this->_document) {
      $this->_xpath = new DOMXPath($this->_document);
      if ($this->_document->documentElement) {
        $uri = $this->_document->documentElement->lookupnamespaceURI('_');
        if (!isset($uri)) {
          $uri = $this->_document->documentElement->lookupnamespaceURI(NULL);
          if (isset($uri)) {
            $this->_xpath->registerNamespace('_', $uri);
          }
        }
      }
    }
    return $this->_xpath;
  }

  /**
  * Match XPath expression agains context and return matched elements.
  *
  * @param string $expr
  * @param DOMElement $context optional, default value NULL
  * @access protected
  * @return DOMNodeList
  */
  protected function _match($expr, $context = NULL) {
    if (isset($context)) {
      return $this->_xpath()->query($expr, $context);
    } else {
      return $this->_xpath()->query($expr);
    }
  }

  /**
  * Test xpath expression against context and return true/false
  *
  * @param string $expr
  * @param DOMElement $context optional, default value NULL
  * @access protected
  * @return boolean
  */
  protected function _test($expr, $context) {
    $check = $this->_xpath()->evaluate($expr, $context);
    if ($check instanceof DOMNodeList) {
      return $check->length > 0;
    } else {
      return (bool)$check;
    }
  }

  /**
  * Check if object is already in internal list
  *
  * @param DOMElement $node
  * @access protected
  * @return boolean
  */
  protected function _inList($node) {
    foreach ($this->_array as $compareNode) {
      if ($compareNode === $node) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
  * Validate string as qualified node name
  *
  * @param string $name
  * @access protected
  * @return boolean
  */
  protected function _isQName($name) {
    if (empty($name)) {
      throw new UnexpectedValueException('Invalid QName: QName is empty.');
    } elseif (FALSE !== strpos($name, ':')) {
      list($namespace, $localName) = explode(':', $name, 2);
      $this->_isNCName($namespace);
      $this->_isNCName($localName, strlen($namespace));
      return TRUE;
    }
    $this->_isNCName($name);
    return TRUE;
  }

  /**
  * Validate string as qualified node name part (namespace or local name)
  *
  * @param string $name
  * @param integer $offset index offset for excpetion messages
  * @access protected
  * @return boolean
  */
  protected function _isNCName($name, $offset = 0) {
    $nameStartChar =
       'A-Z_a-z'.
       '\\x{C0}-\\x{D6}\\x{D8}-\\x{F6}\\x{F8}-\\x{2FF}\\x{370}-\\x{37D}'.
       '\\x{37F}-\\x{1FFF}\\x{200C}-\\x{200D}\\x{2070}-\\x{218F}'.
       '\\x{2C00}-\\x{2FEF}\\x{3001}-\\x{D7FF}\\x{F900}-\\x{FDCF}'.
       '\\x{FDF0}-\\x{FFFD}\\x{10000}-\\x{EFFFF}';
    $nameChar =
       $nameStartChar.
       '\\.\\d\\x{B7}\\x{300}-\\x{36F}\\x{203F}-\\x{2040}';
    if (empty($name)) {
      throw new UnexpectedValueException(
        'Invalid QName "'.$name.'": Missing QName part.'
      );
    } elseif (preg_match('([^'.$nameChar.'])u', $name, $match, PREG_OFFSET_CAPTURE)) {
      //invalid bytes and whitespaces
      $position = (int)$match[0][1];
      throw new UnexpectedValueException(
        'Invalid QName "'.$name.'": Invalid character at index '.($offset + $position).'.'
      );
    } elseif (preg_match('(^[^'.$nameStartChar.'-])u', $name)) {
      //first char is a little more limited
      throw new UnexpectedValueException(
        'Invalid QName "'.$name.'": Invalid character at .index '.$offset.'.'
      );
    }
    return TRUE;
  }

  /**
  * Check if the DOMNode is DOMElement or DOMText with content
  *
  * @param DOMNode $node
  * @access protected
  * @return boolean
  */
  protected function _isNode($node) {
    if (is_object($node)) {
      if ($node instanceof DOMElement) {
        return TRUE;
      } elseif ($node instanceof DOMText &&
                !$node->isWhitespaceInElementContent()) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
  * check if parameter is a valid callback function
  *
  * @param callback $callback
  * @param boolean $allowGlobalFunctions
  * @param boolean $silent (no InvalidArgumentException)
  * @access protected
  * @return boolean
  */
  protected function _isCallback($callback, $allowGlobalFunctions, $silent) {
    if ($callback instanceof Closure) {
      return TRUE;
    } elseif (is_string($callback) &&
              $allowGlobalFunctions &&
              function_exists($callback)) {
      return is_callable($callback);
    } elseif (is_array($callback) &&
              count($callback) == 2 &&
              (is_object($callback[0]) || is_string($callback[0])) &&
              is_string($callback[1])) {
      return is_callable($callback);
    } elseif ($silent) {
      return FALSE;
    } else {
      throw new InvalidArgumentException('Invalid callback argument');
    }
  }

  /**
  * Convert a given content xml string into and array of nodes
  *
  * @param string $content
  * @param boolean $includeTextNodes
  * @param integer $limit
  * @access protected
  * @return array
  */
  protected function _getContentFragment($content, $includeTextNodes = TRUE, $limit = 0) {
    $result = array();
    $fragment = $this->_document->createDocumentFragment();
    if ($fragment->appendXML($content)) {
      for ($i = $fragment->childNodes->length - 1; $i >= 0; $i--) {
        $element = $fragment->childNodes->item($i);
        if ($element instanceof DOMElement ||
            ($includeTextNodes && $this->_isNode($element))) {
          array_unshift($result, $element);
          $element->parentNode->removeChild($element);
        }
      }
      if ($limit > 0 && count($result) >= $limit) {
        return array_slice($result, 0, $limit);
      }
      return $result;
    } else {
      throw new UnexpectedValueException('Invalid document fragment');
    }
  }

  /**
  * Convert a given content into and array of nodes
  *
  * @param string|array|DOMNode|Iterator $content
  * @param boolean $includeTextNodes
  * @param integer $limit
  * @access protected
  * @return array
  */
  protected function _getContentNodes($content, $includeTextNodes = TRUE, $limit = 0) {
    $result = array();
    if ($content instanceof DOMElement) {
      $result = array($content);
    } elseif ($includeTextNodes && $this->_isNode($content)) {
      $result = array($content);
    } elseif (is_string($content)) {
      $result = $this->_getContentFragment($content, $includeTextNodes, $limit);
    } elseif ($content instanceof DOMNodeList ||
              $content instanceof Iterator ||
              $content instanceof IteratorAggregate ||
              is_array($content)) {
      foreach ($content as $element) {
        if ($element instanceof DOMElement ||
            ($includeTextNodes && $this->_isNode($element))) {
          $result[] = $element;
          if ($limit > 0 && count($result) >= $limit) {
            break;
          }
        }
      }
    } else {
      throw new InvalidArgumentException('Invalid content parameter');
    }
    if (empty($result)) {
      throw new UnexpectedValueException('No element found');
    } else {
      //if a node is not in the current document import it
      foreach ($result as $index => $node) {
        if ($node->ownerDocument !== $this->_document) {
          $result[$index] = $this->_document->importNode($node, TRUE);
        }
      }
    }
    return $result;
  }

  /**
  * Get the target nodes from a given $selector.
  *
  * A string will be used as XPath expression.
  *
  * @param string|array|DOMNode|DOMNodeList|FluentDOM $selector
  * @return unknown_type
  */
  protected function _getTargetNodes($selector) {
    if ($this->_isNode($selector)) {
      return array($selector);
    } elseif (is_string($selector)) {
      return $this->_match($selector);
    } elseif (is_array($selector) ||
              $selector instanceof Iterator ||
              $selector instanceof IteratorAggregate ||
              $selector instanceof DOMNodeList) {
      return $selector;
    } else {
      throw new InvalidArgumentException('Invalid selector');
    }
  }

  /**
  * Remove nodes from document tree
  *
  * @param string|array|DOMNode|DOMNodeList|FluentDOM $selector
  * @access protected
  * @return array removed nodes
  */
  protected function _removeNodes($selector) {
    $targetNodes = $this->_getTargetNodes($selector);
    $result = array();
    foreach ($targetNodes as $node) {
      if ($node instanceof DOMNode &&
          isset($node->parentNode)) {
        $result[] = $node->parentNode->removeChild($node);
      }
    }
    return $result;
  }

  /**
  * Convert $content to a DOMElement. If $content contains several elements use the first.
  *
  * @param string|array|DOMNode|Iterator $content
  * @access protected
  * @return DOMElement
  */
  protected function _getContentElement($content) {
    if ($content instanceof DOMElement) {
      return $content;
    } else {
      $contentNodes = $this->_getContentNodes($content, FALSE, 1);
      return $contentNodes[0];
    }
  }
}