<?php
/**
 * Aggregator Model
 * 
 * A model that connects to the feed datasource and defines a custom find() function specific to aggregation.
 *
 * @author      Miles Johnson - http://milesj.me
 * @copyright   Copyright 2006-2011, Miles Johnson, Inc.
 * @license     http://opensource.org/licenses/mit-license.php - Licensed under The MIT License
 * @link        http://milesj.me/code/cakephp/feeds
 */

class Aggregator extends Model {

	/**
	 * No database table needed.
	 *
	 * @access public
	 * @var boolean
	 */
	public $useTable = false;

	/**
	 * Use the feeds datasource.
	 *
	 * @access public
	 * @var boolean
	 */
	public $useDbConfig = 'feeds';

	/**
	 * Overwrite the find method to be specific for feed aggregation.
	 * Set the default settings and prepare the URLs.
	 *
	 * @access public
	 * @param string $type
	 * @param array $options
	 *		- root: A custom root node
	 *		- cache: Key for cache
	 *		- expires: How long should the feed be cached
	 * @return array
	 */
	public function find($type, array $options = array()) {
		$options = $options + array(
			'fields' => array(),
			'order' => array('date' => 'ASC'),
			'limit' => 20,
			'feed' => array(
				'root' => '',
				'cache' => false,
				'expires' => '+1 hour'
			)
		);

		return parent::find($type, $options);
	}

}