<?php
/**
 * FluentDOM
 *
 * @link https://thomas.weinert.info/FluentDOM/
 * @copyright Copyright 2009-2019 FluentDOM Contributors
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 */

declare(strict_types=1);
/**
 * FluentDOM\DOM\Comment extends PHPs DOMComment class.
 *
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * @copyright Copyright (c) 2009-2017 FluentDOM Contributors
 */

namespace FluentDOM\DOM {

  /**
   * FluentDOM\DOM\Comment extends PHPs DOMComment class.
   *
   * @property-read Document $ownerDocument
   * @property-read Element $nextElementSibling
   * @property-read Element $previousElementSibling
   */
  class Comment
    extends \DOMComment
    implements Node\NonDocumentTypeChildNode  {

    use Node\ChildNode\Implementation;
    use Node\NonDocumentTypeChildNode\Properties;
    use Node\StringCast;
    use Node\Xpath;
  }
}
