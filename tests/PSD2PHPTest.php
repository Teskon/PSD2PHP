<?php 
use PHPUnit\Framework\TestCase;
use Teskon\PSD2NO\PSD2NO;

/**
*  Class used to test main PSD2NO Class for Syntax Errors
*
*  @author Kristian Auestad <Kristian Auestad>
*/
class PSD2NOTest extends TestCase
{
	
  /**
  * Check if PSD2NO has any syntax errors to help troubleshoot any typos before pushing to production
  * 
  */
  public function testIsThereAnySyntaxError()
  {
    $var = new PSD2NO("Test");
    $this->assertTrue(is_object($var));
    unset($var);
  }
}
