<?php
    namespace Teskon\PSD2NO\Banks;
    use GuzzleHttp\Client;
    use Teskon\PSD2NO\Exceptions\Banks\BankException;
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
         * Default constructor for storing configuration variables
         * 
         * @var string $authIdentifier
         * @var string $authSecret
         */
        public function __construct(string $authIdentifier, string $authSecret, array $configuration = []){
            $this->authIdentifier = $authIdentifier;
            $this->authSecret = $authSecret;

            // Set default configuration for HTTP Client
            $defaultConfiguration = $this->getBaseDefaultConfigurationVariables();

            if(method_exists($this, 'getDefaultConfigurationVariables')){
                $defaultConfiguration = array_merge($defaultConfiguration, $this->getDefaultConfigurationVariables());
            }
            
            // Set connection configuration and merge with defaults
            $this->configuration = array_merge($defaultConfiguration, $configuration);

            // Set HTTP Client
            $this->setHttpClient();

            if(method_exists($this, 'getAuthToken')){
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
                'track_redirects' => false
            ];
        }

        /**
         * Get Basic Authorization
         * 
         * @return string
         */
        protected function getBasicAuthorization(){
            return 'Basic ' . base64_encode($this->authIdentifier . ':' . $this->authSecret);
        }

        /**
         * Get Bearer Authorization
         *
         * @return string
         */
        protected function getBearerAuthorization(){
            return 'Bearer ' . $this->token;
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

            // Check type of parameters
            if(is_array($parameters)){
                $parameters = ['form_params' => $parameters];
            }
            else if(is_string($parameters)){
                $parameters = ['body' => $parameters];
            }
            else if(is_null($parameters)){
                $parameters = [];
            }
            else{
                throw new BankException("Invalid type. Parameters sent with the request has to be array, string or null.");
            }

            // Set headers for this specific request
            if((is_array($headers) && count($headers) > 0) || is_null($headers))
                $parameters['headers'] = array_merge($this->defaultHeaders, $headers);

            if(empty($parameters['headers']['Authorization']))
                $parameters['headers']['Authorization'] = $this->getBearerAuthorization();

            // Make sure endpoint is up to date
            $parameters['base_uri'] = $this->endpoint;

            $parameters['debug'] = true;
            
            try {
                $response = $this->httpClient->request($method, $endpoint, $parameters);
            }
            catch(BadResponseException $e){
                $response = $e->getResponse();
            }

            return $this->generateReturn($response, $type);
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
         * JSON return type
         * 
         * @var string $response
         * 
         * @return array
         */
        protected function returnJson($response){
            return json_decode($response, true);
        }

     } 
