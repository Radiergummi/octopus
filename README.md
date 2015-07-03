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

Of course, you can always edit the default values in the `__construct()` method as they most likely stay the same for your entire project.

Available configuration settings are:

| Name                    | Type     | Description                                           | Default value                  |
|:------------------------|:---------|:------------------------------------------------------|:-------------------------------|
| `path`                  | string   | The path in which the files to search in are located  | `/path/to/search`              |
| `excludes`              | array    | Files to explicitly exclude from searching            | `['header.php', 'footer.php']` |
| `surroundingTextLength` | int      | The amount of words surrounding the term for snippets | `5`                            |
| `resultsPerFile`        | int      | The amount of results to gather from each file        | `0` (∞)                        |
| `buildUrl`              | Callable | A callback for building URLs to the file with results | null (default callback)        |

The last parameter is a bit less self-explainatory than the rest. Usually when searching, you want to provide a link to the page the result was found in. Using a database-powered CMS, that is a pretty standard task. If you are interested in using this library, though, you probably have your own, custom CMS and handle routing your way. That's fine! You can specify a callback for generating URLs, given the respective file as an `SPLFileInfo` object. The default callback will assume your files symbolize pages, and the `path` is the root directory of your public facing web server. So it builds URLs like so:  
`/public/subfolder1/page1.php` becomes `http://hostname.tld/subfolder1/page1`.
