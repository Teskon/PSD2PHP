<?php
    namespace Teskon\PSD2PHP\Banks;
    use GuzzleHttp\{
        Client,
        Pool
    };
    use GuzzleHttp\Psr7;
    use GuzzleHttp\Psr7\{
        Request,
        Response
    };
    use Teskon\PSD2PHP\Exceptions\Banks\BankException;
    use GuzzleHttp\Exception\BadResponseException;

    /**
     * Interface for main functions that all banks need to have.
     * 
     * @author Kristian Auestad <kristianaue@gmail.com>
     */

     abstract class Bank {
        /**
         * @var string $authIdentifier
         */
        protected $authIdentifier;

        /**
         * @var string $authSecret
         */
        protected $authSecret;

        /**
         * @var array $configuration
         */
        protected $configuration;

        /**
         * @var array $temporaryConfiguration;
         */
        protected $temporaryConfiguration;

        /**
         * @var array $defaultHeaders
         */
        protected $defaultHeaders = [];

        /**
         * @var GuzzleHttp\Client $httpClient
         */
        protected $httpClient;

        /**
         * @var array $validMethods
         */
        private $validMethods = ['GET', 'POST', 'DELETE', 'HEAD', 'OPTIONS', 'PATCH', 'PUT'];

        /**
         * @var int $currentTry
         */
        private $currentTry = 0;

        /**
         * @var bool $queueActive
         */
        private $queueActive = false;

        /**
         * @var array $queue
         */
        private $queue = [];

        /**
         * @var bool $forceRequest
         */
        protected $forceRequest = false;
          
        /**
         * Default constructor for storing configuration variables
         * 
         * @var string $authIdentifier
         * @var string $authSecret
         */
        public function __construct(string $authIdentifier, string $authSecret, array $configuration = []){
            $this->authIdentifier = $authIdentifier;
            $this->authSecret = $authSecret;

            // Set initial configuration
            $this->setConfiguration($configuration);

            if(method_exists($this, 'getAuthToken') && isset($this->configuration['auth_init']) && $this->configuration['auth_init'] === true){
                // Get Auth Token
                $this->getAuthToken();
            }

            if(method_exists($this, 'getDefaultHeaders')){
                // Set default headers
                $this->defaultHeaders = $this->getDefaultHeaders();
            }

            // Make sure httpClient was set properly
            if(!$this->httpClient instanceof Client){
                throw new BankException("A problem occured during GuzzleHTTP initialization. Make sure that the correct version of GuzzleHTTP is installed and that you are using the correct configuration variables.");
            }
        }

        /**
         * Get base default configuration variables
         * 
         * @return array
         */
        private function getBaseDefaultConfigurationVariables(){
            return [
                /**
                 * (string) base URI to API endpoint
                 */
                'base_uri' => '',

                /**
                 * (int, default=5) maximum number of allowed redirects.
                 *
                 */
                'max' => 5,

                /**
                 * (bool, default=false) Set to true to use strict redirects. Strict RFC compliant redirects mean that POST redirect requests are sent as POST requests vs. doing what most browsers do which is redirect POST requests with GET requests.
                 * 
                 */
                'strict' => false,

                /**
                 * (bool, default=false) Set to true to enable adding the Referer header when redirecting.
                 * 
                 */
                'referer' => false,
                
                /**
                 * (array, default=['http', 'https']) Specified which protocols are allowed for redirect requests.
                 * 
                 */
                'protocols' => ['http', 'https'],

                /**
                 * (callable) PHP callable that is invoked when a redirect is encountered. The callable is invoked with the original request and the redirect response that was received. Any return value from the on_redirect function is ignored.
                 * 
                 */
                'on_redirect' => null,

                /**
                 * (bool) When set to true, each redirected URI and status code encountered will be tracked in the X-Guzzle-Redirect-History and X-Guzzle-Redirect-Status-History headers respectively. All URIs and status codes will be stored in the order which the redirects were encountered.
                 * 
                 */
                'track_redirects' => false,

                /**
                 * (bool) When set to true, we will run the getAuthToken method from the bank on initialization
                 * 
                 */
                'auth_init' => false,

                /**
                 * (int) Number of times we're going to retry to get the authentication token before giving up if we're getting an invalid response from the API.
                 * 
                 */
                'auth_retries' => 1,

                /**
                 * (array) Error codes that will cause a retry to be initiated
                 * 
                 */
                'auth_retries_codes' => [
                    500
                ],

                /**
                 * (int) Default concurrency of batch requests
                 * 
                 */
                'batch_concurrency' => 15
            ];
        }

        /**
         * Get Basic Authorization
         * 
         * @return string
         */
        protected function getBasicAuthorization(){
            return 'Basic ' . base64_encode(urlencode($this->authIdentifier) . ':' . urlencode($this->authSecret));
        }

        /**
         * Get Bearer Authorization
         *
         * @return string
         */
        protected function getBearerAuthorization(){
            $token = $this->getAuthToken();

            if(!is_array($token) || !isset($token['token_type'], $token['access_token']))
                return '';

            return $token['token_type'] . ' ' . $token['access_token'];
        }

        /**
         * Set HTTP Client
         * 
         * @var bool $force
         * @return void
         */
        protected function setHttpClient($force = false){
            if($force == true || !$this->httpClient instanceof Client)
                $this->httpClient = new Client($this->configuration);
        }

        /**
         * Make a request
         * 
         * @var string $method
         * @var string $endpoint
         * @var mixed $parameters
         * @var array $headers
         * @var string $type
         * 
         * @return mixed
         */
        protected function request(string $method, string $endpoint, $parameters = null, array $headers = [], $type = 'json'){

            // Ensure that httpClient is set
            if(!$this->httpClient instanceof Client)
                $this->setHttpClient();

            // Ensure method is capitalized
            $method = strtoupper($method);

            // Check method
            if(!in_array($method, $this->validMethods)){
                throw new BankException("Invalid method. The method used to send your request needs to be one of the following: " . implode(', ', $this->validMethods));
            }

            // Set default request headers
            $requestHeaders = $this->defaultHeaders;

            // Set headers for this specific request
            if((is_array($headers) && count($headers) > 0) || is_null($headers))
                $requestHeaders = array_merge($requestHeaders, $headers);

            if(empty($requestHeaders['Authorization']))
                $requestHeaders['Authorization'] = $this->getBearerAuthorization();

            if(substr($endpoint, 0, 4) != 'http' && isset($this->endpoint)){
                // Make sure endpoint is up to date
                $endpoint = $this->endpoint . $endpoint;
            }

            // Overwrite auth_retries if not set
            if(!isset($this->configuration['auth_retries']))
                $this->configuration['auth_retries'] = 3;

            // Check to ensure that auth_retries_codes is an array
            if(!isset($this->configuration['auth_retries_codes']) || !is_array($this->configuration['auth_retries_codes'])){
                if(isset($this->configuration['auth_retries_codes']) && is_int($this->configuration['auth_retries_codes'])){
                    $this->configuration['auth_retries_codes'] = [$this->configuration['auth_retries_codes']];
                }
                else{
                    $this->configuration['auth_retries_codes'] = [500];
                }
            }

            $requestBody = '';
            if(in_array($method, ['POST', 'PUT', 'PATCH'])){
                if(isset($requestHeaders['Content-Type']) && strtolower($requestHeaders['Content-Type']) == 'application/json'){
                    $requestBody = json_encode($parameters);
                }
                else if(is_array($parameters)){
                    $requestBody = http_build_query($parameters);
                }
                else if(is_string($parameters)){
                    $requestBody = $parameters;
                }
            }
            else if($method == "GET" && count($parameters) > 0){
                // Add parameters to endpoint
                $endpoint = $this->generateGetEndpoint($endpoint, $parameters);
            }

            $request = new Request($method, $endpoint, $requestHeaders, $requestBody); 

            if($this->queueActive && (!isset($this->forceRequest) || $this->forceRequest == false)){
                $this->queue[] = $request;
                return $this;
            }

            do {
                try {
                    $response = $this->httpClient->send($request);
                    break;
                }
                catch(BadResponseException $e){
                    $response = $e->getResponse();

                    $this->currentTry++;

                    if($this->configuration['auth_retries'] != -1 && $this->currentTry > $this->configuration['auth_retries'])
                        break;

                    if(in_array($response->getStatusCode(), $this->configuration['auth_retries_codes'])){
                        return $this->request($method, $endpoint, $parameters, $headers, $type);
                    }
                    else
                        break;
                }
            } while(true);

            // Reset $currentTry
            $this->currentTry = 0;

            // Reset $forceRequest
            $this->forceRequest = false;

            // Reset configuration values
            $this->setConfiguration($this->temporaryConfiguration);

            return $this->generateReturn($response, $type);
        }

        /**
         * Start queue
         * 
         * @return self
         */
        public function queue(){
            $this->queueActive = true;

            return $this;
        }

        /**
         * Send all requests in batches
         * 
         * @param int $limit
         * 
         * @return array
         */
        public function get(int $limit = null){
            if($limit <= 0)
                $limit = null;

            if($limit == null){
                $requests = $this->queue;
                $this->queue = [];
            }
            else
                $requests = array_splice($this->queue, 0, $limit);

            if(count($requests) == 0)
                return [];

            $responses = Pool::batch($this->httpClient, $requests, [
                'concurrency' => $this->configuration['batch_concurrency']
            ]);

            $resp = [];
            foreach($responses as $response){
                if(!$response instanceof Response)
                    $response = $response->getResponse();
                
                $resp[] = $response->getBody()->getContents();
            }

            // Reset $queueActive
            $this->queueActive = false;
            
            return $resp;
        }

        /**
         * Generate mixed return based on type
         * 
         * @var string $response
         * @var string $type
         * 
         * @return mixed
         */
        private function generateReturn($response, $type){
            $function = 'return' . ucfirst(strtolower($type));
            if(strtolower($type) == 'raw'){
                return $response->getBody()->getContents();
            }
            else if(method_exists($this, $function)){
                return $this->{$function}($response->getBody()->getContents());
            }
            
            throw new BankException("Can't parse response. Make sure that the parsing method exists, or use raw.");
        }

        /**
         * Generate GET endpoint with parameters
         * 
         * @param string $endpoint
         * @param array|null $parameters
         * 
         * @return string
         */
        private function generateGetEndpoint(string $endpoint, $parameters){
            $parsedEndpoint = parse_url($endpoint);
            $endpoint = '';

            if(isset($parsedEndpoint['scheme'], $parsedEndpoint['host']))
                $endpoint .= $parsedEndpoint['scheme'] . '://' . $parsedEndpoint['host'];

            if(isset($parsedEndpoint['path']))
                $endpoint .= $parsedEndpoint['path'];

            $queryParts = [];
            if(isset($parsedEndpoint['query']))
                parse_str($parsedEndpoint['query'], $queryParts);
                

            if(is_array($parameters) && count($parameters) > 0){
                $queryParts = array_merge($queryParts, $parameters);
            }

            if(is_array($queryParts) && count($queryParts) > 0){
                $endpoint .= '?' . http_build_query($queryParts);
            }

            if(isset($parsedEndpoint['fragment']))
                $endpoint .= '#' .$parsedEndpoint['fragment'];

            return $endpoint;
        }

        /**
         * JSON return type
         * 
         * @param string $response
         * 
         * @return array
         */
        protected function returnJson($response){
            return json_decode($response, true);
        }

        /**
         * Check if response is successful
         * 
         * @param mixed $response
         * 
         * @return bool
         */
        protected function isSuccessful($response){
            return $response instanceof self || is_array($response);
        }

        /**
         * Get configuration values
         * 
         * @return array
         */
        public function getConfiguration(){
            return $this->configuration;
        }

        /**
         * Update configuration values
         * 
         * @var array $configuration
         * 
         * @return self
         */
        public function setConfiguration(array $configuration){
            // Check if change is needed
            if(is_array($this->configuration) && $this->configuration === $this->temporaryConfiguration && count($configuration) === 0){
                return $this;
            }

            // Limitations - make sure auth_retries are never less than -1.
            if(isset($configuration['auth_retries']) && $configuration['auth_retries'] < -1)
                $configuration['auth_retries'] = -1;

            if(is_array($this->configuration) && $configuration !== $this->temporaryConfiguration){
                $this->temporaryConfiguration = $this->configuration;
            }
            
            // Set connection configuration and merge with defaults
            $this->configuration = array_merge($this->getDefaultConfigurationValues(), $configuration);

            if($configuration !== $this->temporaryConfiguration && $this->temporaryConfiguration === NULL){
                $this->temporaryConfiguration = $this->configuration;
            }

            $this->setHttpClient();

            return $this;
        }

        /**
         * Update global configuration values
         * 
         * @var array $configuration
         * 
         * @return self
         */
        public function setGlobalConfiguration(array $configuration){
            // Check if change is needed
            if(is_array($this->configuration) && $this->configuration === $this->temporaryConfiguration && count($configuration) === 0){
                return $this;
            }

            // Set connection configuration and merge with defaults
            $this->temporaryConfiguration = $this->configuration = array_merge($this->getDefaultConfigurationValues(), $configuration);

            $this->setHttpClient();

            return $this;
        }

        /**
         * Get default configuration values
         * 
         * @return array
         */
        private function getDefaultConfigurationValues(){
            // Get default configuration for HTTP Client
            $defaultConfiguration = $this->getBaseDefaultConfigurationVariables();

            if(method_exists($this, 'getDefaultConfigurationVariables')){
                $defaultConfiguration = array_merge($defaultConfiguration, $this->getDefaultConfigurationVariables());
            }

            return $defaultConfiguration;
        }

     } 
