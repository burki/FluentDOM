<?php
/**
* Example file for property 'attr'
*
* @license http://www.opensource.org/licenses/mit-license.php The MIT License
* @copyright Copyright (c) 2010-2014 Bastian Feder, Thomas Weinert
*/
header('Content-type: text/plain');

$html = <<<HTML
<html>
 <head>
  <title>FluentDOM project page</title>
 </head>
 <body>
  <p>
   Always nice to visit
   <a href='http://fluentdom.org'>here</a> or
   <a href='http://github.org/FluentDOM'>here.</a>
  </p>
 </body>
</html>
HTML;

echo "Example for property 'attr' - reading:\n\n";
require_once('../../src/FluentDOM.php');
$fd = FluentDOM::Query($html, 'text/html')->find('//a');
foreach ($fd as $node) {
  $fdNode = FluentDOM::Query($node);
  echo $fdNode->attr['href'], "\n";
}