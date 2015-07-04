<?php
/**
 * Octopus Search Test
 *
 */
class SearchTest extends PHPUnit_Framework_TestCase
{
  public function testCreateObject()
  {
    $obj = new Radiergummi\Octopus\Search('foo');
    
    $this->assertInstanceOf('Radiergummi\Octopus\Search', $obj);
  }
  
  public function testSetConfigVar()
  {
    $obj = new Radiergummi\Octopus\Search('foo');
    
    $obj->set('path', dirname(__FILE__) . '/fixtures/content');
    
    $this->assertInstanceOf('Radiergummi\Octopus\Search', $obj);
  }
}
