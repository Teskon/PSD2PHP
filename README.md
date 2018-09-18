PSD2PHP - PHP library for connecting to PSD2 enabled banks
=======================

[![Latest Version](https://img.shields.io/github/release/Teskon/PSD2PHP.svg?style=flat-square)](https://github.com/Teskon/PSD2PHP/releases)
[![Total Downloads](https://img.shields.io/packagist/dt/Teskon/PSD2PHP.svg?style=flat-square)](https://packagist.org/packages/Teskon/PSD2PHP)

PSD2PHP is a library used to make it easy to use the banking APIs after PSD2. This project is currently based around Norwegian banks, but can be expanded to include international banks in the future.

## Dependencies
To use this project you need the following:
```
php >= 7.1
cURL
```

We are using [guzzlehttp](https://github.com/guzzle/guzzle) in order to send the API requests. If you're not installing this package from composer you will need to download that to your project as well. Running `composer install` should be enough if you're cloning this project.

## Installation
We recommend that you install PSD2PHP using [Composer](http://getcomposer.org) in order to download all the dependencies at the same time.

```bash
# Install Composer
curl -sS https://getcomposer.org/installer | php
```

Next, run the Composer command to install the latest stable version of PSD2PHP:

```bash
composer require teskon/psd2php
```

After the installation is done, you need to require the composer autoloader in your PHP project:
```php
require 'vendor/autoload.php';
```

To update PSD2PHP, run the composer update command:
```bash
composer update
```

## Documentation
There are two ways to use this package:
```php
use Teskon\PSD2PHP\PSD2PHP;

require 'vendor/autoload.php';

$bank = new PSD2PHP("Bank", "ClientID", "ClientSecret");
```

Please note that the above code is just an example using the Bank `Bank` with the client ID `ClientID` and client secret `ClientSecret`. Some banks require different configuration values, and might allow for specification using a `configuration` parameter. Check the readme of the bank you're going to use in `Banks/YOUR BANK/` for more details about which configuration variables are needed.

You can also directly connect to the bank using the main class of the bank itself:
```php
use Teskon\PSD2PHP\Banks\SBanken\SBanken;

require 'vendor/autoload.php';

$bank = new SBanken("ClientID", "ClientSecret");
```

Note that the example above is using our first bank integration (SBanken). Change this to the bank you want to use. Check the available banks in the `Banks` folder.

For more details about which commands you can run, read the readme of the bank you're using.

## Problem/bugs
If you encounter any problems/bugs, please let me know on our [Issue tracker](https://github.com/Teskon/PSD2PHP/issues).