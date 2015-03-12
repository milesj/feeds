# Feeds #

*Documentation may be outdated or incomplete as some URLs may no longer exist.*

*Warning! This codebase is deprecated and will no longer receive support; excluding critical issues.*

A CakePHP Component that will take a list of feeds and aggregate them into a single array based on their timestamp.

* Supports RSS 2.0, RSS 1.0 (RDF) and Atom feed types
* Can support as many feeds as you wish
* Separates feeds into groups so you can add accordingly and not conflict
* Uses the HttpSocket and XML libraries to parse the feeds
* Built in cache support (with a little configuration)
* Has a max return limit to cut down the returned arrays size

## Installation ##

Place the plugin into a folder called `Feeds/` within your application plugins directory and add the datasource to `Config/database.php`.

```php
public $feeds = array('datasource' => 'Feeds.FeedSource');
```

Once your datasource has been defined, you can now use it within your models. To do so, set the `$useDbConfig` property in your model to "feeds" and `$useTable` to false. When you do this, the models `find()` method will interact with the datasource to parse XML and RSS feeds.

```php
class Example extends Model {
    public $useTable = false;
    public $useDbConfig = 'feeds';
}
```

### Caching Results ###

This configuration should be automatically called when the datasource is used, but you can overwrite it. You would call this like any other `Cache::config()`, just make sure that they key is "feeds".

```php
Cache::config('feeds', array(
    'engine'     => 'File',
    'serialize'    => true,
    'prefix'     => ''
));
```

## Configuration ##

You can pass an array of options as the 2nd argument of your models find; exactly like regular model usage. The array accepts the following parameters:

* `fields` - A mapping of element names to extract data from (more in chapter 4)
* `order` - The order in which to sort the response (default date ASC)
* `limit` - How many results to return (default 20)
* `feed.root` - Custom name for the XML documents root (more in chapter 5)
* `feed.cache` - True/false to cache the response
* `feed.expires` - A strtotime() format for the cache duration

When defining the order, the order value will be an array of element to sort direction (similar to Cake's default implementation). The element name must be one of the following: title, guid, date, link, image, author, description. The sort direction should be either ASC or DESC. 

```php
'order' => array('title' => 'ASC')
```

Here's a more robust example:

```php
$this->find('all', array(
    'conditions' => $urls,
    'order' => array('title' => 'ASC'),
    'limit' => 5,
    'feed' => array(
        'root' => 'articles',
        'cache' => true,
        'expires' => '+5 minutes'
    )
));
```

## Fetching Feeds ##

You may use the models `find()` method to fetch your data. You can pass an array of URLs to parse as the conditions. Additionally, the plugin comes packaged with a model called `Aggregator` that you may use for this task.

```php
public $uses = array('Feeds.Aggregator');
```

Then fetch the feeds by passing the conditions.

```php
$this->Aggregator->find('all', array(
    'conditions' => array(
        'Starcraft 2 Armory' => 'http://feeds.feedburner.com/starcraft',
        'Miles Johnson' => 'http://feeds.feedburner.com/milesj'
    )
));
```

If you do not wish to use the `Aggregator`, you could create your own model with its own method.

```php
public function getStarcraftFeeds() {
    return $this->find('all', array(
        'conditions' => array(
            'Starcraft 2 Armory' => 'http://feeds.feedburner.com/starcraft'
        )
    ));
}
```

## Extracting Data ##

By default, the datasource will grab the following elements: title, guid, link, date, image, author, description. The datasource will use a mapping of keys to determine which element to grab the data from, for example.

```php
$elements = array(
    'title',
    'guid' => array('guid', 'id'),
    'date' => array('date', 'pubDate', 'published', 'updated'),
    'link' => array('link', 'origLink'),
    'image' => array('image', 'thumbnail'),
    'author' => array('author', 'writer', 'editor', 'user'),
    'description' => array('description', 'desc', 'summary', 'content', 'text')
);
```

The array above will search within the keys author, writer, editor and user to determine where to get the authors name. Once a value is found, it will exit early on.

If you need to grab additional data, you can use the fields option within `find()`. The following code was used on Twitter's API.

```php
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
```

## Custom Document Root ##

By default, the class will logically grab the list of items from an RSS, RDF and Atom feed. If you are parsing custom XML, or JSON (yes it works), you can define your own root element to grab the items from.

```php
$this->find('all', array(
    'feed' => array(
        'root' => 'items',
        // Cache this feed for 1 hour
        'cache' => 'cacheKey',
        'expires' => '+1 hour'
    )
));
```
