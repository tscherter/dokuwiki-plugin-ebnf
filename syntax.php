<?php
/**
 * Dokuwiki Plugin EBNF: Displays Syntax Diagrams
 *
 * Syntax: <ebnf> ebnf syntax </ebnf>
 *
 * @license    GPL3
 * @author     Vincent Tscherter <vinent.tscherter@karmin.ch>
 * @version    0.4
 */

use dokuwiki\Extension\SyntaxPlugin;

class syntax_plugin_ebnf extends SyntaxPlugin {

    function getType(){
        return 'substition';
    }

    function getSort(){
        return 999;
    }

    function connectTo($mode) {
      $this->Lexer->addSpecialPattern('<ebnf>.*?</ebnf>',$mode,'plugin_ebnf');
    }

    function handle($match, $state, $pos, Doku_Handler $handler){
       switch ($state) {
          case DOKU_LEXER_ENTER :
            break;
          case DOKU_LEXER_MATCHED :
            break;
          case DOKU_LEXER_UNMATCHED :
            break;
          case DOKU_LEXER_EXIT :
            break;
          case DOKU_LEXER_SPECIAL :
            break;
        }
        return array($match);
    }

    function render($mode, Doku_Renderer $renderer, $data) {
        if($mode == 'xhtml'){
            try {
             $text = substr($data[0], 6, strlen($data[0])-13);
             $text = preg_replace( "/[<>]+/", "", $text);
             $text = preg_replace( "/[\\n\\r\\t ]+/", " ", $text);
             $text = urlencode($text);
             $renderer->doc .= "<img src='".DOKU_URL."lib/plugins/ebnf/ebnf.php?syntax=$text' alt='$text'/>";            // ptype = 'normal'
            } catch (Exception $e) {
              $renderer->doc .= "<pre>".htmlentities($text)."\n".$e."</pre>";
            }
            return true;
        }
        return false;
    }
}
?>