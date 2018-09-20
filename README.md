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

If you want to start using this package, you can read our [Getting Started](https://psd2php.org/docs/en/getting-started/installation/) guide.

## Documentation
There are two ways to use this package:
```php
use Teskon\PSD2PHP\PSD2PHP;

require 'vendor/autoload.php';

$bank = new PSD2PHP("Bank", "ClientID", "ClientSecret");
```

Please note that the above code is just an example using the Bank `Bank` with the client ID `ClientID` and client secret `ClientSecret`. Some banks require different configuration values, and might allow for specification using a `configuration` parameter. Check the readme of the bank you're going to use in `src/Banks/YOUR BANK/` for more details about which configuration variables are needed.

You can also directly connect to the bank using the main class of the bank itself:
```php
use Teskon\PSD2PHP\Banks\SBanken\SBanken;

require 'vendor/autoload.php';

$bank = new SBanken("ClientID", "ClientSecret");
```

Note that the example above is using our first bank integration (SBanken). Change this to the bank you want to use. Check the available banks in the `Banks` folder.

For more details about which commands you can run, read the readme of the bank you're using.

## Problems/bugs
If you encounter any problems/bugs, please let us know on our [Issue tracker](https://github.com/Teskon/PSD2PHP/issues).