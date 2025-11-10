<?php

namespace Botkaplus;

class Utils {
    function Bold($text){
        return "**$text**";
    }

    function Hyperlink($text, $link){
        return "[" . trim($text) . "](" . trim($link) . ")";
    }

    function Italic($text){
        return "__" . trim($text) . "__";
    }

    function Underline($text){
        return "--" . $text . "--";
    }

    function Mono($text){
        return "`" . trim($text) . "`";
    }

    function Strike($text){
        return "~~" . trim($text) . "~~";
    }

    function Spoiler($text){
        return "||" . trim($text) . "||";
    }

    function Code($text){
        return "```" . trim($text) . "```";
    }

    function Quote($text){
        return "$" . trim($text) . "$";
    }
}

?>