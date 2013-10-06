<?php

namespace Bunny\HomeBank;

class TypeParser
{
    public function __construct()
    {
        return;
    }

    public function parse( $type, \DOMNodeList $nodes, NodeParser $parser )
    {
        $objects = array();

        foreach ( $nodes as $node )
        {
            $object = $parser->parse( $type, $node );

            // If the nodes we are parsing have an attribute called 'key', use
            // that as the key in the PHP array we are creating.
            if ( isset( $object[ 'key' ] ) )
            {
                $objects[ $object[ 'key' ] ] = $object;
            }
            else
            {
                $objects[] = $object;
            }
        }

        return $objects;
    }
}
