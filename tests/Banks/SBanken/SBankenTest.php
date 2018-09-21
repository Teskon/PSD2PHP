<?php 
use PHPUnit\Framework\TestCase;
use Teskon\PSD2PHP\PSD2PHP;
use Teskon\PSD2PHP\Banks\SBanken\SBanken;

/**
*  Class used to test SBanken Class for Syntax Errors and such
*
*  @author Kristian Auestad <Kristian Auestad>
*/
class SBankenTest extends TestCase
{

  /**
   * ClientID used to connect with SBanken
   * 
   * @var string $clientID
   */
  private $clientID = "";

  /**
   * ClientSecret used to connect with SBanken
   * 
   * @var string $clientSecret
   */
  private $clientSecret = "";

  /**
   * Class used to store SBanken integration
   * 
   * @var SBanken $SBanken
   */
  private $SBanken;

  /**
   * SBankenTest constructor
   * 
   */
  public function __construct(){
    $this->SBanken = new SBanken($this->clientID, $this->clientSecret, [
      // Activate debug in testing script
      'debug' => true
    ]);
  }
	
  /**
  * Check if SBanken has any syntax errors to help troubleshoot any typos before pushing to production
  * 
  */
  public function testIsThereAnySyntaxError()
  {
	$this->assertTrue(is_object($this->SBanken));
	unset($SBanken);
  }

  /**
   * Test configuration setup
   * 
   */
  public function testConfigurationSetup(){
    $this->assertTrue(is_object($this->SBanken));
    unset($SBanken);
  }

  /**
   * Test getAuthToken
   * 
   */
  public function testGetAuthToken(){
    $this->assertTrue(is_array($this->SBanken->getAuthToken()));
	  unset($SBanken);
  }

  /**
   * Test getCustomer
   * 
   */
  public function testGetCustomer(){
    $this->assertTrue(is_array($this->SBanken->getCustomer("1")));
	  unset($SBanken);
  }

  /**
   * Test getAccounts
   * 
   */
  public function testGetAccounts(){
    $this->assertTrue(is_array($this->SBanken->getAccounts("1")));
	  unset($SBanken);
  }

  /**
   * Test getAccount
   * 
   */
  public function testGetAccount(){
    $this->assertTrue(is_array($this->SBanken->getAccount("1", "2")));
	  unset($SBanken);
  }

  /**
   * Test getTransactions
   * 
   */
  public function testGetTransactions(){
    $this->assertTrue(is_array($this->SBanken->getTransactions("1", "2")));
	  unset($SBanken);
  }

  /**
   * Test eInvoices
   * 
   */
  public function testEInvoices(){
    $this->assertTrue(is_array($this->SBanken->getEInvoices("1", "ALL", 0, 1000)));
	  unset($SBanken);
  }
}
