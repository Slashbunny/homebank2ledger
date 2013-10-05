<?php

namespace Bunny\HomeBank;

class AccountParser
{
    static public function parse( \DOMNodeList $nodes )
    {
        $accounts = array();

        foreach ( $nodes as $account_node )
        {
            $account = new Account( $account_node );

            $accounts[ $account[ 'key' ] ] = $account;
        }

        return $accounts;
    }
}
