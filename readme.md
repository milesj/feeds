# Feeds v2.2 #

Feeds is a CakePHP plugin that provides the functionality to parse external RSS feeds into your application through the model layer.

## Requirements ##

* CakePHP 1.2.x, 1.3.x
* PHP 5.2.x
* SimpleXML - http://php.net/manual/book.simplexml.php

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

### 1) Fetching your data ###

You may use the models find() method to fetch your data. You can pass an array of URLs to parse as the conditions.

Additionally, the plugin comes packaged with a model called Aggregator that you may use for this task.

	public $uses = array('Feeds.Aggregator');

Then fetch the feeds by passing the conditions.

	$feeds = $this->Aggregator->find('all', array(
		'conditions' => array(
			'Starcraft 2 Armory' => 'http://feeds.feedburner.com/starcraft',
			'Miles Johnson' => 'http://feeds.feedburner.com/milesj'
		)
	));

### 2) Grabbing additional data from elements ###

By default, the class will grab the following elements: title, guid, link, date, image, author, description. The class will use a mapping of keys to determine which element to grab the data from, for example.

	$elements = array(
		'title',
		'guid' => array('guid', 'id'),
		'date' => array('date', 'pubDate', 'published', 'updated'),
		'link' => array('link', 'origLink'),
		'image' => array('image', 'thumbnail'),
		'author' => array('author', 'writer', 'editor', 'user'),
		'description' => array('description', 'desc', 'summary', 'content', 'text')
	);

The array above will search within the keys author, writer, editor and user to determine where to get the authors name. Once a value is found, it will exit early on.

If you need to grab additional data, you can use the fields option within find(). The following code was used on Twitter's API.

	$this->find('all', array(
		'fields' => array(
			'link' => array('id_str'),
			'description' => array('text'),
			'date' => array('created_at'),
			// Checks user.screen_name for the author value
			'author' => array(
				'keys' => array('user'),
				'attributes' => array('screen_name')
			),
			// Checks user.profile_image_url for the image value
			'image' => array(
				'keys' => array('user'),
				'attributes' => array('profile_image_url')
			)
		)
	));

### 3) Passing in a custom root ###

By default, the class will logically grab the list of items from an RSS, RDF and Atom feed. If you are using custom XML, or JSON (yes it works), you can define your own root element to grab the items from.

	$this->find('all', array(
		'feed' => array(
			'root' => 'items',
			// Cache this feed for 1 hour
			'cache' => 'cacheKey',
			'expires' => '+1 hour'
		)
	));