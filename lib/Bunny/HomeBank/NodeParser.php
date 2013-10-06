<?php

namespace Bunny\HomeBank;

class NodeParser
{
    const OPTIONAL = 0x00;
    const REQUIRED = 0x01;

    private static $_expected_fields = array(
        'Account' => array(
            'key'      => self::REQUIRED,
            'name'     => self::REQUIRED,
            'initial'  => self::OPTIONAL,
            'number'   => self::OPTIONAL,
            'bankname' => self::OPTIONAL,
            'pos'      => self::OPTIONAL,
            'type'     => self::OPTIONAL,
            'minimum'  => self::OPTIONAL,
            'cheque1'  => self::OPTIONAL,
            'flags'    => self::OPTIONAL,
        ),
        'Entry' => array(
            'date'        => self::REQUIRED,
            'amount'      => self::REQUIRED,
            'account'     => self::REQUIRED,
            'dst_account' => self::OPTIONAL,
            'kxfer'       => self::OPTIONAL,
            'paymode'     => self::OPTIONAL,
            'flags'       => self::OPTIONAL,
            'payee'       => self::OPTIONAL,
            'category'    => self::OPTIONAL,
            'wording'     => self::OPTIONAL,
            'info'        => self::OPTIONAL,
        ),
        'Payee' => array(
            'key'  => self::REQUIRED,
            'name' => self::REQUIRED,
        ),
        'Category' => array(
            'key'    => self::REQUIRED,
            'name'   => self::REQUIRED,
            'parent' => self::OPTIONAL,
            'flags'  => self::OPTIONAL,
            'b0'     => self::OPTIONAL,
        ),
    );

    public function __construct()
    {
        return;
    }

    public function parse( $type, \DOMElement $node )
    {
        $result = array();

        // Blow up if this is a type we don't understand
        if ( !isset( self::$_expected_fields[ $type ] ) )
        {
            throw new \Exception( 'Unknown code type ' . $type );
        }

        // Assign XML node values to result array
        foreach ( $node->attributes as $attribute )
        {
            $name  = $attribute->nodeName;
            $value = $attribute->nodeValue;

            if ( !isset( self::$_expected_fields[ $type ][ $name ] ) )
            {
                throw new \Exception( 'Unknown node attribute ' . $name );
            }

            $result[ $name ] = $value;
        }

        // Check that we aren't missing any required fields
        foreach ( self::$_expected_fields[ $type ] as $name => $type )
        {
            if ( $type === self::REQUIRED && !isset( $result[ $name ] ) )
            {
                throw new \Exception( 'Node missing required field ' . $name );
            }

            // Initialize missing values to empty strings
            if ( !isset( $result[ $name ] ) )
            {
                $result[ $name ] = '';
            }
        }

        return $result;
    }
}
