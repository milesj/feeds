# Feeds Plugin v2.1 #

Feeds is a CakePHP plugin that contains the functionality to parse external RSS feeds into your application through the model layer.

## Requirements ##

* CakePHP 1.2.x, 1.3.x
* PHP 5.2.x
* SimpleXML

## Features ##

* A datasource that fetches a feed (HTTP Request) and parses it into an array
* Supports RSS 2.0, RSS 1.0 (RDF) and Atom feed types
* Can aggregrate multiple feeds into a single result
* Uses the HttpSocket library to request the feed
* Built in cache support (with a little configuration)
* Has a max return limit to cut down the returned arrays size
* Can pass in custom fields (elements) to extract from the feed

## Documentation ##

Place the plugin into a folder called feeds within the plugins directory.

Add the datasource config to config/database.php.

	public $feeds = array('datasource' => 'feeds.feed');

Within your model(s), there are multiple ways to declare which feeds to parse.

### 1) Using the $feedUrls property ###

If you want a model to represent a single feed, you can use the $feedUrls property in the class. (There is an example model within the plugin).

	public $feedUrls = 'http://feeds.feedburner.com/milesj';

Additionally, you can pass an array to $feedUrls to fetch multiple feeds.

	public $feedUrls = array(
		'Miles Johnson' => 'http://feeds.feedburner.com/milesj',
		'Starcraft 2 Armory' => 'http://feeds.feedburner.com/starcraft'
	);

Once you have defined your property, simply return your data with find().

	$feed = $this->MilesJFeed->find('all');

### 2) Using find() conditions ###

If you want to use a single model for all your feed parsing, you can use the find()'s conditions options to pass an array of URLs.

Additionally, the plugin comes packaged with a model called Aggregator that you may use for this task.

	public $uses = array('Feeds.Aggregator');

Then fetch the feeds by passing the conditions.

	$feeds = $this->Aggregator->find('all', array(
		'conditions' => array(
			'Starcraft 2 Armory' => 'http://feeds.feedburner.com/starcraft',
			'Miles Johnson' => 'http://feeds.feedburner.com/milesj'
		)
	));