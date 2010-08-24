<?php
/**
 * Feed Aggregator
 *
 * A CakePHP datasource that will take a list of feeds and aggregate them into a single array based on their timestamp.
 * Works with RSS, RDF and Atom types as well as built in support for caching and limitation.
 *
 * @author      Miles Johnson - www.milesj.me
 * @copyright   Copyright 2006-2010, Miles Johnson, Inc.
 * @license     http://www.opensource.org/licenses/mit-license.php - Licensed under The MIT License
 * @link        http://milesj.me/resources/script/feed-aggregator-component
 */

App::import('Core', array('HttpSocket', 'Folder'));
App::import(array(
    'type' => 'Vendor',
    'name' => 'TypeConverter',
    'file' => 'TypeConverter.php'
));

class FeedAggregatorSource extends DataSource {

    /**
	 * Current version: http://milesj.me/resources/logs/feed-aggregator-datasource
	 *
	 * @access public
	 * @var string
	 */
	public $version = '2.0';

	/**
	 * The processed feeds in array format.
	 *
	 * @access private
	 * @var array
	 */
	private $__processed = array();

	/**
	 * Types of feeds and unique element names.
	 *
	 * @access private
	 * @var array
	 */
	private $__typeMap = array(
		'rss' => array(
			'slug' => 'Rss',
            'type' => 'rss',
			'desc' => 'description',
			'date' => 'pubDate'
		),
		'rdf' => array(
			'slug' => 'RDF',
            'type' => 'rdf',
			'desc' => 'description',
			'date' => 'date'
		),
		'atom' => array(
			'slug' => 'Feed',
            'type' => 'atom',
			'desc' => 'summary',
			'date' => 'updated'
		)
	);

    /**
	 * Set the cache settings.
	 *
	 * @access public
	 * @param array $config
	 * @return void
	 */
	public function __construct($config = array()) {
		parent::__construct($config);

        $this->Http = new HttpSocket();

		if (Cache::config('feeds') === false) {
            $cachePath = CACHE .'feeds'. DS;

            // Create the cache dir
			if (!file_exists($cachePath)) {
				$this->Folder = new Folder();
				$this->Folder->create($cachePath, 0777);
			}

			Cache::config('feeds', array(
				'engine' 	=> 'File',
				'serialize' => true,
				'prefix'	=> '',
				'path' 		=> $cachePath,
				'duration'	=> '+1 day'
			));
		}
	}

    /**
     * Describe the supported feeds.
     *
     * @access public
     * @param object $Model
     * @return array
     */
    public function describe($Model) {
        return $this->__typeMap;
    }

    /**
     * Return a list of aggregrated feed URLs.
     *
     * @access public
     * @return array
     */
    public function listSources() {
        return $this->feeds;
    }

    public function read($Model, $query = array()) {
        if (!empty($query['feeds'])) {
			$key = 'feed_'. $query['group'];

			// Detect cached first
			if ($query['cache']) {
				Cache::set(array('duration' => $query['expires']));
				$results = Cache::read($key, 'feeds');

				if (is_array($results)) {
					return $this->_truncate($results, $query['limit']);
				}
			}

            if (!isset($this->__processed[$query['group']])) {
				$this->__processed[$query['group']] = array();
			}

			// Loop feeds
			foreach ($query['feeds'] as $source => $feed) {
				if ($response = $this->Http->get($feed)) {
                    $this->__processed[$query['group']] = $this->_process(TypeConverter::toArray($response), $query) + $this->__processed[$query['group']];
				}
			}

			// Sort by date
			if (!empty($this->__processed[$query['group']])) {
                $this->__processed[$query['group']] = array_filter($this->__processed[$query['group']]);
				krsort($this->__processed[$query['group']]);
			}

			// Cache
			if ($query['cache']) {
				Cache::set(array('duration' => $query['expires']));
				Cache::write($key, $this->__processed[$query['group']], 'feeds');
			}

			return $this->_truncate($this->__processed[$query['group']], $query['limit']);
		}

		return false;
    }

	/**
	 * Extracts a certain value from a variable.
	 *
	 * @access protected
	 * @param string $item
	 * @param array $slugs
	 * @return string
	 */
	protected function _extract($item, $slugs = array('value')) {
		if (is_array($item)) {
			foreach ($slugs as $slug) {
                if (isset($item['attributes'])) {
                    return $this->_extract($item['attributes'], $slugs);

                } else if (isset($item[$slug])) {
					return $item[$slug];
				}
			}
		} else {
			return $item;
		}
	}

	/**
	 * Processes the feed and rebuilds an array based on the feeds type (RSS, RDF, Atom).
	 *
	 * @access protected
	 * @param array $feed
	 * @param array $query
	 * @return boolean
	 */
	protected function _process($feed, $query) {
        $clean = array();

        if (isset($feed['channel'])) {
            $master = $this->__typeMap['rss'];
            $root = $feed['channel']['item'];
            $title = $feed['channel']['title'];

        } else if (isset($feed['item'])) {
            $master = $this->__typeMap['rdf'];
            $root = $feed['item'];

        } else if (isset($feed['entry'])) {
            $master = $this->__typeMap['atom'];
            $root = $feed['entry'];
            $title = $feed['title'];
        }

        // Gather elements
        $elements = $query['elements'] + array('title', $master['date'], 'guid');

        if ($query['explicit']) {
			$elements[] = $master['desc'];
		}

		// Loop the feed
		foreach ($root as $row => $item) {
			if (is_numeric($row)) {
				$link = null;

				foreach (array('origLink', 'link', 'Link') as $l) {
					if (isset($item[$l])) {
						$link = $this->_extract($item[$l], array('value', 'href', 'src'));
					}
				}

				if ($link) {
					$data = array('link' => $link, 'channel' => $title);

					foreach ($elements as $element) {
						if (isset($item[$element])) {
							if ($element == $master['date']) {
								$index = 'date';
							} else if ($element == $master['desc']) {
								$index = 'description';
							} else {
								$index = $element;
							}

							$data[$index] = $this->_extract($item[$element]);
						}
					}

					$clean[date('Y-m-d H:i:s', strtotime($item[$master['date']]))] = $data;
				}
			}
		}

		return $clean;
	}

	/**
	 * Truncates the feed to a certain length.
	 *
	 * @access protected
	 * @param array $feed
	 * @param int $count
	 * @return array
	 */
	protected function _truncate($feed, $count = null) {
		if (!is_numeric($count)) {
			$count = 20;
		}

		if (count($feed) > $count) {
			$feed = array_slice($feed, 0, $count);
		}

		return $feed;
	}

}