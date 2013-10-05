<?php

namespace Bunny\HomeBank;

class PayeeParser
{
    static public function parse( \DOMNodeList $nodes )
    {
        $payees = array();

        foreach ( $nodes as $payee_node )
        {
            $payee = new Payee( $payee_node );

            $payees[ $payee[ 'key' ] ] = $payee;
        }

        return $payees;
    }
}
