# Versatile Router

A frontend URL router for [Symphony CMS](http://getsymphony.com).

## System Requirements

PHP version >= 5.3.

## Preference Settings

Versatile Router has two settings on Symphony's System->Preferences page.

### Disable standard routing

By default, rerouting/redirection is performed only when the source route does not match any page. This can be changed by disabling Symphony's standard routing, in which case you will have to define routes for all of the pages on your website.

### Routes file path

Routes are defined in a file in the Workspace directory which by default is `routes.php`. The path (relative to `workspace`) may be changed.

## Routes

Routes are defined in a PHP file. See Preference Settings for more details.

Define a route:

`route(<source path>, <destination path>, <optional conditions>);`


Define a route which responds to GET requests only:

`get(<source path>, <destination path>, <optional conditions>);`


Define a route which responds to POST requests only:

`post(<source path>, <destination path>, <optional conditions>);`

Define a redirection route. The destination needs to be a full URL:

`redirect('<source path>', '<destination URL>', '<optional conditions>', '<optional status code>');`



### Parameters

Paths may contain parameter names, where the name is prefixed by a colon. In the destination route, the name must be enclosed in braces.

Example route with parameters:

`route('/article/:title/', '/article-main/{:title}/');`

### Conditions

The contents of the PHP arrays `$_SERVER` and `$_REQUEST` can be tested with conditions. Parameters can be filtered using `when_match`.

The conditions available are:

`when_set(<variable>)` returns *true* if variable exists;

`when_not_set(<variable>)` returns *true* if variable does not exist;

`when_is(<variable>)` returns *true* if variable exists and is non-null;

`when_is_not(<variable>)` returns *true* if variable is null or non-existent;

`when_equal(<variable>, <value>)` returns *true* if variable holds specified value;

`when_not_equal(<variable>, <value>)` returns *true* if variable does not hold specified value;

`when_match(<variable>, <regular expression>)` returns *true* if variable matches [PCRE regex pattern](http://php.net/manual/en/book.pcre.php).

A single condition or an array of conditions may be used.

### Wildcards

An asterisk in the source path will match any character except for a period.

### Route Groups

A group of routes with common conditions can be given.

`group(<conditions>, function(){<set of routes>});`

## Routing Examples

Redirect from HTTP to HTTPS connection:

`redirect('/buy-something/, 'https://{HTTP_HOST}/buy-something/', when_not('HTTPS'));`

Group for AJAX requests. The values 'article-page' and 'comments-page' are from the GET query string:

    group(
    	when_match('CONTENT_TYPE', '/^application\/x-www-form-urlencoded/'),
    	function(){
    		get('/article/:title/', '/article-ajax-page/{:title}/', when_is('article-page')); 
    		get('/article/:title/', '/article-ajax-comments/{:title}/', when_is('comments-page')); 
    		post('/article/:title/', '/ajax-comments/{:title}/');
    	}
    );

