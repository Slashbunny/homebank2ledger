<?php

namespace Bunny\HomeBank;

class CategoryParser
{
    static public function parse( \DOMNodeList $nodes )
    {
        $categories = array();

        foreach ( $nodes as $cat_node )
        {
            $category = new Category( $cat_node );

            $categories[ $category[ 'key' ] ] = $category;
        }

        return $categories;
    }
}
