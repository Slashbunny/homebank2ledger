<?php

namespace Bunny\HomeBank;

class HomeBankFile
{
    private $_accounts         = array();
    private $_payees           = array();
    private $_categories       = array();
    private $_entries          = array();
    private $_delete_entry_ids = array();

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

            // Sanitize Wording
            $this->_entries[ $id ][ 'wording' ] = $this->_sanitizeComment( $data[ 'wording' ] );

            // Sanitize Info
            $this->_entries[ $id ][ 'info' ] = $this->_sanitizeComment( $data[ 'info' ] );

            // Resolve Payee ID
            $this->_entries[ $id ][ 'payee_name' ] = !empty( $data[ 'payee' ] )
                ? $this->_payees[ $data[ 'payee' ] ][ 'name' ]
                : 'Unknown Payee';

            // Account-to-account transfer
            if ( !empty( $data[ 'kxfer' ] ) )
            {
                // HomeBank records account-to-account transfers twice, once per
                // account. This means, when we are generating a ledger, we need
                // to ignore one of the transactions
                //
                // For no reason in particular, we are going to ignore the entry
                // with the negative value.

                if ( $data[ 'amount' ] < 0 )
                {
                    // Save the ID to an array. At the end of processing, it
                    // will be deleted from the array
                    $this->_delete_entry_ids[] = $id;
                    continue;
                }

                $account1 = $this->_accounts[ $data[ 'account' ] ];
                $account2 = $this->_accounts[ $data[ 'dst_account' ] ];

                $this->_entries[ $id ][ 'account1' ] = $account1[ 'account_type' ] . ':' . $account1[ 'name' ];
                $this->_entries[ $id ][ 'account2' ] = $account2[ 'account_type' ] . ':' . $account2[ 'name' ];
            }
            // Normal transaction
            else
            {
                // This this income or expense?
                $income_expense = ( $data[ 'amount' ] > 0 ) ? 'Income' : 'Expenses';

                // Determine Income/Expense Category
                $category = !empty( $data[ 'category' ] )
                    ? $this->_categories[ $data[ 'category' ] ][ 'full_name' ]
                    : 'Unknown Account';

                // Resolve Account Name
                $account = $this->_accounts[ $data[ 'account' ] ];

                // Create Credit/Debit Accounts for Ledger Entry
                $this->_entries[ $id ][ 'account1' ] = $income_expense . ':' . $category;
                $this->_entries[ $id ][ 'account2' ] = $account[ 'account_type' ] . ':' . $account[ 'name' ];

                // Invert Amount because we want to display the Income/Expense first during output
                $this->_entries[ $id ][ 'amount' ] = $data[ 'amount' ] * -1;
            }

        }

        // Remove Entries Marked for Deletion
        foreach ( $this->_delete_entry_ids as $id )
        {
            unset( $this->_entries[ $id ] );
        }

        // Sort Entries by Date
        usort( $this->_entries, array( $this, '_sortByDate' ) );
    }

    private function _sortByDate( $a, $b )
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
        $date = new \DateTime( '0001-01-00' );

        $date->add( new \DateInterval( 'P' . $julian_days . 'D' ) );

        return $date->format( 'Y/m/d' );
    }

    private function _sanitizeComment( $string )
    {
        // Square brackets have special meaning in comments
        $string = str_replace( '[', '(', $string );
        $string = str_replace( ']', ')', $string );

        // Colons designate tags in ledger
        $string = str_replace( ':', '：', $string );

        return $string;
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
