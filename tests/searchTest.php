<?php

use PHPUnit\Framework\TestCase;

/**
 * Octopus Search Test
 *
 */
class SearchTest extends TestCase
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
      'Designer',
      $mockContentPath
    );
    
    $expectedResults = array (
      0 => 
      array (
        'url' => '/file2',
        'title' => 'File2',
        'snippet' => 
        array (
          0 => '[...]     Für <span class="term">Designer</span>, Schriftsetzer, Layouter, Grafikenthusiasten und [...]',
        ),
      ),
    );

    $actualResults = $obj->find();

    $this->assertEquals($expectedResults, $actualResults);
  }
  
  public function testSearchWithCustomTitleCreationCallback()
  {
    $mockContentPath = dirname(__FILE__) . '/fixtures/content';
    $obj = new Radiergummi\Octopus\Search(
      'Designer',
      $mockContentPath
    );
    $obj->set('buildTitle', function($file) {
      return 'foo';
    });
    
    $expectedResults = array (
      0 => 
      array (
        'url' => '/file2',
        'title' => 'foo',
        'snippet' => 
        array (
          0 => '[...]     Für <span class="term">Designer</span>, Schriftsetzer, Layouter, Grafikenthusiasten und [...]',
        ),
      ),
    );

    $actualResults = $obj->find();

    $this->assertEquals($expectedResults, $actualResults);
  }
  
  public function testSearchWithCustomURLCreationCallback()
  {
    $mockContentPath = dirname(__FILE__) . '/fixtures/content';
    $obj = new Radiergummi\Octopus\Search(
      'Designer',
      $mockContentPath
    );
    $obj->set('buildUrl', function($file) {
      return 'foo';
    });
    
    $expectedResults = array (
      0 => 
      array (
        'url' => 'foo',
        'title' => 'File2',
        'snippet' => 
        array (
          0 => '[...]     Für <span class="term">Designer</span>, Schriftsetzer, Layouter, Grafikenthusiasten und [...]',
        ),
      ),
    );

    $actualResults = $obj->find();

    $this->assertEquals($expectedResults, $actualResults);
  }
  
  public function testSearchWithCustomSnippetCreationCallback()
  {
    $mockContentPath = dirname(__FILE__) . '/fixtures/content';
    $obj = new Radiergummi\Octopus\Search(
      'Designer',
      $mockContentPath
    );
    $obj->set('buildSnippet', function($match, $before, $after) {
      return 'foo';
    });
    
    $expectedResults = array (
      0 => 
      array (
        'url' => '/file2',
        'title' => 'File2',
        'snippet' => 
        array (
          0 => 'foo',
        ),
      ),
    );

    $actualResults = $obj->find();

    $this->assertEquals($expectedResults, $actualResults);
  }
}

