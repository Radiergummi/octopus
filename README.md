# octopus
A super simple flat file search object in PHP.  

Octopus allows searching a flat file structure, for example in a database-less CMS or a small static page.



## Usage
To start a new search, create a new `Search` object:

```php
$search = new Search('term');
```

To create a set of results, use `find()`:

```php
$results = $search->find();
```

Why the additional step? Because when a new `Search` object is constructed, default parameters will be used for the search process. To alter them, you can now use the `set()` method to provide specific settings:

```php
$search->set('path', '/path/to/search');
```

These values will be stored statically, so you only need to do this once. To set multiple values at the same time, use `configure()`:

```php
$search->configure([
  'path' => '/path/to/search',
  'surroundingTextLength' => '5'
]);
```

Another possible way'd be to create a new Search object, specifying the settings as arguments:  

```php
$search new Search(
  '/path/to/search',
  '5'
);
```

Of course, you can always edit the default values in the `__construct()` method as they most likely stay the same for your entire project.


## Configuration

Available configuration settings are:

| Name                    | Type     | Description                                           | Default value                  |
|:------------------------|:---------|:------------------------------------------------------|:-------------------------------|
| `path`                  | string   | The path in which the files to search in are located  | `/path/to/search`              |
| `excludes`              | array    | Files to explicitly exclude from searching            | `['header.php', 'footer.php']` |
| `surroundingTextLength` | int      | The amount of words surrounding the term for snippets | `5`                            |
| `resultsPerFile`        | int      | The amount of results to gather from each file        | `0` (∞)                        |
| `buildUrl`              | Callable | A callback for building URLs to the file with results | null (default callback)        |


## URL generating callback functions

The last parameter is a bit less self-explainatory than the rest. Usually when searching, you want to provide a link to the page the result was found in. Using a database-powered CMS, that is a pretty standard task. If you are interested in using this library, though, you probably have your own, custom CMS and handle routing your way. That's fine! You can specify a callback for generating URLs, given the respective file as an `SPLFileInfo` object.  
That provides you with, for example, the files name, its absolute path, its extension, etc. The default callback coming with *Octopus* will assume your files symbolize pages, and `path` is the root directory of your public web server. So it builds URLs like this:  
`/public/subfolder1/page1.php` becomes `http://hostname.tld/subfolder1/page1`.

Now there is much room for improvement - say, adding the fragment identifier of the nearest heading (foo.org/page#fragment) would be nice. Or maybe flat file structure drivers, or result sorting, ... .

## A basic real-world results function

```php
/**
 * get search results for a search term
 * 
 * @param string $searchTerm    the term to search for
 * @return string               the result list
 */
function getResults($searchTerm)
{
  // create the results set
  $results = (new Search($searchTerm))->find();
  
  // if the result list is empty, show a "no results" message
  if (empty($results)) return 'No results for' . $searchTerm;
  
  // collect the result list text
  $html = '';
  
  // iterate over results
  foreach ($results as $result => $resultProperties) {
    
    // wrap it in a block level anchor element
    $html .= '<a href="' . $resultProperties['url'] . '">';
    
      // add the page title
      $html .= '<strong>' . $resultProperties['title'] . '</strong>';

      // if we have a search snippet for the result, print it
      if (! empty($resultProperties['snippet'])) {
        $html .= '<p>' . $resultProperties['snippet'] . '</p>';
      }
    
    // close the anchor element
    $html .= '</a>';
  }
  
  // return the result list
  return $html;
}
```
