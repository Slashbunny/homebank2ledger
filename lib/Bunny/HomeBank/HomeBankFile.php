<?php

namespace Bunny\HomeBank;

class HomeBankFile
{
    private $_accounts   = array();
    private $_payees     = array();
    private $_categories = array();
    private $_entries    = array();

    public function __construct( \DOMDocument $dom, TypeParser $tparser, NodeParser $nparser )
    {
        $this->_accounts = $tparser->parse(
            'Account',
            $dom->getElementsByTagName( 'account' ),
            $nparser
        );

        $this->_payees = $tparser->parse(
            'Payee',
            $dom->getElementsByTagName( 'pay' ),
            $nparser
        );

        $this->_categories = $tparser->parse(
            'Category',
            $dom->getElementsByTagName( 'cat' ),
            $nparser
        );

        $this->_entries = $tparser->parse(
            'Entry',
            $dom->getElementsByTagName( 'ope' ),
            $nparser
        );

        // Account Post-processing
        $this->_processAccounts();

        // Category Post-processing
        $this->_processCategories();

        // Entry Post-processing
        $this->_processEntries();
    }

    private function _processAccounts()
    {
        $stdin  = fopen( 'php://stdin', 'r' );
        $stderr = fopen( 'php://stderr', 'w' );

        foreach ( $this->_accounts as $id => $data )
        {
            // Determine whether accounts are assets or liabilities
            $continue = false;

            while ( $continue === false )
            {
                fwrite( $stderr, 'Is the account "' . $data['name'] . '" an (A)sset or (L)iability? ');

                $line = fgets( $stdin );

                switch ( strtolower( trim( $line ) ) )
                {
                    case 'a':
                    case 'asset':
                        $continue     = true;
                        $account_type = 'Assets';
                        break;
                    case 'l':
                    case 'liability':
                        $continue     = true;
                        $account_type = 'Liabilities';
                        break;
                    case 'q':
                        exit;
                        break;
                    default:
                        break;
                }
            }

            $this->_accounts[ $id ][ 'account_type' ] = $account_type;

            // Set Initial Balance to 0
            if ( empty( $data[ 'initial' ] ) )
            {
                $this->_accounts[ $id ][ 'initial' ] = 0.00;
            }
            else
            {
                // Round Initial Balances
                $this->_accounts[ $id ][ 'initial' ] = round( $data[ 'initial' ], 2 );
            }
        }
    }

    private function _processCategories()
    {
        foreach ( $this->_categories as $id => $data )
        {
            $this->_categories[ $id ][ 'full_name' ] = substr( $this->_expandCategoryNames( $id ), 0, -1 );
        }
    }

    private function _processEntries()
    {
        foreach ( $this->_entries as $id => $data )
        {
            // Convert Date
            $this->_entries[ $id ][ 'date' ]   = $this->_convertJulianDate( $data[ 'date' ] );

            // Round Amount
            $this->_entries[ $id ][ 'amount' ] = round( $data[ 'amount' ], 2 );

            // Add Reconciliation Flag
            $this->_entries[ $id ][ 'reconciled' ] = (bool)( (int)$data['flags'] & 1 );
        }
    }

    private function _expandCategoryNames( $id, $full_name = '' )
    {
        $cat = $this->_categories[ $id ];

        // Prepend category to string
        $full_name = $cat[ 'name' ] . ':' . $full_name;

        // If there is no parent, return string as-is
        if ( empty( $cat[ 'parent' ] ) )
        {
            return $full_name;
        }

        return $this->_expandCategoryNames( $cat[ 'parent' ], $full_name );
    }

    private function _convertJulianDate( $julian_days )
    {
        $date = new \DateTime( '00-00-0001' );

        $date->add( new \DateInterval( 'P' . $julian_days . 'D' ) );

        return $date->format( 'Y/m/d' );
    }

    public function getAccounts()
    {
        return $this->_accounts;
    }

    public function getPayees()
    {
        return $this->_payees;
    }

    public function getCategories()
    {
        return $this->_categories;
    }

    public function getEntries()
    {
        return $this->_entries;
    }
}
