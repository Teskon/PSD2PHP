<?php
    namespace Teskon\PSD2PHP;

    /**
     * 
     * PSD2PHP is a module based script that is used to connect to various banks to implement the new Banking APIs into your application.
     * 
     * PSD2 (Payment Service Directive) is a a new banking directive that is making the Bank information available for you to implement into your application.
     * This will allow you to see your bank balance, and make transactions from your own website (and much more).
     * Fore more information about PSD2 you can read about it here: https://ec.europa.eu/info/law/payment-services-psd-2-directive-eu-2015-2366_en
     * 
     * Read the readme to know which banks are supported. More banks will become available in the future when their APIs are made public and we add them.
     * If you want to add your own bank you can add a module following the examples in the readme. Your code will be reviewed and added to the master branch if it is accepted.
     * 
     * @author Kristian Auestad <kristianaue@gmail.com>
     */

     class PSD2PHP {
         /**
          * Bank instance
          *
          * @var mixed $bank
          */
          private $bank = null;
        /**
         * Main constructor for PSD2PHP.
         * 
         * @param string $bank
         * @param array $configuration
         */
        public function __construct(string $bank, ...$configuration){
            // Capitalize first character of $bank
            $bank = ucfirst($bank);

            if($bank == "Test"){
                return $this;
            }

            $bank = __NAMESPACE__ . '\\Banks\\' . $bank . '\\' . $bank;

            $this->bank = new $bank(...$configuration);
        }

        /**
         * Call method
         * 
         * @param string $function
         * @param array $parameters
         * 
         * @return mixed
         */
        public function __call(string $function, array $parameters){
            return $this->bank->{$function}(...$parameters);
        }

        /**
         * Get variable
         * 
         * @param string $variable
         * 
         * @return mixed
         */
        public function __get(string $variable){
            return $this->bank->{$variable};
        }

        /**
         * Set variable
         * 
         * @param string $variable
         * @param mixed $value
         * 
         * @return void
         */
        public function __set(string $variable, $value){
            $this->bank->{$variable} = $value;
        }
     }