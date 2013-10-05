<?php

namespace Bunny\HomeBank;

class TypeParser
{
    static public function parse( $type, \DOMNodeList $nodes )
    {
        $objects    = array();
        $class_type = 'Bunny\\HomeBank\\' . $type;

        foreach ( $nodes as $node )
        {
            $object = new $class_type( $node );

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
