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
    
    $mockContentPath = dirname(__FILE__) . '/fixtures/content';
    
    $obj->set('path', $mockContentPath);
    
    $this->assertEquals($mockContentPath, Radiergummi\Octopus\Search::$path);
  }
  
  public function testSetMultipleConfigVars()
  {
    $obj = new Radiergummi\Octopus\Search('foo');
    
    $mockContentPath = dirname(__FILE__) . '/fixtures/content';
    $mockExcludes = array('header.php', 'footer.php', 'search.php');
    
    $obj->configure(array('path' => $mockContentPath, 'excludes' => $mockExcludes));
    
    $this->assertEquals($mockContentPath, Radiergummi\Octopus\Search::$path);
    $this->assertEquals($mockExcludes, Radiergummi\Octopus\Search::$excludes);
  }

  public function testConstructObjectWithConfigVars()
  {
    $mockContentPath = dirname(__FILE__) . '/fixtures/content';
    $mockExcludes = array('header.php', 'footer.php', 'search.php');

    $obj = new Radiergummi\Octopus\Search(
      'foo',
      $mockContentPath,
      $mockExcludes
    );

    $this->assertEquals($mockContentPath, Radiergummi\Octopus\Search::$path);
    $this->assertEquals($mockExcludes, Radiergummi\Octopus\Search::$excludes);
  }

  public function testSearchConcluded()
  {
    $mockContentPath = dirname(__FILE__) . '/fixtures/content';
    $obj = new Radiergummi\Octopus\Search(
      'foo',
      $mockContentPath
    );
    
    $results = $obj->find();
    echo $results;
    $this->assertInternalType ('array', $results);
  }
}

