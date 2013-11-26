# homebank2ledger

Converts [HomeBank][homebank] data files into [Ledger][ledger-cli] format. Due to the fact ledger is a double-entry accounting system and HomeBank is single entry, the conversion will likely require additional manual tweaking, depending on your accounting preferences.

[homebank]: http://homebank.free.fr/
[ledger-cli]: http://ledger-cli.org/

## Setup

A modern version of PHP (5.3+) is required to run homebank2ledger. [Composer][composer] is required to install additional dependencies.

Run composer within the project directory first:

```sh
$ composer install
```

[composer]: http://getcomposer.org/

## Usage

The program can be run directly from the cloned repository:

```sh
$ ./homebank2ledger -f untitled.xhb > converted.ledger
```

