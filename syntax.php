<?php
/**
 * Dokuwiki Plugin EBNF: Displays Syntax Diagrams
 *
 * Syntax: <ebnf> ebnf syntax </ebnf>
 *
 * @license    GPL3
 * @author     Vincent Tscherter <vincent@tscherter.net>
 * @version    0.5
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
             $ebnf = substr($data[0], 6, -7); // remove <ebnf> and </ebnf>
             $ebnf = trim($ebnf); // remove spaces around
             if (empty($ebnf)) return $ebnf='{}'; // if empty, use {}
             $ebnf = preg_replace( "/\\s+/", " ", $ebnf); // replace multiple spaces by one space
             $query = "syntax=".urlencode($ebnf); // urlencode the query
             $renderer->doc .= "<img src='".DOKU_BASE."lib/plugins/ebnf/ebnf.php?$query' alt='$query'/>";          
            } catch (Exception $e) {
              $renderer->doc .= "<pre>".htmlentities($ebnf)."\n".$e."</pre>";
            }
            return true;
        }
        return false;
    }
}
?>