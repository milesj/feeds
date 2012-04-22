# Feeds v3.0.1 #

Feeds is a CakePHP plugin that provides the functionality to parse external RSS feeds into your application through the model layer.

This version is only compatible with CakePHP 2.0.

## Compatibility ##

* v2.x - CakePHP 1.3
* v3.x - CakePHP 2.0

## Requirements ##

* PHP 5.2, 5.3
* SimpleXML - http://php.net/manual/book.simplexml.php

## Features ##

* A DataSource that fetches a feed (HTTP Request) and parses it into an array
* Supports RSS 2.0, RSS 1.0 (RDF) and Atom feed types
* Can aggregrate multiple feeds into a single result
* Uses the HttpSocket library to request the feed
* Built in cache support (with a little configuration)
* Has a max return limit to cut down the returned arrays size
* Can pass in custom fields (elements) to extract from the feed

## Documentation ##

Thorough documentation can be found here: http://milesj.me/code/cakephp/feeds