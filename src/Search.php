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
     * path
     * holds the base search path
     *
     * (default value: '')
     *
     * @var string
     * @access public
     * @static
     */
    public static $path = '';

    /**
     * filesToSearch
     * holds all files to search in
     *
     * (default value: array())
     *
     * @var array
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
     * buildSnippet
     * holds the callback for building result excerpts
     *
     * (default value: null)
     *
     * @var Callable
     * @access public
     * @static
     */
    public static $buildSnippet = null;

    /**
     * buildTitle
     * holds the callback for building result titles
     *
     * (default value: null)
     *
     * @var Callable
     * @access public
     * @static
     */
    public static $buildTitle = null;

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
     * @param string $path (default: '/')              the path to search
     * @param mixed $excludes (default: [])            the files to exclude from searching
     * @param int $surroundingTextLength (default: 5)  the amount of words of surrounding text in snippets
     * @param int $resultsPerFile (default: 0)         the amount of result snippets to build for each file
     * @param Callable $buildTitle (default: null)     the callback for building result titles
     * @param Callable $buildUrl (default: null)       the callback for building URLs to results
     * @param Callable $buildSnippet (default: null)   the callback for building result excerpts
     */
    public function __construct(
        $query,
        $path = '/',
        $excludes = array('header.php', 'footer.php'),
        $surroundingTextLength = 5,
        $resultsPerFile = 0,
        $buildTitle = null,
        $buildUrl = null,
        $buildSnippet = null
    ) {
        // the query for this search
        $this->query = $query;
        
        // the path to search in
        if (empty(static::$path)) {
            static::$path = $path;
        }
        
        // files to explictly exclude from searching
        if (empty(static::$excludes)) {
            static::$excludes = $excludes;
        }
        
        // the amount of words around the search term to include in result snippets
        if (empty(static::$surroundingTextLength)) {
            static::$surroundingTextLength = $surroundingTextLength;
        }
        
        // the amount of results per file to be regarded
        if (empty(static::$resultsPerFile)) {
            static::$resultsPerFile = $resultsPerFile;
        }
        
        // the callback for building the result links
        if (empty($buildUrl)) {
            static::$buildUrl = function ($file) {
                $path = Search::$path;
                
                // add filepath relative to base search directory
                $relativePath = substr($file->getPath() . DIRECTORY_SEPARATOR . $file->getFileName(), strlen($path));
    
                // return the URI for the file path without the file extension
                // for example: (file) "/public/foo/bar/page-name.php" (uri) "/foo/bar/page-name"
                return substr($relativePath, 0, -strlen('.' . $file->getExtension()));
            };
        } else {
            static::$buildUrl = $buildUrl;
        }
        
        // the callback for building the snippet text
        if (empty($buildSnippet)) {
            static::$buildSnippet = function ($match, $beforeMatch, $afterMatch) {
                return vsprintf(
                    // the snippet string
                        '[...] %s<span class="term">%s</span>%s [...]',
                    array(
                            // the text before the term
                            $beforeMatch,

                            // the term itself as matched in the text
                            $match,

                            // the text after the term
                            $afterMatch
                        )
                );
            };
        } else {
            static::$buildSnippet = $buildSnippet;
        }
        
        
        // the callback for building the result title
        if (empty($buildTitle)) {
            static::$buildTitle = function ($file) {

                // build title for resource by trimming of the file extension,
                // replacing dashes with whitespace and uppercasing words.
                return ucwords(str_replace('-', ' ', substr($file->getFilename(), 0, -strlen('.' . $file->getExtension()))));
            };
        } else {
            static::$buildTitle = $buildTitle;
        }
    }
    
    /**
     * set function.
     * method for setting default options for searches
     *
     * @access public
     * @param string $key  the option name
     * @param mixed $value  the options value
     */
    public function set($key, $value)
    {
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
    public function configure($options)
    {
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
     * @return array        the search results
     */
    public function find()
    {
        // the list of files to search in
        if (empty(static::$filesToSearch)) {
            static::$filesToSearch = static::getFiles(static::$path);
        }

        // iterate over all files
        foreach (static::$filesToSearch as $file) {
            // if a file is defined as to be excluded, skip this loop iteration
            if (in_array($file->getFileName(), static::$excludes)) {
                continue;
            }
            
            // search the file content for the term. To avoid returning html
            // tags, everything except line breaks, code and paragraphs gets
            // stripped out before the content is parsed.
            if (stristr($content = strip_tags(nl2br(file_get_contents($file)), '<br><code><p>'), $this->query) !== false) {
                // call the callback for URL generation
                $result['url'] = call_user_func(static::$buildUrl, $file);

                // call the callback for title generation
                $result['title'] = call_user_func(static::$buildTitle, $file);

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
                            $result['snippet'][] = call_user_func_array(static::$buildSnippet, array(
                                $matches[$i][3],
                                $matches[$i][1],
                                $matches[$i][4]
                            ));
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
                if (is_readable($file)) {
                    $files[] = $file;
                }
            }
        }
        
        // return all found files
        return $files;
    }
}
