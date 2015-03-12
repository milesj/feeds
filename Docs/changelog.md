# Changelog #

*These logs may be outdated or incomplete.*

## 3.0.1 ##

* Fixed a bug where RSS parsing fails when the URL is down
* Updated to TypeConverter 1.3
* Added capturing of source variable from the feed
* Added support for an alternate date format in the Aggregator model

## 2.2.3 ##

* Fixed a bug where RSS parsing fails when the URL is down

## 3.0 ##

* Updated to CakePHP 2.0 (not backwards compatible with 1.3)
* Updated to support CakeResponse from HttpSocket
* Minor cleanup

## 2.2 ##

* Added root overwriting via the "root" setting
* Added custom attribute value extraction
* Added individual URL caching to speed up the parsing time when a URL is used multiple times, or parsing multiple feeds
* Fixed a bug if feed array wasn't set
* Made private members protected

## 2.1 ##

* Fixed some problems parsing atom feeds
* Will now intelligently find elements and extract values

## 2.0 ##

* Converted to a DataSource from a Component
* Added a sorting mechanism by creating a "sort" setting
* Added support for single model/feed relationships
* Added example Models

## 1.4 ##

* Upgraded to PHP 5 only

## 1.3 ##

* Moved the types array from parseType into a property called $typeMap
* Reworked how elements would be selected when parsed a feeds item
* Increased performance and speed

## 1.2 ##

* Added a $grabElements property to grab additional elements from a single feed entry
* Fixed notice errors that would appear when using empty feeds
* Added additional checking for the link element

## 1.1 ##

* Added the cache configuration in __construct() so that it's automatically called
* Reworked the caching so that it caches the whole result, not just a truncated one
* Added a _truncate() method to limit the results

## 1.0 ##

* First initial release of the Feed Aggregator Component
