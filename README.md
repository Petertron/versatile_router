# Versatile Router (version 0.2)

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

Paths may contain parameter names, where the name is prefixed by a colon. In the destination route, the name must be enclosed in braces. Parameter names may contain alphanuneric characters and underscores only.

Example route with parameters:

`route('/article/:title/', '/article-page/{:title}/');`

Parameters may be added to Symphony's parameter pool by placing them in square brackets at the end of the destination route. For example:

`get('/articles/:page_num/', '/articles-page/[:page_num]', array(when_number.':page_num'))`

`get('/articles/:category/:page_num/', '/articles-page/[:category :page_num]', array(when_number.':page-num'))`

### Conditions

The values of URL parameters and the contents of the PHP arrays `$_SERVER` and `$_REQUEST` can be tested with conditions. Conditions are specified using an array.

The conditions available are:

`when_present.'<variable>'` returns *true* if variable exists;

`when_not_present.'<variable>'` returns *true* if variable does not exist;

`when_true.'<variable>'` returns *true* if variable exists and is non-null;

`when_not_true.'<variable>'` returns *true* if variable is null or non-existent;

`when_number.'<variable>'` returns *true* if variable is a number

`when_not_number.'<variable>'` returns *true* if variable is not a number

`when_equal.'<variable> <value>'` returns *true* if variable holds specified value;

`when_not_equal.'<variable> <value>'` returns *true* if variable does not hold specified value;

`when_match.'<variable> <regular expression>'` returns *true* if variable matches [PCRE regex pattern](http://php.net/manual/en/book.pcre.php).

`when_no_match.'<variable> <regular expression>'` returns *true* if variable does not match [PCRE regex pattern](http://php.net/manual/en/book.pcre.php).

Example routes with conditions:

`get('/article/:number/', '/article-page/:number/', array(when_number.':number'))`;

`redirect('/buy-something/, 'https://{HTTP_HOST}/buy-something/', array(when_not_true.'HTTPS');`

### Wildcards

An asterisk in the source path will match any character except for a period.

### Route Groups

A group of routes with common conditions can be given.

`group(function(){<set of routes>}, <conditions>);`

Example group for AJAX requests. The values 'article-page' and 'comments-page' are from the GET query string:

    group(
    	function(){
    		get('/article/:title/', '/article-ajax-page/{:title}/', array(when_true.'article-page')); 
    		get('/article/:title/', '/article-ajax-comments/{:title}/', array(when_true.'comments-page')); 
    		post('/article/:title/', '/ajax-comments/{:title}/');
    	},
    	array(when_match.'CONTENT_TYPE /^application\/x-www-form-urlencoded/')
    );