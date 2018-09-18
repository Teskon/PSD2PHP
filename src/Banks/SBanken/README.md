PSD2PHP - SBanken integration
=======================
Integration used to connect to the SBanken public API (https://sbanken.no)

You can read more about the APIs used here:
[Accounts API](https://api.sbanken.no/Bank/swagger/index.html)
[Customers API](https://api.sbanken.no/Customers/swagger/index.html)

**PLEASE NOTE THAT THIS API IS CURRENTLY IN BETA. BUGS CAN OCCUR**

## Documentation
Start with creating a class object that will be used later to connect to the different APIs. To do so, you need to create a ClientID and a ClientSecret at [SBanken](https://sbanken.no/bruke/utviklerportalen/) (only available in Norwegian)

When you've created yourself a ClientID and a ClientSecret, create an instance of the SBanken API using one of the following examples:
```php
use Teskon\PSD2PHP\PSD2PHP;

require 'vendor/autoload.php';

$SBanken = new PSD2PHP("SBanken", "ClientID", "ClientSecret");
```

```php
use Teskon\PSD2PHP\Banks\SBanken\SBanken;

require 'vendor/autoload.php';

$SBanken = new SBanken("ClientID", "ClientSecret");
```

Please note that all the examples below should be added after the code above.

### Configuration
To setup the SBanken instance with your own configuration you can add it an `array` parameter to the constructor. Examples:
```php
use Teskon\PSD2PHP\PSD2PHP;

require 'vendor/autoload.php';

$SBanken = new PSD2PHP("SBanken", "ClientID", "ClientSecret", [
    'max' => 3
]);
```

```php
use Teskon\PSD2PHP\Banks\SBanken\SBanken;

require 'vendor/autoload.php';

$SBanken = new SBanken("ClientID", "ClientSecret", [
    'max' => 3
]);
```

The example above will create an instance of the SBanken object with a max number of allowed redirects of 3. Available configuration parameters are listed here:

| Type       | Key                 | Description                          | Default value     |
|------------|---------------------|--------------------------------------|-------------------|
| int        | max                 | Maximum number of allowed redirects. | 5                 |
| bool       | strict              | Set to true to use strict redirects. Strict RFC compliant redirects mean that POST redirect requests are sent as POST requests vs. doing what most browsers do which is redirect POST requests with GET requests. | false |
| bool       | referer             | Set to true to enable adding the Referer header when redirecting. | false             |
| array      | protocols           | Specified which protocols are allowed for redirect requests.| ['http', 'https'] |
| callable   | on_redirect         | PHP callable that is invoked when a redirect is encountered. The callable is invoked with the original request and the redirect response that was received. Any return value from the on_redirect function is ignored. | null              |
| bool       | track_redirects     | When set to true, each redirected URI and status code encountered will be tracked in the X-Guzzle-Redirect-History and X-Guzzle-Redirect-Status-History headers respectively. All URIs and status codes will be stored in the order which the redirects were encountered. | false             |

In this version there is no option to change the configuration values for each request. This is a planned feature that will be added soon. If you need to do that now, you can recreate the bank instance.

### Get Customer information
You can get the customer information by running the `getCustomer` command:

```php
$customerID = '12345678910';
$customer = $SBanken->getCustomer($customerID);
```

Parameters:
   
* `$customerID` (string) *required* 
  Your social security numbers (11 characters long).

### Get Accounts 
You can get the accounts owned by a customer by running the `getAccounts` command:

```php
$customerID = '12345678910';
$accounts = $SBanken->getAccounts($customerID);
```

Parameters:

* `$customerID` (string) *required*  
  Your social security numbers (11 characters long).

### Get Account
You can get a specific account owned by a customer by running the `getAccount` command:

```php
$customerID = '12345678910';
$accountID = 'abc';
$account = $SBanken->getAccount($customerID, $accountID);
```

Parameters:

* `$customerID` (string) *required*
  Your social security numbers (11 characters long).
* `$accountID` (string) *required*
  Account ID retrieved from `getAccounts`

### Get Transactions
You can get transactions beloning to an account by running the `getTransactions` command:

```php
$customerID = '12345678910';
$accountID = 'abc';
$index = 0;
$length = 100;
$startDate = '2018-01-01';
$endDate = '2018-01-31';
$transactions = $SBanken->getTransactions($customerID, $accountID, $index, $length, $startDate, $endDate);
```

Parameters:

* `$customerID` (string) *required*
  Your social security numbers (11 characters long).
* `$accountID` (string) *required*
  Account ID retrieved from `getAccounts`
* `$index` (int) *optional, default: 0*
  Starting index of retrieved results
* `$length` (int) *optional, default: 100*
  Result limit. Minimum value is 1. Maximum value is 1000.
* `$startDate` (string) *optional, default: current date - 30 days*
  Date to start retrieving transactions from. Minimum date is 2000-01-01. Maximum date is current date + 1 day.
* `$endDate` (string) *optional, default: current date*
  Date to stop retrieving transactions from. Minimum date is $startDate. Maximum date is current date + 1 day.

### Get E-Invoices
You can get e-invoices belonging to a customer by running the `getEInvoices` command:

```php
$customerID = '12345678910';
$status = 'ALL';
$index = 0;
$length = 100;
$startDate = '2018-01-01';
$endDate = '2018-01-31';
$eInvoices = $SBanken->getEInvoices($customerID, $status, $index, $length, $startDate, $endDate);
```

Parameters:

* `$customerID` (string) *required*
  Your social security numbers (11 characters long).
* `$status` (string) *optional, default: ALL*
  Current status of e-invoices you want returned. Can be ALL, NEW, PROCESSED or DELETED.
* `$index` (int) *optional, default: 0*
  Starting index of retrieved results
* `$length` (int) *optional, default: 100*
  Result limit. Minimum value is 1. Maximum value is 1000.
* `$startDate` (string) *optional, default: current date - 60 days*
  Date to start retrieving transactions from. Minimum date is 2000-01-01. Maximum date is current date + 60 day.
* `$endDate` (string) *optional, default: current date + 60 days*
  Date to stop retrieving transactions from. Minimum date is $startDate. Maximum date is current date + 60 days.

### Get E-Invoice
You can get specific e-invoices belonging to a customer by running the `getEInvoice` command:

```php
$customerID = '12345678910';
$eInvoiceID = 'abc';
$eInvoice = $SBanken->getEInvoices($customerID, $eInvoiceID);
```

Parameters:

* `$customerID` (string) *required*
  Your social security numbers (11 characters long).
* `$eInvoiceID` (string) *required*
  ID of specified E-Invoice retrieved from `getEInvoices`

## Planned functionality
We're planning to update this module by adding support for more functionality as it is available. Currently we are planning to add support for:
* Transfer money between owned accounts
* Pay E-Invoices.
* Make it possible to change configuration variables for each request