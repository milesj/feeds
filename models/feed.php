<?php
/**
* Feed Aggregator
*
* A CakePHP model that connects to the feed aggregation datasource.
*
* @author      Miles Johnson - www.milesj.me
* @copyright   Copyright 2006-2010, Miles Johnson, Inc.
* @license     http://www.opensource.org/licenses/mit-license.php - Licensed under The MIT License
* @link        http://milesj.me/resources/script/feed-aggregator-component
*/

class Feed extends Model {

    /**
     * No database table needed.
     *
     * @access public
     * @var boolean
     */
    public $useTable = false;

    /**
     * Use the weGame datasource.
     *
     * @access public
     * @var boolean
     */
    public $useDbConfig = 'feedAggregator';

    /**
	 * List of feed URLs to be parsed.
	 *
	 * @access protected
	 * @var array
	 */
	protected $_feeds = array();

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
     * @param array $options
     * @return array
     */
    public function aggregate($group, $options = array()) {
        $options = $options + array('limit' => 20, 'explicit' => false, 'cache' => false, 'expires' => '+1 hour', 'elements' => array());
        $options['group'] = $group;
        $options['feeds'] = $this->_feeds[$group];

        return $this->find('all', $options);
    }

}