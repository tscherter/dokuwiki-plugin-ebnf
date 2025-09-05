<?php
/*
    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.

    Vincent Tscherter, tscherter@tscherter.net, Solothurn, 2009-01-18

    2009-01-18 version 0.1 first release
    2009-01-02 version 0.2
      - title und comment literal added
      - ";" als terminator-symbol added
    2023-09-28 version 0.3 prefixed all constants
    2025-09-05 version 0.4 PHP 8 compatibility and other fixes
*/

define('META', 'https://www.dokuwiki.org/plugin:ebnf');

// parser
define('EBNF_OPERATOR_TOKEN', 1);
define('EBNF_LITERAL_TOKEN', 2);
define('EBNF_WHITESPACE_TOKEN', 3);
define('EBNF_IDENTIFIER_TOKEN', 4);

// rendering
define('EBNF_FONT', 2);
define('EBNF_U', 10);
define('EBNF_AW', 3);

// lexemes
$ebnf_lexemes[] = array( 'type' => EBNF_OPERATOR_TOKEN, 'expr' => '[={}()|.;[\]]' );
$ebnf_lexemes[] = array( 'type' => EBNF_LITERAL_TOKEN,  'expr' => "\"[^\"]*\"" );
$ebnf_lexemes[] = array( 'type' => EBNF_LITERAL_TOKEN,  'expr' => "'[^']*'" );
$ebnf_lexemes[] = array( 'type' => EBNF_IDENTIFIER_TOKEN,  'expr' => "[a-zA-Z0-9_-]+" );
$ebnf_lexemes[] = array( 'type' => EBNF_WHITESPACE_TOKEN,  'expr' => "\\s+" );

// input example
$input = <<<EOD
"EBNF defined in itself" {
  syntax     = [ title ] "{" { rule } "}" [ comment ].
  rule       = identifier "=" expression ( "." | ";" ) .
  expression = term { "|" term } .
  term       = factor { factor } .
  factor     = identifier
             | literal
             | "[" expression "]"
             | "(" expression ")"
             | "{" expression "}" .
  identifier = character { character } .
  title      = literal .
  comment    = literal .
  literal    = "'" character { character } "'"
             | '"' character { character } '"' .
}
EOD;

if (isset($_GET['syntax'])) {
  $input = $_GET['syntax'];
  $input = stripslashes($input);
}

$format = "png";
if (isset($_GET['format'])) $format = $_GET['format'];

try {
  $tokens = ebnf_scan($input, true);
  $dom = ebnf_parse_syntax($tokens);
  if ($format == 'xml') {
    header('Content-Type: application/xml');
    echo $dom->saveXML();
  } else {
    render_node($dom->firstChild, true);
  }
} catch (EbnfException $e) {
    header('Content-Type: text/plain');
    $dom = new DOMDocument();
    $syntax = $dom->createElement("syntax");
    $syntax->setAttribute('title', 'EBNF - Syntax Error');
    $syntax->setAttribute('meta',
        $e->getMessage()
        . " - '" . substr($input, $e->getPos(), 30) . "...'"
    );
    $dom->appendChild($syntax);
    render_node($dom->firstChild, true);
}

function rr($im, $x1, $y1, $x2, $y2, $r, $black){
  imageline($im, $x1+$r, $y1, $x2-$r, $y1, $black);
  imageline($im, $x1+$r, $y2, $x2-$r, $y2, $black);
  imageline($im, $x1, $y1+$r, $x1, $y2-$r, $black);
  imageline($im, $x2, $y1+$r, $x2, $y2-$r, $black);
  imagearc($im, $x1+$r, $y1+$r, 2*$r, 2*$r, 180, 270, $black);
  imagearc($im, $x2-$r, $y1+$r, 2*$r, 2*$r, 270, 360, $black);
  imagearc($im, $x1+$r, $y2-$r, 2*$r, 2*$r, 90, 180, $black);
  imagearc($im, $x2-$r, $y2-$r, 2*$r, 2*$r, 0, 90, $black);
}

function create_image($w, $h) {
  global $white, $black, $blue, $red, $green, $silver;
  $im = imagecreatetruecolor($w, $h) or die("no img");
  imageantialias($im, true);
  $white = imagecolorallocate ($im, 255, 255, 255);
  $black = imagecolorallocate ($im, 0, 0, 0);
  $blue = imagecolorallocate ($im, 0, 0, 255);
  $red = imagecolorallocate ($im, 255, 0, 0);
  $green = imagecolorallocate ($im, 0, 200, 0);
  $silver = imagecolorallocate ($im, 127, 127, 127);
  imagefilledrectangle($im, 0,0,$w,$h,$white);
  return $im;
}

function arrow($image, $x, $y, $lefttoright) {
  global $white, $black;
  if (!$lefttoright) {
      $points = array($x, $y - EBNF_U / 3, $x - EBNF_U, $y, $x, $y + EBNF_U / 3);
  } else {
      $points = array($x - EBNF_U, $y - EBNF_U / 3, $x, $y, $x - EBNF_U, $y + EBNF_U / 3);
  }
  if (PHP_VERSION_ID >= 80000 ) {
      imagefilledpolygon($image, $points, $black);
  } else {
      imagefilledpolygon($image, $points, 3, $black);
  }
}


function render_node($node, $lefttoright) {
  global $white, $black, $blue, $red, $green, $silver;
  if ($node->nodeName=='identifier' || $node->nodeName=='terminal') {
    $text = html_entity_decode($node->getAttribute('value'));
    $w = imagefontwidth(EBNF_FONT)*(strlen($text)) + 4*EBNF_U;
    $h = 2*EBNF_U;
    $im = create_image($w, $h);

    if ($node->nodeName!='terminal') {
        imagerectangle($im, EBNF_U, 0, $w-EBNF_U-1, $h-1, $black);
        imagestring($im, EBNF_FONT, 2*EBNF_U, intdiv($h-imagefontheight(EBNF_FONT), 2), $text, $red);
    } else {
      if ($text!="...")
	      rr($im, EBNF_U, 0, $w-EBNF_U-1, $h-1, EBNF_U/2, $black);
      imagestring($im, EBNF_FONT, 2*EBNF_U, intdiv($h-imagefontheight(EBNF_FONT), 2),
        $text, $text!="..."?$blue:$black);
    }
    imageline($im,0,EBNF_U, EBNF_U, EBNF_U, $black);
    imageline($im,$w-EBNF_U,EBNF_U, $w+1, EBNF_U, $black);
    return $im;
  } else if ($node->nodeName=='option' || $node->nodeName=='loop') {
    if ($node->nodeName=='loop')
      $lefttoright = ! $lefttoright;
    $inner = render_node($node->firstChild, $lefttoright);
    $w = imagesx($inner)+6*EBNF_U;
    $h = imagesy($inner)+2*EBNF_U;
    $im = create_image($w, $h);
    imagecopy($im, $inner, 3*EBNF_U, 2*EBNF_U, 0,0, imagesx($inner), imagesy($inner));
    imageline($im,0,EBNF_U, $w, EBNF_U, $black);
    arrow($im, $w/2+EBNF_U/2, EBNF_U, $node->nodeName=='loop'?!$lefttoright:$lefttoright);
    arrow($im, 3*EBNF_U, 3*EBNF_U, $lefttoright);
    arrow($im, $w-2*EBNF_U, 3*EBNF_U, $lefttoright);
    imageline($im,EBNF_U,EBNF_U, EBNF_U, 3*EBNF_U, $black);
    imageline($im,EBNF_U,3*EBNF_U, 2*EBNF_U, 3*EBNF_U, $black);
    imageline($im,$w-EBNF_U,EBNF_U, $w-EBNF_U, 3*EBNF_U, $black);
	imageline($im,$w-3*EBNF_U-1,3*EBNF_U, $w-EBNF_U, 3*EBNF_U, $black);
    return $im;
  } else if ($node->nodeName=='sequence') {
    $inner = render_childs($node, $lefttoright);
    if (!$lefttoright)
      $inner = array_reverse($inner);
    $w = count($inner)*EBNF_U-EBNF_U; $h = 0;
    for ($i = 0; $i<count($inner); $i++) {
      $w += imagesx($inner[$i]);
      $h = max($h, imagesy($inner[$i]));
    } $im = create_image($w, $h);
    imagecopy($im, $inner[0], 0, 0, 0,0, imagesx($inner[0]), imagesy($inner[0]));
    $x = imagesx($inner[0])+EBNF_U;
    for ($i = 1; $i<count($inner); $i++) {
      imageline($im, $x-EBNF_U-1, EBNF_U, $x, EBNF_U, $black);
      arrow($im, $x, EBNF_U, $lefttoright);
      imagecopy($im, $inner[$i], $x, 0, 0,0, imagesx($inner[$i]), imagesy($inner[$i]));
      $x += imagesx($inner[$i])+EBNF_U;
    } return $im;
  } else if ($node->nodeName=='choise') {
    $inner = render_childs($node, $lefttoright);
    $h = (count($inner)-1)*EBNF_U; $w = 0;
    for ($i = 0; $i<count($inner); $i++) {
      $h += imagesy($inner[$i]);
      $w = max($w, imagesx($inner[$i]));
    } $w += 6*EBNF_U; $im = create_image($w, $h); $y = 0;
    imageline($im, 0, EBNF_U, EBNF_U, EBNF_U, $black);
    imageline($im, $w-EBNF_U, EBNF_U, $w, EBNF_U, $black);
    for ($i = 0; $i<count($inner); $i++) {
      imageline($im, EBNF_U, $y+EBNF_U, $w-EBNF_U, $y+EBNF_U, $black);
      imagecopy($im, $inner[$i], 3*EBNF_U, $y, 0,0, imagesx($inner[$i]), imagesy($inner[$i]));
      arrow($im, 3*EBNF_U, $y+EBNF_U, $lefttoright);
      arrow($im, $w-2*EBNF_U, $y+EBNF_U, $lefttoright);
      $top = $y + EBNF_U;
      $y += imagesy($inner[$i])+EBNF_U;
    }
    imageline($im, EBNF_U, EBNF_U, EBNF_U, $top, $black);
    imageline($im, $w-EBNF_U, EBNF_U, $w-EBNF_U, $top, $black);
    return $im;
  } else if ($node->nodeName=='syntax') {
    $title = $node->getAttribute('title');
    $meta = $node->getAttribute('meta');
    $node = $node->firstChild;
    $names = array();
    $images = array();
    while ($node!=null) {
	   $names[] = $node->getAttribute('name');
	   $im = render_node($node->firstChild, $lefttoright);
	   $images[] = $im;
       $node = $node->nextSibling;
    } $wn  = 0; $wr = 0; $h = 5*EBNF_U;
    for ($i = 0; $i<count($images); $i++) {
      $wn = max($wn, imagefontwidth(EBNF_FONT)*strlen($names[$i]));
      $wr = max($wr, imagesx($images[$i]));
	  $h += imagesy($images[$i])+2*EBNF_U;
    }
    if ($title=='') $h -= 2*EBNF_U;
    if ($meta=='') $h -= 2*EBNF_U;
    $w = max($wr+$wn+3*EBNF_U, imagefontwidth(1)*strlen($meta)+2*EBNF_U);
    $im = create_image($w, $h);
    $y = 2*EBNF_U;
    if ($title!='') {
      imagestring($im, EBNF_FONT, EBNF_U, intdiv(2*EBNF_U-imagefontheight(EBNF_FONT), 2),
      $title, $green);
      imageline($im, 0, 2*EBNF_U, $w, 2*EBNF_U, $green);
      $y += 2*EBNF_U;
    }
    for ($i = 0; $i<count($images); $i++) {
      imagestring($im, EBNF_FONT, EBNF_U, $y-EBNF_U+intdiv(2*EBNF_U-imagefontheight(EBNF_FONT), 2), $names[$i], $red);
      imagecopy($im, $images[$i], $wn+2*EBNF_U, $y, 0,0, imagesx($images[$i]) , imagesy($images[$i]));
      imageline($im, EBNF_U, $y+EBNF_U, $wn+2*EBNF_U, $y+EBNF_U, $black);
      imageline($im, $wn+2*EBNF_U+imagesx($images[$i])-1, $y+EBNF_U, $w-EBNF_U, $y+EBNF_U, $black);
      imageline($im, $w-EBNF_U, $y+EBNF_U/2, $w-EBNF_U ,$y+1.5*EBNF_U, $black);
      $y += 2*EBNF_U + imagesy($images[$i]);
    }
    imagestring($im, 1, EBNF_U, $h-2*EBNF_U+(2*EBNF_U-imagefontheight(1))/2,
      $meta, $silver);
    rr($im, 0,0,$w-1, $h-1, EBNF_U/2, $green);
    header('Content-Type: image/png');
    imagepng($im);
    return $im;
  }
}

function render_childs($node, $lefttoright) {
   $childs = array();
   $node = $node->firstChild;
   while ($node!=null) {
     $childs[] = render_node($node, $lefttoright);
     $node = $node->nextSibling;
   } return $childs;
}

function ebnf_scan(&$input) {
  global $ebnf_lexemes;
  $i = 0; $n = strlen($input); $m = count($ebnf_lexemes); $tokens = array();
  while ($i < $n) {
    $j = 0;
    while ($j < $m &&
      preg_match("/^{$ebnf_lexemes[$j]['expr']}/", substr($input,$i), $matches)==0) $j++;
    if ($j<$m) {
      if ($ebnf_lexemes[$j]['type']!=EBNF_WHITESPACE_TOKEN)
        $tokens[] = array('type' => $ebnf_lexemes[$j]['type'],
          'value' => $matches[0], 'pos' => $i);
      $i += strlen($matches[0]);
	} else
	  throw new EbnfException("Invalid token at position", $i);
  } return $tokens;
}


function ebnf_check_token($token, $type, $value) {
  return $token['type']==$type && $token['value']==$value;
}

function ebnf_parse_syntax(&$tokens) {
  $dom = new DOMDocument();
  $syntax = $dom->createElement("syntax");
  $syntax->setAttribute('meta', META);
  $dom->appendChild($syntax);
  $i = 0; $token = $tokens[$i++];
  if ($token['type'] == EBNF_LITERAL_TOKEN) {
    $syntax->setAttribute('title',
      stripcslashes(substr($token['value'], 1, strlen($token['value'])-2 )));
    $token = $tokens[$i++];
  }
  if (!ebnf_check_token($token, EBNF_OPERATOR_TOKEN, '{') )
    throw new EbnfException("Syntax must start with '{'", $token['pos']);
  $token = $tokens[$i];
  while ($i < count($tokens) && $token['type'] == EBNF_IDENTIFIER_TOKEN) {
    $syntax->appendChild(ebnf_parse_production($dom, $tokens, $i));
    if ($i<count($tokens)) $token = $tokens[$i];
  } $i++; if (!ebnf_check_token($token, EBNF_OPERATOR_TOKEN, '}'))
    throw new EbnfException("Syntax must end with '}'", $tokens[count($tokens)-1]['pos']);
  if ($i<count($tokens)) {
    $token = $tokens[$i];
    if ($token['type'] == EBNF_LITERAL_TOKEN) {
      $syntax->setAttribute('meta',
        stripcslashes(substr($token['value'], 1, strlen($token['value'])-2 )));
    }
  }
    return $dom;
}

function ebnf_parse_production(&$dom, &$tokens, &$i) {
  $token = $tokens[$i++];
  if ($token['type']!=EBNF_IDENTIFIER_TOKEN)
    throw new EbnfException("Production must start with an identifier'{'", $token['pos']);
  $production = $dom->createElement("rule");
  $production->setAttribute('name', $token['value']);
  $token = $tokens[$i++];
  if (!ebnf_check_token($token, EBNF_OPERATOR_TOKEN, "="))
    throw new EbnfException("Identifier must be followed by '='", $token['pos']);
  $production->appendChild( ebnf_parse_expression($dom, $tokens, $i));
  $token = $tokens[$i++];
  if (!ebnf_check_token($token, EBNF_OPERATOR_TOKEN, '.')
    && !ebnf_check_token($token, EBNF_OPERATOR_TOKEN, ';'))
    throw new EbnfException("Rule must end with '.' or ';'", $token['pos']);
  return $production;
}

function ebnf_parse_expression(&$dom, &$tokens, &$i) {
  $choise = $dom->createElement("choise");
  $choise->appendChild(ebnf_parse_term($dom, $tokens, $i));
  $token=$tokens[$i]; $mul = false;
  while (ebnf_check_token($token, EBNF_OPERATOR_TOKEN, '|')) {
    $i++;
    $choise->appendChild(ebnf_parse_term($dom, $tokens, $i));
    $token=$tokens[$i]; $mul = true;
  } return $mul ? $choise : $choise->removeChild($choise->firstChild);
}

function ebnf_parse_term(&$dom, &$tokens, &$i) {
  $sequence = $dom->createElement("sequence");
  $factor = ebnf_parse_factor($dom, $tokens, $i);
  $sequence->appendChild($factor);
  $token=$tokens[$i]; $mul = false;
  while ($token['value']!='.' && $token['value']!='=' && $token['value']!='|'
    && $token['value']!=')' && $token['value']!=']' && $token['value']!='}') {
    $sequence->appendChild(ebnf_parse_factor($dom, $tokens, $i));
    $token=$tokens[$i]; $mul = true;
  } return $mul ? $sequence: $sequence->removeChild($sequence->firstChild);
}

function ebnf_parse_factor(&$dom, &$tokens, &$i) {
  $token = $tokens[$i++];
  if ($token['type']==EBNF_IDENTIFIER_TOKEN) {
    $identifier = $dom->createElement("identifier");
    $identifier->setAttribute('value', $token['value']);
    return $identifier;
  } if ($token['type']==EBNF_LITERAL_TOKEN){
    $literal = $dom->createElement("terminal");
    $literal->setAttribute('value', stripcslashes(substr($token['value'], 1, strlen($token['value'])-2 )));
    return $literal;
  } if (ebnf_check_token($token, EBNF_OPERATOR_TOKEN, '(')) {
    $expression = ebnf_parse_expression($dom, $tokens, $i);
    $token = $tokens[$i++];
    if (!ebnf_check_token($token, EBNF_OPERATOR_TOKEN, ')'))
      throw new EbnfException("Group must end with ')'", $token['pos']);
    return $expression;
  } if (ebnf_check_token($token, EBNF_OPERATOR_TOKEN, '[')) {
    $option = $dom->createElement("option");
    $option->appendChild(ebnf_parse_expression($dom, $tokens, $i));
    $token = $tokens[$i++];
    if (!ebnf_check_token($token, EBNF_OPERATOR_TOKEN, ']'))
      throw new EbnfException("Option must end with ']'", $token['pos']);
    return $option;
  } if (ebnf_check_token($token, EBNF_OPERATOR_TOKEN, '{')) {
    $loop = $dom->createElement("loop");
    $loop->appendChild(ebnf_parse_expression($dom, $tokens, $i));
    $token = $tokens[$i++];
    if (!ebnf_check_token($token, EBNF_OPERATOR_TOKEN, '}'))
      throw new EbnfException("Loop must end with '}'", $token['pos']);
    return $loop;
  }
  throw new EbnfException("Factor expected", $token['pos']);
}

class EbnfException extends Exception {
    protected int $pos;

    public function __construct($message, $pos) {
        $this->pos = $pos;
        parent::__construct($message . ": $pos");
    }

    public function getPos() {
        return $this->pos;
    }
}
