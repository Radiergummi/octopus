<?php
namespace Radiergummi\Octopus;

use \RecursiveDirectoryIterator;
use \RecursiveIteratorIterator;
class Octopus
{
	/**
	 * query
	 * 
	 * (default value: '')
	 * 
	 * @var string
	 * @access public
	 * @static
	 */
	public static $query = '';

	/**
	 * directory
	 * 
	 * (default value: '')
	 * 
	 * @var string
	 * @access public
	 * @static
	 */
	public static $directory = '';

	/**
	 * files
	 * 
	 * (default value: array())
	 * 
	 * @var array
	 * @access public
	 * @static
	 */
	public static $files = array();

	/**
	 * searchpath
	 * 
	 * (default value: array())
	 * 
	 * @var array
	 * @access public
	 * @static
	 */
	public static $searchpath = array();

	/**
	 * excludes
	 * 
	 * (default value: array())
	 * 
	 * @var array
	 * @access public
	 * @static
	 */
	public static $excludes = array();
	
	/**
	 * results
	 * 
	 * (default value: array())
	 * 
	 * @var array
	 * @access public
	 * @static
	 */
	public static $results = array();

	/**
	 * surroundingTextLength
	 * 
	 * (default value: 0)
	 * 
	 * @var int
	 * @access public
	 * @static
	 */
	public static $surroundingTextLength = 0;

	/**
	 * resultsPerFile
	 * 
	 * (default value: 0)
	 * 
	 * @var int
	 * @access public
	 * @static
	 */
	public static $resultsPerFile = 0;

	/**
	 * setup function.
	 * setup settings for the search. You should load these from your configuration here,
	 * for example using my project Libview @ github.com/Radiergummi/libview :)
	 * 
	 * @access public
	 * @static
	 * @param string $path (default: '')
	 * @param mixed $excludes (default: [])
	 * @param int $surrounding (default: 0)
	 * @param int $results (default: 0)
	 * @param string $extension (default: '')
	 * @return void
	 */
	public static function setup(
		$path = '',
		$excludes = [],
		$surrounding = 0,
		$results = 0,
		$extension = ''
	) {
		// the path to search in.
		static::$searchpath = (! empty($path) ? $path : '/path/to/search');
		static::$excludes = (! empty($excludes) ? $excludes : ['file1.php', 'file2.php']);
		static::$surroundingTextLength = (! empty($surrounding) ? $surrounding : 10);
		static::$resultsPerFile = (! empty($results) ? $results : 0);
	}

	/**
	 * find function.
	 * attempts to find a string in a directory and returns an array of results.
	 * 
	 * @access public
	 * @static
	 * @param mixed $query
	 * @return array
	 */
	public static function find($query)
	{
		// setup the configuration parameterss, if not done already
		if (empty(static::$searchpath)) static::setup();
		
		// set query to class var
		static::$query = $query;
		
		// iterate over specified folder, skipping . and .. directories
		foreach(new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator(
				static::$searchpath,
				RecursiveDirectoryIterator::SKIP_DOTS
			)
		) as $file) {
			// skip excluded file
			if (in_array($file->getFileName(), static::$excludes)) continue;

			// add filepathpath relative to base search directory
			static::$files[] = substr($file->getPath() . DS . $file->getFileName(), strlen(static::$searchpath));
			
			// search file for term
			if (stristr($content = strip_tags(nl2br(file_get_contents($file)), '<br><code><p>'), static::$query) !== false) {

				// build URL to resource
				$result['url'] = '/' . substr(end(static::$files), 0, -strlen(EXT));
				
				// build name for resource
				$result['title'] = ucwords(str_replace('-', ' ', end(@explode('/', Config::get('app.uri')))));
			
				// generate snippet with search term
				if (preg_match_all('/((\s\S*){0,' . static::$surroundingTextLength . '})(' . static::$query . ')((\s?\S*){0,' . static::$surroundingTextLength . '})/im', $content, $matches, PREG_SET_ORDER)) {
					// limit results per file
					$resultLimit = (static::$resultsPerFile === 0 ? count($matches) : static::$resultsPerFile);

					for ($i = 0; $i < $resultLimit; $i++) {
						if (!empty($matches[$i][3])) {
							$result['hits'][] = vsprintf('[...] %s<span class="term">%s</span>%s [...]', [$matches[$i][1], $matches[$i][3], $matches[$i][4]]);
						} else {
							$result['hits'][] = 'keine Treffer';
						}
					}
				}

				// if found, append to results
				static::$results[] = $result;
			}
		}

		return static::$results;
	}
}
