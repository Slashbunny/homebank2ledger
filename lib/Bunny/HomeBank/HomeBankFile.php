<?php

namespace Bunny\HomeBank;

class HomeBankFile
{
    private $_accounts   = array();
    private $_payees     = array();
    private $_categories = array();
    private $_entries    = array();

    public function __construct( \DOMDocument $dom, TypeParser $parser )
    {
        $this->_accounts = $parser->parse(
            'Account',
            $dom->getElementsByTagName( 'account' )
        );

        $this->_payees = $parser->parse(
            'Payee',
            $dom->getElementsByTagName( 'pay' )
        );

        $this->_categories = $parser->parse(
            'Category',
            $dom->getElementsByTagName( 'cat' )
        );

        $this->_entries = $parser->parse(
            'Entry',
            $dom->getElementsByTagName( 'ope' )
        );
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
