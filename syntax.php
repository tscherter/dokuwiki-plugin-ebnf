<?php
/**
 * Dokuwiki Plugin EBNF: Displays Syntax Diagrams
 *
 * Syntax: <ebnf> ebnf syntax </ebnf>
 *
 * @license    GPL3
 * @author     Vincent Tscherter <vinent.tscherter@karmin.ch>
 * @version    0.1
 */

if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

class syntax_plugin_ebnf extends DokuWiki_Syntax_Plugin {

    function getType(){
        return 'substition';
    }

    function getSort(){
        return 999;
    }

    function connectTo($mode) {
      $this->Lexer->addSpecialPattern('<ebnf>.*?</ebnf>',$mode,'plugin_ebnf');
    }

    function handle($match, $state, $pos, &$handler){
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

    function render($mode, &$renderer, $data) {
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