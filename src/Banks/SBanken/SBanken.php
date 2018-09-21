<?php
    namespace Teskon\PSD2PHP\Banks\SBanken;
    use Teskon\PSD2PHP\Banks\Bank;
    use Teskon\PSD2PHP\Exceptions\Banks\SBanken\{
        SBankenAccountsException, 
        SBankenAuthTokenException, 
        SBankenCustomerException, 
        SBankenEInvoiceException,
        SBankenEndpointException, 
        SBankenTransactionsException,
        SBankenTransferException
    };
    use DateTime;

    /**
     * Module for connections to the SBanken public API (https://sbanken.no)
     * Please note that this API is currently in BETA. Bugs can occur when using this API.
     * 
     * You can read more on this API here: https://github.com/Sbanken/api-examples
     * 
     * @author Kristian Auestad <kristianaue@gmail.com>
     */

     class SBanken extends Bank {
         /**
          * Endpoints to API 
          *
          * @var array $endpoints
          */
          protected $endpoints = [
                'token' => 'https://auth.sbanken.no/identityserver/',
                'customers' => 'https://api.sbanken.no/customers/api/v1/',
                'accounts' => 'https://api.sbanken.no/bank/api/v1/'
          ];

          /**
           * Current endpoint
           *
           * @var string $endpoint
           */
           protected $endpoint = 'https://auth.sbanken.no/identityserver/';

          /**
           * API token
           * 
           * @var array|null $token
           */
          public $token = null;

          /**
           * Set default configuration variables for SBanken
           * 
           * @return array
          */
          protected function getDefaultConfigurationVariables(){
              return [
                /**
                 * (string) base URI to API endpoint
                 */
                'base_uri' => $this->endpoints['token'],
              ];
          }

          /**
           * Update endpoint
           * 
           * @var string $endpoint
           * @return void
           */
            protected function setEndpoint(string $endpoint){
                if(!isset($this->endpoints[$endpoint]))
                    throw new SBankenEndpointException("Could not set endpoint. Make sure that you are trying to use an existing endpoint");
                
                $this->endpoint = $this->endpoints[$endpoint];
            }
        
          /**
           * Get default headers
           * 
           * @return array
           */
          protected function getDefaultHeaders(){
              return [
                  /**
                   * (string) default token used to communicate with the API
                   */
                  'Authorization' => '',
                  
                  /**
                   * (string) return format
                   */
                  'Accept' => 'application/json',

                  /**
                   * (string) Content-Type
                   */
                  'Content-Type' => 'application/x-www-form-urlencoded; charset=utf-8'
              ];
          }

          /**
           * Get header token
           * 
           * @var bool $force
           * @return array
           */
          public function getAuthToken(bool $force = false){
            // Check if token exists in cache
            if($force == false && is_string($this->token))
                return $this->token;

            // Get token from API
            $token = $this->request('POST', '/identityserver/connect/token', ['grant_type' => 'client_credentials'], array_merge($this->getDefaultHeaders(), [
                'Authorization' =>  $this->getBasicAuthorization()
            ]));

            if(!is_array($token) || !isset($token['access_token'], $token['expires_in'], $token['token_type']))
                throw new SBankenAuthTokenException("Could not retrieve auth token. Ensure that your API token and secret is correct");
            
            $this->token = $token['access_token'];
            
            return [
                'access_token' => $this->token,
                'expires_in' => $token['expires_in'],
                'token_type' => $token['token_type']
            ];
          }

          /**
           * Get customer
           * 
           * @var string $customerID
           * @return array
           */
          public function getCustomer(string $customerID){
              $this->setEndpoint('customers');
              $customer = $this->request('GET', 'Customers', [], [
                  'customerId' => $customerID
              ]);

            if(!is_array($customer))
                throw new SBankenCustomerException("Could not retrieve Customer information. Ensure that you have the correct privileges to do so.");
            
            return $customer;
          }

          /**
           * Get accounts
           * 
           * @var string $customerID
           * @return array
           */
          public function getAccounts(string $customerID){
              $this->setEndpoint('accounts');
              $accounts = $this->request('GET', 'Accounts', [], [
                  'customerId' => $customerID
              ]);

            if(!is_array($accounts))
                throw new SBankenAccountsException("Could not retrieve Accounts. Ensure that you have the corret privileges to do so.");
            
            return $accounts;
                
          }

          /**
           * Get specific account
           * 
           * @var string $customerID
           * @var string $accountID
           * @return array
           */
          public function getAccount(string $customerID, string $accountID){
            $this->setEndpoint('accounts');
            $account = $this->request('GET', 'Accounts/' . urlencode($accountID), [], [
                'customerId' => $customerID
            ]);

            if(!is_array($account))
                throw new SBankenAccountsException("Could not retrieve Account. Ensure that you have the corret privileges to do so.");
               
            return $account;
          }

          /**
           * Get account transactions
           * 
           * @var string $customerID
           * @var string $accountID
           * @var int $index
           * @var int $length
           * @var mixed $startDate
           * @var mixed $endDate
           * @return array
           */
          public function getTransactions(string $customerID, string $accountID, int $index = 0, int $length = 100, $startDate = null, $endDate = null){
            $this->setEndpoint('accounts');

            // Build GET parameters
            $parameters = [
                'index' => $index,
                'length' => $length
            ];

            $minDate = '2000-01-01';
            $maxDate = (new DateTime('+1 day'))->format('Y-m-d');

            if($endDate == null){
                $endDate = (new DateTime)->format('Y-m-d');
            }

            if($startDate == null){
                $startDate = (new DateTime($endDate . ' -30 days'));
            }

            if($endDate > $maxDate)
                throw new SBankenTransactionsException('The maximum end date that is allowed by this API is ' . $maxDate);
            
            if($startDate < $minDate)
                throw new SBankenTransactionsException('The minimum start date that is allowed by this API is ' . $minDate);

            $parameters['startDate'] = $startDate;
            $parameters['endDate'] = $endDate;

            $parameters = '?' . http_build_query($parameters);

            $transactions = $this->request('GET', 'Transactions/' . urlencode($accountID) . $parameters, [], [
                'customerId' => $customerID
            ]);

            if(!is_array($transactions))
                throw new SBankenTransactionsException("Could not retrieve Transactions. Ensure that you have the corret privileges to do so.");

            return $transactions;
          }

          /**
           * Get all not-processed/new e-invoices
           * 
           * @var string $customerID
           * @var string $status
           * @var int $index
           * @var int $length
           * @var string $startDate
           * @var string $endDate
           * 
           * @return array
           */
          public function getEInvoices(string $customerID, string $status = 'ALL', int $index = 0, int $length = 100, string $startDate = null, string $endDate = null){
            $this->setEndpoint('accounts');

            $status = strtoupper($status);
            if(!in_array($status, ['ALL', 'NEW', 'PROCESSED', 'DELETED']))
                throw new SBankenEInvoiceException("The status needs to be ALL, NEW, PROCESSED or DELETED.");

            if($length > 1000)
                throw new SBankenEInvoiceException("The maximum length that can be used is 1000.");
            
            if($length < 0)
                throw new SBankenEInvoiceException("The length needs to be at least 0.");

            $minDate = '2000-01-01';
            $maxDate = (new DateTime('+60 days'))->format('Y-m-d');

            if($endDate == null){
                $endDate = (new DateTime('+60 days'))->format('Y-m-d');
            }

            if($startDate == null){
                $startDate = (new DateTime($endDate . ' -60 days'))->format('Y-m-d');
            }

            if($endDate > $maxDate)
                throw new SBankenEInvoiceException('The maximum end date that is allowed by this API is ' . $maxDate);
            
            if($startDate < $minDate)
                throw new SBankenEInvoiceException('The minimum start date that is allowed by this API is ' . $minDate);

            $parameters = [
                'status' => $status,
                'index' => $index,
                'length' => $length,
                'startDate' => $startDate,
                'endDate' => $endDate
            ];

            $parameters = '?' . http_build_query($parameters);

            $eInvoices = $this->request('GET', 'EFakturas' . $parameters, [], [
                'customerId' => $customerID
            ]);

            if(!is_array($eInvoices))
                throw new SBankenEInvoiceException("Could not GET new E-Invoices. Ensure that you have the correct access privileges");

            return $eInvoices;
          }

          /**
           * Get specific e-invoice
           * 
           * @var string $customerID
           * @var string $eInvoiceID
           * 
           * @return array
           */
          public function getEInvoice(string $customerID, string $eInvoiceID){
            $this->setEndpoint('accounts');

            $eInvoice = $this->request('GET', 'EFakturas/' . urlencode($eInvoiceID), [], [
                'customerId' => $customerID
            ]);

            if(!is_array($eInvoice))
                throw new SBankenEInvoiceException("Could not GET E-Invoice. Ensure that you have the correct access privileges.");

            return $eInvoice;
          }

          /**
           * Transfer money between own accounts
           * 
           * @var string $customerID
           * @var string $fromAccountID
           * @var string $toAccountID
           * @var string $message
           * @var float $amount
           * 
           * @return array
           */
          public function postTransfer(string $customerID, string $fromAccountID, string $toAccountID, string $message, float $amount){
            $this->setEndpoint('accounts');

            $transfer = $this->request('POST', 'Transfers', json_encode([
                'fromAccountId' => $fromAccountID,
                'toAccountId' => $toAccountID,
                'message' => $message,
                'amount' => $amount
            ]), [
                'customerId' => $customerID,
                'Content-Type' => 'text/json'
            ]);

            if(!is_array($transfer))
                throw new SBankenTransferException("Could not make transfer. Ensure that you have the correct access privileges.");

            return $transfer;
          }

          /**
           * Pay non-processed/new E-Invoice
           * 
           * @var string $customerID
           * @var string $eInvoiceID
           * @var string $accountID
           * @var bool $payOnlyMinimumAmount
           * 
           * @return array
           */
          public function postEInvoice(string $customerID, string $eInvoiceID, string $accountID, bool $payOnlyMinimumAmount = false){
              $this->setEndpoint('accounts');

              $payment = $this->request('POST', 'EFakturas', json_encode([
                  'eFakturaId' => $eInvoiceID,
                  'accountId' => $accountID,
                  'payOnlyMinimumAmount' => $payOnlyMinimumAmount
              ]), [
                  'customerId' => $customerID,
                  'Content-Type' => 'text/json'
              ]);

              if(!is_array($payment))
                throw new SBankenEInvoiceException("Could not pay e-invoice. Make sure that you have the correct access privileges and that the e-invoice hasn't been paid already.");

              return $payment;
          }
     } 
