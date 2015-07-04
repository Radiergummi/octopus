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
	 * Constructs a new search object
	 *
	 * @example $results = new Search('foo')->set('path', '/content/sites')->find();
	 * 
	 * @access public
	 * @param string $path (default: '')								the path to search
	 * @param mixed $excludes (default: [])							the files to exclude from searching
	 * @param int $surroundingTextLength (default: 0)		the amount of words of surrounding text in snippets
	 * @param int $resultsPerFile (default: 0)					the amount of result snippets to build for each file
	 * @param Callable $buildUrl (default: null)				the callback for building URLs to results
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
		
		// the callback for
		if (empty($buildUrl)) {
			static::$buildUrl = function($file) {
				// add filepath relative to base search directory
				$relativePath = substr($file->getPath() . DIRECTORY_SEPARATOR . $file->getFileName(), strlen(static::$searchpath));
	
				// return the URI for the file path without the file extension
				// for example: (file) "/public/foo/bar/page-name.php" (uri) "/foo/bar/page-name"
				return '/' . substr($relativePath, 0, -strlen($file->getExtension()));
			};
		}
		
		else {
			static::$buildUrl = $buildUrl;
		}
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

		// return the current search object for chaining
		return $this;
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
		
		// return the current search object for chaining
		return $this;
	}

	/**
	 * find function.
	 * attempts to find a string in a directory and returns an array of results.
	 * 
	 * @access public
	 * @return array		the search results
	 */
	public function find()
	{
		foreach(static::$filesToSearch as $file) {

			// if a file is defined as to be excluded, skip this loop iteration
			if (in_array($file->getFileName(), static::$excludes)) continue;
			
			// search the file content for the term. To avoid returning html
			// tags, everything except line breaks, code and paragraphs gets 
			// stripped out before the content is parsed.
			if (stristr($content = strip_tags(nl2br(file_get_contents($file)), '<br><code><p>'), static::$query) !== false) {

				// call the callback for URL generation
				$result['url'] = call_user_func(static::buildUrl, $file);
				
				// build name for resource by trimming of the file extension,
				// replacing dashes with whitespace and uppercasing words.
				// NOTE: This should be done within a callback, too.
				$result['title'] = ucwords(str_replace('-', ' ', substr($file->getFilename(), 0, -strlen($file->getExtension))));
			
				// generate snippet with search term
				if (preg_match_all(
					// this regex captures the words around the search term, if present.
					'/((\s\S*){0,' . static::$surroundingTextLength . '})(' . $this->query . ')((\s?\S*){0,' . static::$surroundingTextLength . '})/im',
					
					// the prepared file content string
					$content,
					
					// the return variable name
					$matches,
					
					// return matches in associative array
					PREG_SET_ORDER
				)) {
					// limit results per file
					$resultLimit = (static::$resultsPerFile === 0 ? count($matches) : static::$resultsPerFile);

					// iterate over result snippets
					for ($i = 0; $i < $resultLimit; $i++) {
						
						// if we have a match for the term in the file text
						if (! empty($matches[$i][3])) {
							
							// add a new snippet to the result
							$result['snippet'][] = vsprintf(
								
								// the snippet string
								'[...] %s<span class="term">%s</span>%s [...]',
								
								// the text before the term
								[$matches[$i][1],
								
								// the term itself as matched in the text
								$matches[$i][3],
								
								// the text after the term
								$matches[$i][4]]
							);
						}
					}
				}

				// if found, append to results
				$this->results[] = $result;
			}
		}

		// return all results
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
			
			// add each found file to the array of files in which to search for the query,
			// if the file is actually readable
			foreach (new RecursiveIteratorIterator($directoryIterator) as $file) {
				if (is_readable($file)) $files[] = $file;
			}
		}
		
		// return all found files
		return $files;
	}
}
