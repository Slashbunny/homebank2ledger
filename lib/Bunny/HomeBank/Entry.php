<?php

namespace Bunny\HomeBank;

class Entry
    implements \ArrayAccess
{
    const OPTIONAL = 0x00;

    const REQUIRED = 0x01;

    private $_data = array();

    private static $_expected_fields = array(
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

        // Convert date to Y/m/d format
        $this[ 'date' ] = $this->_convertDate( $this[ 'date' ] );

        // Round Amount
        $this[ 'amount' ] = round( $this[ 'amount' ], 2 );
    }

    /**
     * Converts date from glibc julian date to year/month/day
     *
     */
    private function _convertDate( $julian_days )
    {
        $date = new \DateTime( '00-00-0001' );

        $date->add( new \DateInterval( 'P' . $julian_days . 'D' ) );

        return $date->format( 'Y/m/d' );
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
