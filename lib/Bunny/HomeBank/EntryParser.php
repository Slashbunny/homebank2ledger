<?php

namespace Bunny\HomeBank;

class EntryParser
{
    static public function parse( \DOMNodeList $nodes )
    {
        $entries = array();

        foreach ( $nodes as $entry_node )
        {
            $entries[] = new Entry( $entry_node );
        }

        usort( $entries, array( 'self', 'sort_tran_date' ) );

        return $entries;
    }

    static public function sort_tran_date( $a, $b )
    {
        $a_date = strtotime( $a[ 'date' ] );
        $b_date = strtotime( $b[ 'date' ] );

        if ( $a_date > $b_date )
        {
            return 1;
        }
        elseif ( $a_date < $b_date )
        {
            return -1;
        }
        else
        {
            return 0;
        }
    }
}
