<?php 
use PHPUnit\Framework\TestCase;
use Teskon\PSD2PHP\PSD2PHP;

/**
*  Class used to test main PSD2PHP Class for Syntax Errors
*
*  @author Kristian Auestad <Kristian Auestad>
*/
class PSD2PHPTest extends TestCase
{
	
  /**
  * Check if PSD2PHP has any syntax errors to help troubleshoot any typos before pushing to production
  * 
  */
  public function testIsThereAnySyntaxError()
  {
    $var = new PSD2PHP("Test");
    $this->assertTrue(is_object($var));
    unset($var);
  }
}
