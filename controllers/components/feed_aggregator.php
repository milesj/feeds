<?php
/** 
 * Feed Aggregator Component
 *
 * A CakePHP Component that will take a list of feeds and aggregate them into a single array based on their timestamp.
 * Works with RSS, RDF and Atom types as well as built in support for cacheing and limitation.
 *
 * @author 		Miles Johnson - www.milesj.me
 * @copyright	Copyright 2006-2009, Miles Johnson, Inc.
 * @license 	http://www.opensource.org/licenses/mit-license.php - Licensed under The MIT License
 * @link		www.milesj.me/resources/script/feed-aggregator-component
 */

App::import('Core', array('HttpSocket', 'Xml'));
 
class FeedAggregatorComponent extends Object {

	/**
	 * Current version: www.milesj.me/resources/logs/feed-aggregator-component
	 *
	 * @access public
	 * @var string
	 */ 
	public $version = '1.4';   
	
	/**
	 * How many items to return.
	 *
	 * @access public
	 * @var int
	 */
	public $returnItemCount = 20;
	
	/**
	 * Should we grab the item description?
	 *
	 * @access public
	 * @var boolean
	 */
	public $grabDescription = false;
	
	/**
	 * Are there extra elements to grab from the feed?
	 *
	 * @access public
	 * @var array
	 */
	public $grabElements = array();
	
	/**
	 * Should we cache the final aggregated output?
	 *
	 * @access public
	 * @var boolean
	 */
	public $cache = false;
	
	/**
	 * When should the cache expire?
	 *
	 * @access public
	 * @var string
	 */
	public $expires = '+1 hour'; 

	/**
	 * List of feed URLs to be parsed.
	 *
	 * @access protected
	 * @var array
	 */
	protected $_feeds = array();
	
	/**
	 * The parsed feeds in array format.
	 *
	 * @access protected
	 * @var array
	 */
	protected $_parsed = array();
	
	/**
	 * Types of feeds and unique element names.
	 *
	 * @access protected
	 * @var array
	 */
	private $__typeMap = array(
		'rss' => array(
			'slug' 	=> 'Rss',
			'desc' 	=> 'description',
			'date'	=> 'pubDate'
		),
		'rdf' => array(
			'slug' 	=> 'RDF',
			'desc' 	=> 'description',
			'date'	=> 'date'
		),
		'atom' => array(
			'slug' 	=> 'Feed',
			'desc' 	=> 'summary',
			'date'	=> 'updated'
		)
	);
	
	/**
	 * Load classes.
	 *
	 * @access public
	 * @uses HttpSocket
	 * @return void
	 */ 
	public function __construct() {
		parent::__construct();
		
		$this->Http = new HttpSocket();
		
		if (Cache::config('feeds') === false) {
			Cache::config('feeds', array(
				'engine' 	=> 'File',
				'serialize' => true,
				'prefix'	=> ''
			));
		}	
	}
	
	/**
	 * Adds a feed to the list of feeds to be aggregated.
	 *
	 * @access public
	 * @param string $group
	 * @param array $feeds
	 * @return void
	 */
	public function addFeed($group = 'default', $feeds = array()) {
		if (empty($group)) {
			$group = 'default';
		}
			
		if (!empty($feeds) && is_array($feeds)) {
			foreach ($feeds as $source => $url) {
				if (preg_match('/^(?:https?):\/\/(?:[-A-Za-z0-9]+\.)+[A-Za-z]{2,4}(?:[-a-zA-Z0-9._\/&:=+%?]+)?$/', strtolower($url))) {
					$this->_feeds[$group][$source] = $url;
				}
			}
		}
	}
	
	/**
	 * Grab all the feeds and combine them; truncate if necessary.
	 *
	 * @access public
	 * @uses XML
	 * @param string $group
	 * @param int $count
	 * @return array
	 */
	public function aggregate($group = 'default', $count = null) {
		if (!empty($this->_feeds[$group])) {
			$key = 'feed_'. $group;
			
			// Detect cached first
			if ($this->cache === true) {
				Cache::set(array('duration' => $this->expires));
				$results = Cache::read($key, 'feeds');
				
				if (is_array($results)) {
					return $this->_truncate($results, $count);
				}
			}
		
			// Loop feeds
			foreach ($this->_feeds[$group] as $source => $feed) {
				$feedSource = $this->Http->get($feed);
				
				if (!empty($feedSource)) {
					$feedXml = new Xml($feedSource);
					$feedType = strtolower($feedXml->children[0]->name);
					$feedArray = $feedXml->toArray();
					
					// Atom
					if ($feedType == 'feed') {
						$type = 'atom';
						
					// RSS 2.0, RDF 2.0
					} else {
						$type = $feedType;
					}
					
					$this->_parseType($type, $feedArray, $group, $source);
				}
			}
			
			if (!isset($this->_parsed[$group])) {
				$this->_parsed[$group] = array();
			}
			
			// Sort by date
			if (!empty($this->_parsed[$group])) {
				krsort($this->_parsed[$group]);
			}
			
			// Cache
			if ($this->cache === true) {
				Cache::set(array('duration' => $this->expires));
				Cache::write($key, $this->_parsed[$group], 'feeds');
			}
			
			return $this->_truncate($this->_parsed[$group], $count);
		}
		
		return false;
	}
	
	/**
	 * Get a certain value from a variable.
	 *
	 * @access protected
	 * @param string $item
	 * @param array $slugs
	 * @return string
	 */
	protected function _getValue($item, $slugs = array('value')) {
		if (is_array($item)) {
			foreach ($slugs as $slug) {
				if (isset($item[$slug])) {
					$return = $item[$slug];
				}
			}
		} else {
			$return = $item;
		}	
		
		return $return;
	}
	
	/**
	 * Parses the feed and rebuilds an array based on the feeds type (RSS, RDF, Atom).
	 *
	 * @access protected
	 * @param string $type
	 * @param array $feed
	 * @param string $group
	 * @param string $source
	 * @return boolean
	 */
	protected function _parseType($type = 'rss', $feed, $group, $source) {
		if (isset($this->__typeMap[$type])) {
			$master = $this->__typeMap[$type];
		} else {
			return false;
		}
		
		switch ($type) {
			case 'rss':		$items = $feed['Rss']['Channel']['Item']; break;
			case 'rdf':		$items = $feed['RDF']['Item']; break;
			case 'atom': 	$items = $feed['Feed']['Entry']; break;
		}
		
		if (empty($items)) {
			$this->_parsed[$group] = array();
			return;
		}
		
		// Set elements
		$elements = array('title', $master['date'], 'guid');
		
		if ($this->grabDescription === true) {
			$elements[] = $master['desc'];
		}

		if (!empty($this->grabElements) && is_array($this->grabElements)) {
			$elements = array_merge($elements, $this->grabElements);
		}
		
		// Loop the feed
		foreach ($items as $row => $item) {
			if (is_numeric($row)) {
				$link = null;
				$linkTexts = array('origLink', 'link', 'Link');
				foreach ($linkTexts as $l) {
					if (isset($item[$l])) {
						$link = $this->_getValue($item[$l], array('value', 'href', 'src'));
					}
				}
				
				if (!empty($link)) {
					$data = array('link' => $link, 'channel' => $source);
					
					foreach ($elements as $element) {
						if (isset($item[$element])) {
							if ($element == $master['date']) {
								$index = 'date';
							} else if ($element == $master['desc']) {
								$index = 'description';
							} else {
								$index = $element;
							}
							
							$data[$index] = $this->_getValue($item[$element]);
						}
					}
					
					$this->_parsed[$group][date('Y-m-d H:i:s', strtotime($item[$master['date']]))] = $data;
				}
			}
		}
		
		return true;
	}
	
	/**
	 * Truncates the feed to a certain amount.
	 * 
	 * @access protected
	 * @param array $feed
	 * @param int $count
	 * @return array
	 */
	protected function _truncate($feed, $count = null) {
		if (!is_numeric($count)) {
			$count = $this->returnItemCount;
		}
		
		if (count($feed) > $count) {
			$feed = array_slice($feed, 0, $count);
		}
		
		return $feed;
	}
	
}
