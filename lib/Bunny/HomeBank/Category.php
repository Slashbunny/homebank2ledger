<?php

namespace Bunny\HomeBank;

class Category
    implements \ArrayAccess
{
    const OPTIONAL = 0x00;

    const REQUIRED = 0x01;

    private $_data = array();

    private static $_expected_fields = array(
        'key'    => self::REQUIRED,
        'name'   => self::REQUIRED,
        'parent' => self::OPTIONAL,
        'flags'  => self::OPTIONAL,
        'b0'     => self::OPTIONAL,
    );

    public function __construct( \DOMElement $node )
    {
        // Assign XML node values to data array
        foreach ( $node->attributes as $attribute )
        {
            $name  = $attribute->nodeName;
            $value = $attribute->nodeValue;

            if ( !isset( self::$_expected_fields[ $name ] ) )
            {
                throw new \Exception( 'Unknown node attribute ' . $name );
            }

            $this->_data[ $name ] = $value;
        }

        // Check that we aren't missing any required fields
        foreach ( self::$_expected_fields as $name => $type )
        {
            if ( $type === self::REQUIRED && !isset( $this[ $name ] ) )
            {
                throw new \Exception( 'Node missing required field ' . $name );
            }
        }
    }

    public function offsetExists( $offset )
    {
        return isset( $this->_data[ $offset ] );
    }

    public function offsetSet( $offset, $value )
    {
        $this->_data[ $offset ] = $value;
    }

    public function offsetGet( $offset )
    {
        return $this->_data[ $offset ];
    }

    public function offsetUnset( $offset )
    {
        unset( $this->_data[ $offset ] );
    }
}
