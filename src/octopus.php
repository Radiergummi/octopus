<?php
namespace Radiergummi\Octopus;

use \RecursiveDirectoryIterator;
use \RecursiveIteratorIterator;
class Search
{
	/**
	 * query
	 * holds the search query term
	 * 
	 * (default value: '')
	 * 
	 * @var string
	 * @access public
	 */
	public $query = '';
	
	/**
	 * results
	 * holds the search results
	 * 
	 * (default value: array())
	 * 
	 * @var array
	 * @access public
	 */
	public $results = array();

	/**
	 * filesToSearch
	 * holds all files to search in
	 * 
	 * (default value: '')
	 * 
	 * @var string
	 * @access public
	 * @static
	 */
	public static $filesToSearch = array();

	/**
	 * buildUrl
	 * holds the callback for building URLs to your search results
	 * 
	 * (default value: null)
	 * 
	 * @var Callable
	 * @access public
	 * @static
	 */
	public static $buildUrl = null;

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
	 * __construct function.
	 * setup settings for the search. You should load these from your configuration here,
	 * for example using my project Libconfig @ github.com/Radiergummi/libconfig :)
	 *459 087 893 hnq564
	 * @example $results = new Search('foo')->set('path', '/content/sites')->find();
	 * 
	 * @access public
	 * @param string $path (default: '')
	 * @param mixed $excludes (default: [])
	 * @param int $surroundingTextLength (default: 0)
	 * @param int $resultsPerFile (default: 0)
	 * @return $this
	 */
	public function __construct(
		$query,
		$path = '/path/to/search',
		$excludes = ['file1.php', 'file2.php'],
		$surroundingTextLength = 0,
		$resultsPerFile = 0,
		$buildUrl = null
	) {
		// the path to search in.
		if (empty(static::$path)) static::$path = $path;
		
		// files to explictly exclude from searching
		if (empty(static::$excludes)) static::$excludes = $excludes;
		
		// the amount of words around the search term to include in result snippets
		if (empty(static::$surroundingTextLength)) static::$surroundingTextLength = $surroundingTextLength;
		
		// the amount of results per file to be regarded
		if (empty(static::$resultsPerFile)) static::$resultsPerFile = $resultsPerFile;

		// the list of files to search in
		if (empty(static::$filesToSearch)) static::$filesToSearch = static::getFiles();
		
		if (empty($buildUrl)) static::$buildUrl = function($file) {
			// add filepath relative to base search directory
			$relativePath = substr($file->getPath() . DIRECTORY_SEPARATOR . $file->getFileName(), strlen(static::$searchpath));

			return '/' . substr($relativePath, 0, -strlen($file->getExtension()));
		};
		
		return $this;
	}
	
	/**
	 * set function.
	 * method for setting default options for searches
	 * 
	 * @access public
	 * @static
	 * @param string $key  the option name
	 * @param mixed $value  the options value
	 */
	public static function set($key, $value) {
		static::$$key = $value;
	}
	
	/**
	 * configure function.
	 * method for setting multiple default options
	 * 
	 * @access public
	 * @param array $options
	 * @return void
	 */
	public function configure($options) {
		foreach ($options as $key => $value) {
			$this->set($key, $value);
		}
	}

	/**
	 * find function.
	 * attempts to find a string in a directory and returns an array of results.
	 * 
	 * @access public
	 * @param mixed $query
	 * @return array
	 */
	public function find()
	{
		foreach(static::$filesToSearch as $file) {

			// skip excluded file
			if (in_array($file->getFileName(), static::$excludes)) continue;

			
			// search file for term
			if (stristr($content = strip_tags(nl2br(file_get_contents($file)), '<br><code><p>'), static::$query) !== false) {

				// call the callback for URL generation
				//$result['url'] = '/' . substr(end(static::$files), 0, -strlen(EXT));
				$result['url'] = call_user_func(static::buildUrl, $relativePath);
				
				// build name for resource
				$result['title'] = ucwords(str_replace('-', ' ', end(@explode('/', Config::get('app.uri')))));
			
				// generate snippet with search term
				if (preg_match_all('/((\s\S*){0,' . static::$surroundingTextLength . '})(' . $this->query . ')((\s?\S*){0,' . static::$surroundingTextLength . '})/im', $content, $matches, PREG_SET_ORDER)) {
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
				$this->results[] = $result;
			}
		}

		return $this->results;
	}
	
	/**
	 * getFiles function.
	 * retrieves all files within the speciefied directories as SPLFileInfo objects
	 *
	 * @access public
	 * @static
	 * @param array $directories  the directory paths to search
	 * @return array  the retrieved files
	 */
	public static function getFiles($directories)
	{
		$files = array();
		
		// iterate over each directory in the array
		foreach ((array) $directories as $directory) {

			// create a new recursive directory iterator for a path, skipping "." and ".."
			$directoryIterator = new RecursiveDirectoryIterator(
				$directory,
				RecursiveDirectoryIterator::SKIP_DOTS
			);
			
			// add each found file to the array of files in which to search for the query.
			foreach (new RecursiveIteratorIterator($directoryIterator) as $file) {
				$files[] = $file;
			}
		}
		
		return $files;
	}
}
