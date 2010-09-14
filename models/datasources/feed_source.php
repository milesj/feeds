<?php
/**
 * A datasource that will take a list of feeds and aggregate them into a single array based on their timestamp.
 * Works with RSS, RDF and Atom types as well as built in support for caching and limitation.
 *
 * @author		Miles Johnson - www.milesj.me
 * @copyright	Copyright 2006-2010, Miles Johnson, Inc.
 * @license		http://opensource.org/licenses/mit-license.php - Licensed under The MIT License
 * @link		http://milesj.me/resources/script/feeds-plugin
 */

App::import('Core', array('HttpSocket', 'Folder'));
App::import(array(
    'type' => 'Vendor',
    'name' => 'TypeConverter',
    'file' => 'TypeConverter.php'
));

class FeedSource extends DataSource {

    /**
	 * Current version: http://milesj.me/resources/logs/feeds-plugin
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
	private $__feeds = array();

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

			if (!file_exists($cachePath)) {
				$Folder = new Folder();
				$Folder->create($cachePath, 0777);
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
		return array_keys($this->__feeds);
    }

	/**
	 * Grab the feeds through an HTTP request and parse it out into an array.
	 *
	 * @access public
	 * @param object $Model
	 * @param array $query
	 * @return array
	 */
    public function read($Model, $query = array()) {
		if (!isset($query['feed'])) {
			$query['feed'] = array(
				'explicit' => false,
				'cache' => false,
				'expires' => '+1 hour'
			);
		}
	
		if (!empty($query['conditions'])) {
			$cache = $query['feed']['cache'];

			// Detect cached first
			if ($cache) {
				Cache::set(array('duration' => $query['feed']['expires']));
				$results = Cache::read($cache, 'feeds');

				if (is_array($results)) {
					return $this->_truncate($results, $query['limit']);
				}
			}

			// Request and parse feeds
			foreach ($query['conditions'] as $source => $url) {
				if ($response = $this->Http->get($url)) {
                    $this->__feeds[$source] = $this->_process($response, $query, $source);
				}
			}

			// Combine and sort feeds
			$results = array();

			if (!empty($this->__feeds)) {
				foreach ($this->__feeds as $source => $feed) {
					$results = $feed + $results;
				}

				$results = array_filter($results);
				krsort($results);

				// Cache
				if ($cache) {
					Cache::set(array('duration' => $query['feed']['expires']));
					Cache::write($cache, $results, 'feeds');
				}
			}

			return $this->_truncate($results, $query['limit']);
		}

		return false;
    }

	/**
	 * Extracts a certain value from a node.
	 *
	 * @access protected
	 * @param string $item
	 * @param array $slugs
	 * @return string
	 */
	protected function _extract($item, $slugs = array('value')) {
		if (is_array($item)) {
			foreach ($slugs as $slug) {
                if (!empty($item[$slug])) {
					return trim($item[$slug]);
					
				} else if (isset($item['attributes'])) {
                    return $this->_extract($item['attributes'], $slugs);
                }
			}
		} else {
			return trim($item);
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
	protected function _process($feed, $query, $source) {
		$feed = TypeConverter::toArray($feed);
        $clean = array();

        if (isset($feed['channel'])) {
            $master = $this->__typeMap['rss'];
            $root = $feed['channel']['item'];
            $title = $feed['channel']['title'];

        } else if (isset($feed['item'])) {
            $master = $this->__typeMap['rdf'];
            $root = $feed['item'];
            $title = $feed['title'];

        } else if (isset($feed['entry'])) {
            $master = $this->__typeMap['atom'];
            $root = $feed['entry'];
            $title = $feed['title'];
        }

        // Gather elements
        $elements = array('title', $master['date'], 'guid');

		if (is_array($query['fields'])) {
			$elements = $query['fields'] + $elements;
		}
		
        if ($query['feed']['explicit']) {
			$elements[] = $master['desc'];
		}

		// Loop the feed
		foreach ($root as $row => $item) {
			if (is_numeric($row)) {
				$link = null;

				foreach (array('origLink', 'link') as $linkKey) {
					if (isset($item[$linkKey])) {
						$link = $this->_extract($item[$linkKey], array('value', 'href', 'src'));
					}
				}

				if (!$link) {
					trigger_error('Feed '. $source .' does not have a valid link element.', E_USER_WARNING);
					continue;
				}
				
				$data = array(
					'link' => $link,
					'channel' => trim($title),
					'source' => $source
				);

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

		if (count($feed) < $count) {
			return $feed;
			
		} else if (count($feed) > $count) {
			$feed = array_slice($feed, 0, $count);
		}

		return $feed;
	}

}