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
  * Check if SBanken has any syntax errors to help troubleshoot any typos before pushing to production
  * 
  */
  public function testIsThereAnySyntaxError()
  {
    $SBanken = new SBanken($this->clientID, $this->clientSecret, [
        'debug' => true
    ]);
    $this->assertTrue(is_object($SBanken));
    unset($SBanken);
  }

  /**
   * Test configuration setup
   * 
   */
  public function testConfigurationSetup(){
    $SBanken = new SBanken($this->clientID, $this->clientSecret, [
        'debug' => true
    ]);
    $this->assertTrue(is_object($SBanken));
    unset($SBanken);
  }

  /**
   * Test getAuthToken
   * 
   */
  public function testGetAuthToken(){
    $SBanken = new SBanken($this->clientID, $this->clientSecret, [
        'debug' => true
    ]);
    $this->assertTrue(is_array($SBanken->getAuthToken()));
    unset($SBanken);
  }

  /**
   * Test getCustomer
   * 
   */
  public function testGetCustomer(){
    $SBanken = new SBanken($this->clientID, $this->clientSecret, [
        'debug' => true
    ]);
    $this->assertTrue(is_array($SBanken->getCustomer("1")));
    unset($SBanken);
  }

  /**
   * Test getAccounts
   * 
   */
  public function testGetAccounts(){
    $SBanken = new SBanken($this->clientID, $this->clientSecret, [
        'debug' => true
    ]);
    $this->assertTrue(is_array($SBanken->getAccounts("1")));
    unset($SBanken);
  }

  /**
   * Test getAccount
   * 
   */
  public function testGetAccount(){
    $SBanken = new SBanken($this->clientID, $this->clientSecret, [
        'debug' => true
    ]);
    $this->assertTrue(is_array($SBanken->getAccount("1", "2")));
    unset($SBanken);
  }

  /**
   * Test getTransactions
   * 
   */
  public function testGetTransactions(){
    $SBanken = new SBanken($this->clientID, $this->clientSecret, [
        'debug' => true
    ]);
    $this->assertTrue(is_array($SBanken->getTransactions("1", "2")));
    unset($SBanken);
  }

  /**
   * Test eInvoices
   * 
   */
  public function testEInvoices(){
    $SBanken = new SBanken($this->clientID, $this->clientSecret, [
        'debug' => true
    ]);
    $this->assertTrue(is_array($SBanken->getEInvoices("1", "ALL", 0, 1000)));
    unset($SBanken);
  }

  /**
   * Test transfer between owned accounts
   * 
   */
  public function testPostTransfer(){
    $SBanken = new SBanken($this->clientID, $this->clientSecret, [
        'debug' => true
    ]);
    $this->assertTrue(is_array($SBanken->postTransfer("1", "2", "3", "Text", 1.00)));
    unset($SBanken);
  }

  /**
   * Test payment of e-invoice
   * 
   */
  public function testPostEInvoice(){
    $SBanken = new SBanken($this->clientID, $this->clientSecret, [
        'debug' => true
    ]);
    $this->assertTrue(is_array($SBanken->postEInvoice("1", "2", "3")));
    unset($SBanken);
  }
}
