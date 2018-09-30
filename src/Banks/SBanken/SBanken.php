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
           * Get endpoint
           * 
           * @param string $endpoint
           * 
           * @return string
           */
          protected function getEndpoint(string $endpoint){
            if(!isset($this->endpoints[$endpoint]))  
                throw new SBankenEndpointException("Could not set endpoint. Make sure that you are trying to use an existing endpoint");

            return $this->endpoints[$endpoint];
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
           * @param bool $force
           * @return array|self
           */
          public function getAuthToken(bool $force = false){
            // Check if token exists in cache
            if($force == false && is_array($this->token))
                return $this->token;

            $this->forceRequest = true;
            
            $endpoint = $this->getEndpoint('token') . 'connect/token';

            // Get token from API
            $token = $this->request('POST', $endpoint, ['grant_type' => 'client_credentials'], array_merge($this->getDefaultHeaders(), [
                'Authorization' =>  $this->getBasicAuthorization()
            ]));

            if(!is_array($token))
                throw new SBankenAuthTokenException("Could not retrieve auth token. Ensure that your API token and secret is correct");
            
            $this->token = $token;

            return $token;
          }

          /**
           * Get customer
           * 
           * @param string $customerID
           * @return array|self
           */
          public function getCustomer(string $customerID){
              $endpoint = $this->getEndpoint('customers') . 'Customers';
              $customer = $this->request('GET', $endpoint, [], [
                  'customerId' => $customerID
              ]);

            if(!$this->isSuccessful($customer))
                throw new SBankenCustomerException("Could not retrieve Customer information. Ensure that you have the correct privileges to do so.");
            
            return $customer;
          }

          /**
           * Get accounts
           * 
           * @param string $customerID
           * @return array|self
           */
          public function getAccounts(string $customerID){
              $endpoint = $this->getEndpoint('accounts') . 'Accounts';
              $accounts = $this->request('GET', $endpoint, [], [
                  'customerId' => $customerID
              ]);

            if(!$this->isSuccessful($accounts))
                throw new SBankenAccountsException("Could not retrieve Accounts. Ensure that you have the corret privileges to do so.");
            
            return $accounts;
                
          }

          /**
           * Get specific account
           * 
           * @param string $customerID
           * @param string $accountID
           * @return array|self
           */
          public function getAccount(string $customerID, string $accountID){
            $endpoint = $this->getEndpoint('accounts') . 'Accounts/' . urlencode($accountID);
            $account = $this->request('GET', $endpoint, [], [
                'customerId' => $customerID
            ]);

            if(!$this->isSuccessful($account))
                throw new SBankenAccountsException("Could not retrieve Account. Ensure that you have the corret privileges to do so.");
               
            return $account;
          }

          /**
           * Get account transactions
           * 
           * @param string $customerID
           * @param string $accountID
           * @param int $index
           * @param int $length
           * @param mixed $startDate
           * @param mixed $endDate
           * @return array|self
           */
          public function getTransactions(string $customerID, string $accountID, int $index = 0, int $length = 100, $startDate = null, $endDate = null){
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

            $endpoint = $this->getEndpoint('accounts') . 'Transactions/' . urlencode($accountID);

            $transactions = $this->request('GET', $endpoint, $parameters, [
                'customerId' => $customerID
            ]);

            if(!$this->isSuccessful($transactions))
                throw new SBankenTransactionsException("Could not retrieve Transactions. Ensure that you have the corret privileges to do so.");

            return $transactions;
          }

          /**
           * Get all not-processed/new e-invoices
           * 
           * @param string $customerID
           * @param string $status
           * @param int $index
           * @param int $length
           * @param string $startDate
           * @param string $endDate
           * 
           * @return array|self
           */
          public function getEInvoices(string $customerID, string $status = 'ALL', int $index = 0, int $length = 100, string $startDate = null, string $endDate = null){
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

            $endpoint = $this->getEndpoint('accounts') . 'EFakturas';

            $eInvoices = $this->request('GET', $endpoint, $parameters, [
                'customerId' => $customerID
            ]);

            if(!$this->isSuccessful($eInvoices))
                throw new SBankenEInvoiceException("Could not GET new E-Invoices. Ensure that you have the correct access privileges");

            return $eInvoices;
          }

          /**
           * Get specific e-invoice
           * 
           * @param string $customerID
           * @param string $eInvoiceID
           * 
           * @return array|self
           */
          public function getEInvoice(string $customerID, string $eInvoiceID){
            $this->setEndpoint('accounts');
            $endpoint = $this->getEndpoint('accounts') . 'EFakturas/' . urlencode($eInvoiceID);

            $eInvoice = $this->request('GET', $endpoint, [], [
                'customerId' => $customerID
            ]);

            if(!$this->isSuccessful($eInvoice))
                throw new SBankenEInvoiceException("Could not GET E-Invoice. Ensure that you have the correct access privileges.");

            return $eInvoice;
          }

          /**
           * Transfer money between own accounts
           * 
           * @param string $customerID
           * @param string $fromAccountID
           * @param string $toAccountID
           * @param string $message
           * @param float $amount
           * 
           * @return array|self
           */
          public function postTransfer(string $customerID, string $fromAccountID, string $toAccountID, string $message, float $amount){
            $endpoint = $this->getEndpoint('accounts') . 'Transfers';

            $transfer = $this->request('POST', $endpoint, [
                'fromAccountId' => $fromAccountID,
                'toAccountId' => $toAccountID,
                'message' => $message,
                'amount' => $amount
            ], [
                'customerId' => $customerID,
                'Content-Type' => 'text/json'
            ]);

            if(!$this->isSuccessful($transfer))
                throw new SBankenTransferException("Could not make transfer. Ensure that you have the correct access privileges.");

            return $transfer;
          }

          /**
           * Pay non-processed/new E-Invoice
           * 
           * @param string $customerID
           * @param string $eInvoiceID
           * @param string $accountID
           * @param bool $payOnlyMinimumAmount
           * 
           * @return array|self
           */
          public function postEInvoice(string $customerID, string $eInvoiceID, string $accountID, bool $payOnlyMinimumAmount = false){
              $endpoint = $this->getEndpoint('accounts') . 'EFakturas';

              $payment = $this->request('POST', $endpoint, [
                  'eFakturaId' => $eInvoiceID,
                  'accountId' => $accountID,
                  'payOnlyMinimumAmount' => $payOnlyMinimumAmount
              ], [
                  'customerId' => $customerID,
                  'Content-Type' => 'text/json'
              ]);

              if(!$this->isSuccessful($payment))
                throw new SBankenEInvoiceException("Could not pay e-invoice. Make sure that you have the correct access privileges and that the e-invoice hasn't been paid already.");

              return $payment;
          }
     } 
